<?php

namespace App\Http\Controllers;

use App\Jobs\RunScript;
use App\Models\ColorPalette;
use App\Models\Project;
use App\Models\ProjectGene;
use App\Models\ProjectParameter;
use App\Models\ProjectPlatform;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ProjectController extends Controller
{

    public function index() : View
    {
        $projects = Project::where('user_id', auth()->id())->orderByDesc('updated_at')->get();

        return view('projects.index', compact(['projects']));
    }

    public function open(Project $project) {

        setActiveProject($project);

        return redirect($project->getCurrentStepUrl());

        //return redirect()->route('import-data', ['project' => $project->id]);
    }

    public function create(): View
    {

        return view('wizard.new-project', ['platforms' => ProjectPlatform::all()]);

    }

    public function store() {

        $name = request('name');
        $description = request('description');
        $project_platform_id = request('project_platform_id');

        if(strlen(trim($name)) < 4)
            return response('Name has to be at least 4 characters long', 400);

        try {
            $project = Project::create(['name' => $name, 'description' => $description, 'project_platform_id' => $project_platform_id, 'user_id' => auth()->id()]);

            setActiveProject($project);

            return response(route('import-data',['project' => $project->id]));
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function edit(Project $project) {

        $platforms = ProjectPlatform::all();

        return view('wizard.edit-project', compact(['project', 'platforms']));

    }

    public function update(Project $project) {
        $name = request('name');
        $description = request('description');
        $project_platform_id = request('project_platform_id');

        if(strlen(trim($name)) < 4)
            return response('Name has to be at least 4 characters long', 400);

        $project->name = $name;
        $project->description = $description;
        $project->project_platform_id = $project_platform_id;

        $project->save();

        return response(route('my-projects'));
    }

    public function getProjectParameters(Project $project) {

        return $project->project_parameters;

    }


    public function import_data(Project $project): View
    {
        $samples = $project->samples;

        return view('wizard.import-data')->with(compact('project', 'samples'));

    }

    public function readExcelMetadataFile(Project $project)
    {
        if(request()->file('metadata')) {
            $file = request()->file('metadata');

            //Save a copy of the uploaded Excel file
            $file->storeAs($project->workingDir(), 'uploaded_metadata_' . $file->getClientOriginalName());

            //Read the first sheet and export it as CSV
            $data = Excel::toArray([], $file);
            $firstSheetData = $data[0];
            $csvData = '';
            foreach ($firstSheetData as $row) {
                $csvData .= implode(',', $row) . "\n";
            }

            return $csvData;
        }

        return '';
    }

    public function save_metadata(Project $project) {

        $metadata = request('metadata');

        ProjectParameter::updateOrCreate(['parameter' => 'metadata', 'project_id' => $project->id], ['type' => 'json', 'value' => json_encode($metadata)]);

        $contents = 'samplename';
        foreach ($metadata as $meta)
            if(strlen($meta['name'])) $contents .= ',' . $meta['name'];
        $contents .= "\n";
        foreach($project->samples as $sample) {
            $contents .= $sample->name;
            foreach ($metadata as $meta) {
                $contents .= (array_key_exists($sample->name, $meta['values']) && strlen($meta['values'][$sample->name])) ? ',' . $meta['values'][$sample->name] : ',';
            }
            $contents .= "\n";
        }

        Storage::put($project->workingDir() . 'clinical_data.csv', $contents);

        return json_encode(request('metadata'));
    }

    public function qc_data_transformation(Project $project) {
        $samples = $project->samples;
        $color_palettes = ColorPalette::orderBy('label')->get();
        return view('wizard.qc_data_transformation')->with(compact('project', 'samples', 'color_palettes'));
    }

    public function destroy(Project $project) {

        $project->delete();

        session()->forget('project_id');

        return 'OK';
    }

    public function go_to_step(Project $project, $step) {
        $project->current_step = $step;
        $project->save();

        return route('qc-data-transformation', ['project' => $project->id]);

        /*if($step === 2)
            return redirect()->route('qc-data-transformation', ['project' => $project->id]);*/
    }


    public function getJobPositionInQueue(Project $project) {
        $jobId = array_key_exists('job.' . request('command'), $project->project_parameters) ? $project->project_parameters['job.' . request('command')] : 0 ;
        return $jobId ? $project->getJobPositionInQueue($jobId) : 0;
    }

    public function cancelJobInQueue(Project $project) {

        $jobId = array_key_exists('job.' . request('command'), $project->project_parameters) ? $project->project_parameters['job.' . request('command')] : 0 ;

        $tasks = Task::where('user_id', auth()->id())->where('project_id', $project->id)->where('process', request('command'))->whereNull('finished_at')->orderByDesc('scheduled_at')->get();

        $task = null;
        if($tasks->count()) {
            $task = $tasks[0];
            $task->cancelled_at = now();
            $task->save();

            Log::info('ProjectController- Cancelling task ' . $task->id . ' on ' . $task->cancelled_at);
        }

        Log::info('ProjectController-Cancelling job with id: ' . $jobId);

        return $jobId ? $project->cancelJobInQueue($jobId, $task) : 0;
    }

    public function getJobsInQueue(Project $project) {
        return $project->getJobsInQueue(request('command'));
    }

    public function setJobEmailNotification(Project $project) {

        $command = request('command');
        $sendEmail = request('sendemail', false);
        $project->setJobEmailNotification($command, $sendEmail);

        return 'OK';
    }

    public function createStList(Project $project) {

        $jobId = $project->createJob('Data import', 'createStList', []);

        return $project->getJobPositionInQueue($jobId);

    }


    public function searchGenes(Project $project) {
        $query = request('query');

        $context = (request()->has('context') && strlen(request('context'))) ? request('context') : 'I';

        if(strlen($query)) {
            $genes = $project->genes($context)->where('name', 'LIKE', $query . '%')->orderBy('name')->limit(100);
            return $genes->pluck('name');
        }

        return [];
    }

    public function searchGenesRegexp(Project $project) {
        $query = request('query');

        $context = (request()->has('context') && strlen(request('context'))) ? request('context') : 'I';

        if(strlen($query)) {
            $genes = $project->genes($context)->where('name', 'REGEXP', $query)->orderBy('name')->limit(100);
            return $genes->pluck('name');
        }

        return [];
    }


    public function applyFilter(Project $project) {

        //RunScript::dispatch('Filter data', $project, 'applyFilter', request('parameters'));
        //return 'OK';

        $jobId = $project->createJob('Filter data', 'applyFilter', request('parameters'));
        return $project->getJobPositionInQueue($jobId);

    }

    public function generateFilterPlots(Project $project) {


        //RunScript::dispatch('Generate filter plots', $project, 'generateFilterPlots', ['color_palette' => request('color_palette'), 'variable' => request('variable')]);

        //$project->generateFilterPlots(['color_palette' => request('color_palette'), 'variable' => request('variable')]);

        $jobId = $project->createJob('Generate filter plots', 'generateFilterPlots', ['color_palette' => request('color_palette'), 'variable' => request('variable')]);
        return $project->getJobPositionInQueue($jobId);
    }

    public function applyNormalization(Project $project) {

        $jobId = $project->createJob('Normalize data', 'applyNormalization', request('parameters'));

        //RunScript::dispatch('Normalize data', $project, 'applyNormalization', request('parameters'));

        return $project->getJobPositionInQueue($jobId);

    }

    public function generateNormalizationPlots(Project $project) {

        //RunScript::dispatch('Generate normalization plots', $project, 'generateNormalizationPlots', ['color_palette' => request('color_palette'), 'gene' => request('gene')]);

        //$project->generateNormalizationPlots(['color_palette' => request('color_palette'), 'gene' => request('gene')]);

        $jobId = $project->createJob('Generate normalization plots', 'generateNormalizationPlots', ['color_palette' => request('color_palette'), 'gene' => request('gene')]);
        return $project->getJobPositionInQueue($jobId);
    }

    public function generateNormalizationData(Project $project) {
        $jobId = $project->createJob('Generate normalization data', 'generateNormalizationData', [], 'low');
        return $project->getJobPositionInQueue($jobId);
    }

    public function applyPca(Project $project) {
        $jobId = $project->createJob('Principal Component Analysis', 'applyPca', ['n_genes' => request('n_genes')]);
        return $project->getJobPositionInQueue($jobId);
    }
    public function pcaPlots(Project $project) {
        $jobId = $project->createJob('Principal Component Analysis Plots', 'pcaPlots', ['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'n_genes' => request('n_genes'), 'hm_display_genes' => request('hm_display_genes')]);
        return $project->getJobPositionInQueue($jobId);
    }

    public function quiltPlot(Project $project) {

        $jobId = $project->createJob('Quilt plot', 'quiltPlot', ['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'sample1' => request('sample1'), 'sample2' => request('sample2')]);
        return $project->getJobPositionInQueue($jobId);

    }


    public function stplot_visualization(Project $project) {
        $samples = $project->samples;
        $color_palettes = ColorPalette::orderBy('label')->get();
        return view('wizard.stplot-visualization')->with(compact('project', 'samples', 'color_palettes'));
    }

    public function stplot_quilt(Project $project) {

        $parameters = [
            'genes' => request('genes'),
            'ptsize' => request('ptsize'),
            'col_pal' => request('col_pal'),
            'data_type' => request('data_type')
        ];

        //RunScript::dispatch('STplot - Quilt plot', $project, 'STplotQuilt', $parameters);

        //return $project->STplotQuilt($parameters);

        $jobId = $project->createJob('STplot - Quilt plot', 'STplotQuilt', $parameters);
        return $project->getJobPositionInQueue($jobId);

    }

    public function stplot_expression_surface(Project $project) {

        $parameters = [
            'genes' => request('genes'),
            //'col_pal' => request('col_pal'),
        ];

        ProjectParameter::updateOrCreate(['parameter' => 'STplotExpressionSurface.genes', 'project_id' => $project->id], ['type' => 'json', 'value' => json_encode(request('genes'))]);

        $jobId = $project->createJob('STplot - Expression surface', 'STplotExpressionSurface', $parameters);
        return $project->getJobPositionInQueue($jobId);

    }

    public function stplot_expression_surface_plots(Project $project) {

        $parameters = [
            'genes' => json_decode($project->project_parameters['STplotExpressionSurface.genes']),
            'col_pal' => request('col_pal'),
        ];

        $jobId = $project->createJob('STplot - Expression surface', 'STplotExpressionSurfacePlots', $parameters);
        return $project->getJobPositionInQueue($jobId);

    }

    public function sthet_spatial_het(Project $project) {
        $samples = $project->samples;
        $color_palettes = ColorPalette::orderBy('label')->get();
        return view('wizard.sthet-spatial-het')->with(compact('project', 'samples', 'color_palettes'));
    }

    public function sthet_spatial_het_calculate(Project $project) {

        $parameters = [
            'genes' => request('genes'),
            'method' => request('method'),
            'color_pal' => request('color_pal'),
            'plot_meta' => request('plot_meta')
        ];
        $jobId = $project->createJob('SThet - Spatial heterogeneity', 'SThet', $parameters);
        return $project->getJobPositionInQueue($jobId);

    }

    public function sthet_spatial_het_plot(Project $project) {

        $parameters = [
            'genes' => request('plot_genes'),
            'color_pal' => request('color_pal'),
            'plot_meta' => request('plot_meta')
        ];
        $jobId = $project->createJob('SThet - Spatial heterogeneity Plot', 'SThetPlot', $parameters);
        return $project->getJobPositionInQueue($jobId);

    }

    public function spatial_domain_detection(Project $project) {
        $samples = $project->samples;
        $color_palettes = ColorPalette::orderBy('label')->get();
        return view('wizard.spatial-domain-detection')->with(compact('project', 'samples', 'color_palettes'));
    }

    public function sdd_stclust(Project $project) {
        $jobId = $project->createJob('Spatial Domain Detection - STclust', 'STclust', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }


    public function differential_expression(Project $project) {
        $samples = $project->samples;
        return view('wizard.differential-expression')->with(compact('project', 'samples'));
    }

    public function differential_expression_non_spatial(Project $project) {
        $jobId = $project->createJob('Differential Expression - STDiff Non-spatial tests', 'STDiffNonSpatial', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function differential_expression_spatial(Project $project) {
        $jobId = $project->createJob('Differential Expression - STDiff Spatial tests', 'STDiffSpatial', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function spatial_gene_set_enrichment(Project $project) {
        $samples = $project->samples;
        return view('wizard.spatial-gene-set-enrichment')->with(compact('project', 'samples'));
    }

    public function spatial_gene_set_enrichment_stenrich(Project $project) {
        $jobId = $project->createJob('Spatial gene set enrichment', 'STEnrich', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function spatial_gradients(Project $project) {
        $samples = $project->samples;
        return view('wizard.spatial-gradients')->with(compact('project', 'samples'));
    }

    public function spatial_gradients_stgradients(Project $project) {
        $jobId = $project->createJob('Spatial gradients', 'STGradients', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }


}

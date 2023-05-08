<?php

namespace App\Http\Controllers;

use App\Jobs\RunScript;
use App\Models\ColorPalette;
use App\Models\Project;
use App\Models\ProjectGene;
use App\Models\ProjectParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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

        return view('wizard.new-project');

    }

    public function store() {

        $name = request('name');
        $description = request('description');

        if(strlen(trim($name)) < 4)
            return response('Name has to be at least 4 characters long', 400);

        try {
            $project = Project::create(['name' => $name, 'description' => $description, 'user_id' => auth()->id()]);

            setActiveProject($project);

            return response(route('import-data',['project' => $project->id]));
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function edit(Project $project) {

        return view('wizard.edit-project', compact(['project']));

    }

    public function update(Project $project) {
        $name = request('name');
        $description = request('description');

        if(strlen(trim($name)) < 4)
            return response('Name has to be at least 4 characters long', 400);

        $project->name = $name;
        $project->description = $description;

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

        return 'OK';
    }

    public function go_to_step(Project $project, $step) {
        $project->current_step = $step;
        $project->save();

        return route('qc-data-transformation', ['project' => $project->id]);

        if($step === 2)
            return redirect()->route('qc-data-transformation', ['project' => $project->id]);
    }


    public function getJobPositionInQueue(Project $project) {
        $jobId = array_key_exists('job.' . request('command'), $project->project_parameters) ? $project->project_parameters['job.' . request('command')] : 0 ;
        return $jobId ? $project->getJobPositionInQueue($jobId) : 0;
    }

    public function setJobEmailNotification(Project $project) {

        $command = request('command');
        $sendEmail = request('sendemail', false);
        $project->setJobEmailNotification($command, $sendEmail);

        return 'OK';
    }

    public function createStList(Project $project) {

        //$project->createStList();

        //$job = RunScript::dispatch('Data import', $project, 'createStList', []);

        /*$job = new RunScript('Data import', $project, 'createStList', []);

        $jobId = Queue::connection()->push($job);

        //$queueName = Queue::getName('database');
        $queueName = env('QUEUE_DEFAULT_NAME');
        $queuePosition = DB::table('jobs')
            ->where('queue', $queueName)
            ->where('id', '<=', $jobId)
            ->count();*/

        $jobId = $project->createJob('Data import', 'createStList', []);

        return $project->getJobPositionInQueue($jobId);

    }


    public function searchGenes(Project $project) {
        $query = request('query');

        $context = (request()->has('context') && strlen(request('context'))) ? request('context') : 'initial';

        if(strlen($query)) {

            $genes = ProjectGene::where('project_id', $project->id)->where('context', $context)->where('gene', 'LIKE', $query . '%')->orderBy('gene')->limit(100);

            //$sql_with_bindings = Str::replaceArray('?', $genes->getBindings(), $genes->toSql());
            //dd($sql_with_bindings);

            return $genes->pluck('gene');

        }

        return [];
    }

    public function searchGenesRegexp(Project $project) {
        $query = request('query');

        $context = (request()->has('context') && strlen(request('context'))) ? request('context') : 'initial';

        if(strlen($query)) {
            $genes = ProjectGene::where('context', $context)->where('gene', 'REGEXP', $query)->orderBy('gene')->limit(100);

            //$sql_with_bindings = Str::replaceArray('?', $genes->getBindings(), $genes->toSql());
            //dd($sql_with_bindings);

            return $genes->pluck('gene');
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

        $project->current_step = 4;
        $project->save();

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

    public function applyPca(Project $project) {

        //RunScript::dispatch('Principal Component Analysis', $project, 'applyPca', ['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'n_genes' => request('n_genes'), 'hm_display_genes' => request('hm_display_genes')]);

        //return $project->applyPca(['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'n_genes' => request('n_genes'), 'hm_display_genes' => request('hm_display_genes')]);

        $jobId = $project->createJob('Principal Component Analysis', 'applyPca', ['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'n_genes' => request('n_genes'), 'hm_display_genes' => request('hm_display_genes')]);
        return $project->getJobPositionInQueue($jobId);

    }

    public function quiltPlot(Project $project) {

        //RunScript::dispatch('Quilt plot', $project, 'quiltPlot', ['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'sample1' => request('sample1'), 'sample2' => request('sample2')]);

        //return $project->quiltPlot(['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'sample1' => request('sample1'), 'sample2' => request('sample2')]);

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
            'col_pal' => request('col_pal'),
        ];

        //RunScript::dispatch('STplot - Expression surface', $project, 'STplotExpressionSurface', $parameters);

        //return $project->STplotExpressionSurface($parameters);

        $jobId = $project->createJob('STplot - Expression surface', 'STplotExpressionSurface', $parameters);
        return $project->getJobPositionInQueue($jobId);

    }

    public function sthet_spatial_het(Project $project) {
        $samples = $project->samples;
        $color_palettes = ColorPalette::orderBy('label')->get();
        return view('wizard.sthet-spatial-het')->with(compact('project', 'samples', 'color_palettes'));
    }

    public function sthet_spatial_het_plot(Project $project) {

        $parameters = [
            'genes' => request('genes'),
            'method' => request('method'),
            'color_pal' => request('color_pal'),
            'plot_meta' => request('plot_meta')
        ];

        //RunScript::dispatch('SThet - Spatial heterogeneity', $project, 'SThetPlot', $parameters);

        //return $project->SThetPlot($parameters);

        $jobId = $project->createJob('SThet - Spatial heterogeneity', 'SThetPlot', $parameters);
        return $project->getJobPositionInQueue($jobId);

    }


}

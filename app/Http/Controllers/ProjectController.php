<?php

namespace App\Http\Controllers;

use App\Models\ColorPalette;
use App\Models\Project;
use App\Models\Sample;
use App\Models\File as SampleFile;
use App\Models\ProjectParameter;
use App\Models\ProjectPlatform;
use App\Models\ProjectProcessFiles;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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


    public function create_cosmx_temp_project() {
        try {

            //Create the Sandbox project for the user
            $project = Project::create(['name' => 'Test CosMx project ' . Carbon::now()->format('Y-m-d'), 'description' => 'Test CosMx project with preloaded samples to explore spatialGE functionalities', 'project_platform_id' => Project::COSMX_PLATFORM, 'user_id' => auth()->id()]);
            $project->current_step = 1;
            $project->save();



            //Copy sample and other initial files
            $userFolder = auth()->user()->getUserFolder();
            $projectFolder = $userFolder . $project->id . '/';
            Storage::createDirectory($projectFolder);
            File::copyDirectory(Storage::path('common/test_projects/cosmx/samples'), Storage::path($projectFolder));


            //Load samples and files from disk
            $json = json_decode(Storage::get('common/test_projects/cosmx/samples.json'));
            //Create new samples and files in the DB
            $fovSamples = [];
            $metadata = ["name" => "tumor", "values" => []];
            $_metadata = ["Lung5_Rep1" => "Tumor1", "Lung5_Rep2" => "Tumor1", "Lung5_Rep3" => "Tumor1","Lung9_Rep1" => "Tumor2","Lung9_Rep2" => "Tumor2"];
            foreach($json as $sampleName => $files) {

                $sample = Sample::create(['name' => $sampleName]);
                $sample->projects()->save($project);
                foreach($files as $fileData) {
                    $fileModel = SampleFile::create(['filename' => $fileData->filename, 'type' => $fileData->type]);
                    $sample->files()->save($fileModel);
                }


                // Get an instance of the directory iterator for the directory
                //$files = new \DirectoryIterator(Storage::path($projectFolder . $sampleName . '/spatial/'));

                // Iterate over each file in the directory
                /*foreach ($files as $file) {
                    // Check if the file is a regular file and ends with '.jpg'
                    if ($file->isFile() && $file->getExtension() === 'jpg') {
                        // Get the filename without extension
                        $filename = $file->getBasename('.jpg');

                        // Extract the XXX number from the filename
                        $xxxNumber = intval(preg_replace('/[^0-9]/', '', $filename));

                        Storage::createDirectory($projectFolder . $sampleName . '_fov_' . $xxxNumber);
                        Storage::createDirectory($projectFolder . $sampleName . '_fov_' . $xxxNumber . '/spatial');

                        Storage::move($projectFolder . $sampleName . '/spatial/' . $file->getBasename(), $projectFolder . $sampleName . '_fov_' . $xxxNumber . '/spatial/' . $sampleName . '_fov_' . $xxxNumber . '.jpg');

                        foreach($_metadata as $key => $value)
                            if (strpos($sampleName, $key) === 0)
                                $metadata["values"][$sampleName] = $_metadata[$key];

                        $fovSamples[$sampleName . '_fov_' . $xxxNumber] = [];
                        $fovSamples[$sampleName . '_fov_' . $xxxNumber][] = ["filename" => $sampleName . "_exprMat_file.csv", "type" => "expressionFile"];
                        $fovSamples[$sampleName . '_fov_' . $xxxNumber][] = ["filename" => $sampleName . "_metadata_file.csv", "type" => "coordinatesFile"];
                        $fovSamples[$sampleName . '_fov_' . $xxxNumber][] = ["filename" => $sampleName . '_fov_' . $xxxNumber . '.jpg', "type" => "imageFile"];

                    }
                }*/
            }
            //Storage::put($projectFolder . 'samples.json', json_encode($fovSamples, JSON_PRETTY_PRINT));
            //Storage::put($projectFolder . 'metadata.json', json_encode(["meta" => json_encode($metadata)], JSON_PRETTY_PRINT));


            // //Insert genes into DB
            // $project->createGeneList('common/sandbox/genes.csv', 'I');

            // //Load parameters from disk
            // $json = json_decode(Storage::get('common/sandbox/parameters.json'));
            // //Create new parameters in the DB
            // foreach($json as $parameter) {
            //     ProjectParameter::updateOrCreate(['parameter' => $parameter->parameter, 'project_id' => $project->id, 'tag' => $parameter->tag], ['type' => $parameter->type, 'value' => $parameter->value]);
            // }



            // File::copy(Storage::path('common/sandbox/initial_stlist.RData'), Storage::path("$projectFolder/initial_stlist.RData"));
            // File::copy(Storage::path('common/sandbox/initial_stlist_summary.csv'), Storage::path("$projectFolder/initial_stlist_summary.csv"));

            // //Add parameter to indicate that this is the 'Demo project'
            // ProjectParameter::updateOrCreate(['parameter' => 'isDemoProject', 'project_id' => $project->id, 'tag' => ''], ['type' => 'number', 'value' => 1]);

            setActiveProject($project);
            return redirect($project->url);

        } catch(Exception $e) {
            Log::error('Error while creating Sandbox for user ' . auth()->id());
            Logg:info($e->getMessage());

            return response('Error', 500);
        }
    }


    public function clone_demo_project($platform) {
        try {

            $_platform = Project::VISIUM_PLATFORM;
            if($platform === 'CosMx') $_platform = Project::COSMX_PLATFORM;

            $_basepath = "common/testprojects/$platform";

            //Check if the user already has a Demo project
            // if(auth()->user()->hasDemoProject()) {
            //     return redirect(auth()->user()->getDemoProject()->url);
            // }

            //Create the Sandbox project for the user
            $project = Project::create(['name' => $platform . ' test project ' . Carbon::now()->format('Y-m-d'), 'description' => 'Test project with preloaded samples to explore spatialGE functionalities', 'project_platform_id' => $_platform, 'user_id' => auth()->id()]);
            $project->current_step = 2;
            $project->save();

            //Load samples and files from disk
            $json = json_decode(Storage::get("$_basepath/samples.json"));
            //Create new samples and files in the DB
            foreach($json as $sampleName => $files) {
                $sample = Sample::create(['name' => $sampleName]);
                $sample->projects()->save($project);
                foreach($files as $fileData) {
                    $fileModel = SampleFile::create(['filename' => $fileData->filename, 'type' => $fileData->type]);
                    $sample->files()->save($fileModel);
                }
            }

            //Insert genes into DB
            $project->createGeneList("$_basepath/genes.csv", 'I');

            //Load parameters from disk
            $json = json_decode(Storage::get("$_basepath/parameters.json"));
            //Create new parameters in the DB
            foreach($json as $parameter) {
                $parameter->value = str_replace('{project_public_url}', $project->workingDirPublicURL(), $parameter->value);
                ProjectParameter::updateOrCreate(['parameter' => $parameter->parameter, 'project_id' => $project->id, 'tag' => $parameter->tag], ['type' => $parameter->type, 'value' => $parameter->value]);
            }

            //Copy sample and other initial files
            $userFolder = auth()->user()->getUserFolder();
            $projectFolder = $userFolder . $project->id . '/';
            Storage::createDirectory($projectFolder);
            File::copyDirectory(Storage::path("$_basepath/samples"), Storage::path($projectFolder));
            File::copy(Storage::path("$_basepath/initial_stlist.RData"), Storage::path("$projectFolder/initial_stlist.RData"));
            File::copy(Storage::path("$_basepath/initial_stlist_summary.csv"), Storage::path("$projectFolder/initial_stlist_summary.csv"));
            if($_platform === Project::COSMX_PLATFORM) File::copy(Storage::path("$_basepath/slide_x_fov_table.csv"), Storage::path("$projectFolder/slide_x_fov_table.csv"));

            //Add parameter to indicate that this is the 'Demo project'
            ProjectParameter::updateOrCreate(['parameter' => 'isDemoProject', 'project_id' => $project->id, 'tag' => ''], ['type' => 'number', 'value' => 1]);

            setActiveProject($project);
            return redirect($project->url);

        } catch(Exception $e) {
            Log::error('Error while creating Sandbox for user ' . auth()->id());
            Logg:info($e->getMessage());

            return response('Error', 500);
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

    public function getSTdiffAnnotations(Project $project) {

        return $project->getSTdiffAnnotations();

    }

    public function getSTdiffAnnotationsBySample(Project $project, $method = 'stclust') {

        return $project->getSTdiffAnnotationsBySample($method);

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

    public function getParametersUsedInJob(Project $project) {

        $data = $project->getParametersUsedInJob(request('command'));

        return response($data, 200, ['Content-Type' => 'text/csv']);
    }

    public function downloadJobFiles(Project $project, $process) {

        $files = ProjectProcessFiles::where('project_id', $project->id)->where('process', $process)->firstOrFail();
        $files = json_decode($files->files);

        $public_folder = $project->workingDirPublic();

        $zip = new \ZipArchive();
        $zipFileName = $process . '.zip';
        Storage::delete($project->workingDirPublic() . $zipFileName);
        $zip->open(Storage::path($public_folder . $zipFileName), \ZipArchive::CREATE);

        foreach($files as $file) {
            if(Storage::fileExists($public_folder . $file)) {
                $zip->addFile(Storage::path($public_folder . $file), $file);
            }
        }

        $logName = '_LOG_' . $process . '.csv';
        Storage::put($public_folder . $logName, $project->getParametersUsedInJob($process));
        $zip->addFile(Storage::path($public_folder . $logName), $logName);

        $zip->close();

        return response()->download(Storage::path($public_folder . $zipFileName), $zipFileName, array('Content-Type: application/octet-stream','Content-Length: '. Storage::size($public_folder . $zipFileName)))/*->deleteFileAfterSend(true)*/;

    }

    public function getJobPositionInQueue(Project $project) {
        $jobId = array_key_exists('job.' . request('command'), $project->project_parameters) ? $project->project_parameters['job.' . request('command')] : 0 ;
        return $jobId ? $project->getJobPositionInQueue($jobId, request('command')) : 0;
    }

    public function cancelJobInQueue(Project $project) {

        $jobId = array_key_exists('job.' . request('command'), $project->project_parameters) ? $project->project_parameters['job.' . request('command')] : 0 ;

        $task = $project->getLatestTask(request('command'));

        if($task) {
            $task->cancelled_at = now();
            $task->save();
        }

        $result = $project->cancelJobInQueue($jobId, $task);

        Log::info('ProjectController- Cancelling task ' . $task->id . ' on ' . $task->cancelled_at . ' - job with id: ' . $jobId);

        return $jobId ? $result : 0;
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

        $parameters = request('parameters');
        unset($parameters['__task']);

        $jobId = $project->createJob('Filter data', 'applyFilter', $parameters);
        return $project->getJobPositionInQueue($jobId);

    }

    public function generateFilterPlots(Project $project) {


        //RunScript::dispatch('Generate filter plots', $project, 'generateFilterPlots', ['color_palette' => request('color_palette'), 'variable' => request('variable')]);

        //$project->generateFilterPlots(['color_palette' => request('color_palette'), 'variable' => request('variable')]);

        $jobId = $project->createJob('Generate filter plots', 'generateFilterPlots', ['color_palette' => request('color_palette'), 'variable' => request('variable'), 'samples' => request('samples')]);
        return $project->getJobPositionInQueue($jobId);
    }

    public function applyNormalization(Project $project) {

        $parameters = request('parameters');

        /*************** EXPERIMENTAL HPC **************/
        if(app()->isLocal() && env('HPC_ENABLED', false)) {
            $parameters['executeIn'] = 'HPC';
        }

        $jobId = $project->createJob('Normalize data', 'applyNormalization', $parameters);

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

    public function sdd_stclust_rename(Project $project) {

        $project->saveSTdiffAnnotationChanges(request('annotations'));

        return 'OK';

        // $jobId = $project->createJob('Spatial Domain Detection - STclust (annotation renaming)', 'STclustRename', request()->all());
        // return $project->getJobPositionInQueue($jobId);
    }

    public function sdd_spagcn(Project $project) {
        $jobId = $project->createJob('Spatial Domain Detection - SpaGCN', 'SpaGCN', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function sdd_spagcn_svg(Project $project) {
        $jobId = $project->createJob('SpaGCN - Spatially variable genes', 'SpaGCN_SVG', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function sdd_spagcn_rename(Project $project) {
        $jobId = $project->createJob('Spatial Domain Detection - SpaGCN (annotation renaming)', 'SpaGCNRename', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function sdd_milwrm(Project $project) {
        $jobId = $project->createJob('Spatial Domain Detection - MILWRM', 'MILWRM', request()->all());
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
        $gene_set = '';
        if(!is_null(request('gene_sets')) && strlen(request('gene_sets')) && request('gene_sets') !== 'upload') {
            $gene_set = request('gene_sets') . '.gmt';
            $gene_sets_file = 'common/stenrich/' . $gene_set;
            Storage::copy($gene_sets_file, $project->workingDir() . $gene_set);
        } else if(request('gene_sets') === 'upload' && request()->hasFile('user_gene_sets')) {
            $gene_set = 'STenrich_user_gene_set.gmt';
            $file = request()->file('user_gene_sets');
            $file->move(Storage::path($project->workingDir()), $gene_set);
        }
        $parameters = request()->except('user_gene_sets');
        $parameters['gene_sets'] = $gene_set;
        $jobId = $project->createJob('Spatial gene set enrichment', 'STEnrich', $parameters);
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

    public function phenotyping(Project $project) {
        $samples = $project->samples;
        $color_palettes = ColorPalette::orderBy('label')->get();
        return view('wizard.phenotyping')->with(compact('project', 'samples', 'color_palettes'));
    }

    public function STdeconvolve(Project $project) {
        $jobId = $project->createJob('Phenotyping - STdeconvolve', 'STdeconvolve', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function STdeconvolve2(Project $project) {
        $jobId = $project->createJob('Phenotyping - STdeconvolve2', 'STdeconvolve2', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function STdeconvolve3(Project $project) {
        $jobId = $project->createJob('Phenotyping - STdeconvolve3', 'STdeconvolve3', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }


    public function InSituType(Project $project) {
        $jobId = $project->createJob('Phenotyping - InSituType', 'InSituType', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function InSituType2(Project $project) {
        $jobId = $project->createJob('Phenotyping - InSituType2', 'InSituType2', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function InSituTypeRename(Project $project) {
        $jobId = $project->createJob('Phenotyping - InSituType (Rename)', 'InSituTypeRename', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }

    public function SPARK_X(Project $project) {
        $samples = $project->samples;
        return view('wizard.sparkx')->with(compact('project', 'samples'));
    }

    public function SPARK(Project $project) {
        $jobId = $project->createJob('SPARK', 'SPARK', request()->all());
        return $project->getJobPositionInQueue($jobId);
    }


}

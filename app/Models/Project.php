<?php

namespace App\Models;

use App\Http\Controllers\spatialContainer;
use App\Jobs\RunScript;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    use SoftDeletes;

    const VISIUM_PLATFORM = 1;
    const GENERIC_PLATFORM = 8;
    const COSMX_PLATFORM = 3;

    protected $table = 'projects';

    protected $fillable = ['name', 'description', 'user_id', 'project_platform_id'];

    protected $appends = ['url', 'assets_url', 'project_parameters', 'platform_name', 'created_on'];

    protected $dates = ['created_at', 'updated_at'];

    private ?spatialContainer $_container = null;

    //Relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function samples(): BelongsToMany
    {
        return $this->belongsToMany(Sample::class);
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(ProjectParameter::class);
    }

    public function isDemoProject() {
        return $this->parameters()->where('parameter', 'isDemoProject')->count();
    }

    /*public function genes(): HasMany
    {
        return $this->hasMany(ProjectGene::class);
    }*/

    public function genes($context = 'I'): BelongsToMany
    {
        return $this->belongsToMany(Gene::class, 'project_gene')->where('context', $context);
    }

    //Attributes
    public function getUrlAttribute()
    {
        return route('open-project', ['project' => $this->id]);
    }

    public function getAssetsUrlAttribute()
    {
        return $this->workingDirPublicURL();
    }

    public function getCreatedOnAttribute()
    {
        return $this->created_at->format('M d, Y');
    }

    public function getPlatformNameAttribute()
    {

        if ($this->project_platform_id === self::VISIUM_PLATFORM)
            return 'VISIUM';
        elseif ($this->project_platform_id === self::GENERIC_PLATFORM)
            return 'GENERIC';
        elseif ($this->project_platform_id === self::COSMX_PLATFORM)
            return 'COSMX';


        return 'UNKNOWN';
    }

    public function isVisiumPlatform() {
        return $this->project_platform_id === self::VISIUM_PLATFORM;
    }

    public function isGenericPlatform() {
        return $this->project_platform_id === self::GENERIC_PLATFORM;
    }

    public function isCosmxPlatform() {
        return $this->project_platform_id === self::COSMX_PLATFORM;
    }

    private function setSTdiffData($data) {

        //sort($data);

        $fileName = $this->workingDir() . 'stdiff_annotation_variables_clusters.csv';

        $file = fopen(Storage::path($fileName), 'w');

        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
    }

    private function getSTdiffData() {

        $data = [];

        $fileName = $this->workingDir() . 'stdiff_annotation_variables_clusters.csv';
        if (Storage::fileExists($fileName)) {

            $_data = Storage::read($fileName);
            $lines = explode("\n", $_data);
            //sort($lines);

            foreach($lines as $line) {
                if(strlen(trim($line))) {
                    $data[] = explode(',', $line);
                }
            }
        }

        return $data;
    }

    public function getSTdiffAnnotations() {

        $params = [];
        $params['annotation_variables'] = [];
        $params['annotation_variables_clusters'] = [];

        $data = $this->getSTdiffData();
        if (count($data)) {

            //$_annotations = array_column($data, 1);
            //$_annotations = array_unique($_annotations);
            // $_annotations = array_values(array_unique($_annotations));

            //Log::info(json_encode($_annotations));

            $tmp_annot = [];

            $annotations = [];
            $annotations_clusters = [];
            foreach ($data as $index_annotation => $_row) {

                $annotation = $_row[1];

                if (strlen(trim($annotation)) && (!in_array($_row[1], $tmp_annot) || !in_array($_row[2], $tmp_annot))) {

                    $label = '';
                    $parts = explode('_', $annotation);

                    if (sizeof($parts) > 2 && $parts[0] === 'stclust') {

                        $label .= 'STclust; ';

                        if (str_starts_with($parts[2], 'k'))
                            $label .= 'Domains (k): ' . str_pad(substr($parts[2], 1), 2, '0', STR_PAD_LEFT) . '; ';

                        if ($parts[1] === 'spw0')
                            $label .= 'No spatial weight';
                        elseif (str_starts_with($parts[1], 'spw'))
                            $label .= 'spatial weight:' . substr($parts[1], 3);

                        if (str_starts_with($parts[2], 'dspl'))
                            $label .= '; DeepSplit=' . substr($parts[2], 4) . '; Automatic mode (DynamicTreeCut)';
                    } elseif (sizeof($parts) >= 2 && $parts[0] === 'spagcn') {
                        $label .= 'SpaGCN; ';
                        if (str_starts_with($parts[1], 'k'))
                            $label .= 'Domains (k): ' . str_pad(substr($parts[1], 1), 2, '0', STR_PAD_LEFT);
                        if (sizeof($parts) > 2 && $parts[2] === 'refined')
                            $label .= '; Refined clusters';
                    }
                    elseif ($parts[0] === 'insitutype') {
                        $label .= 'InSituType';
                    }


                    if(!in_array($_row[1], $tmp_annot)) {
                        array_push($tmp_annot, $_row[1]);



                        /*if ($label === '') {
                        $parts = explode('_', $data[$index_annotation][1]);
                        if(count($parts) > 1) $label = $parts[0] . '; ';
                        $label .= $annotation;
                        }*/

                        $annotations[] = ['label' => $label, 'value' => $annotation];

                        //Obtain clusters for this annotation
                        $_clusters = array_filter($data, function ($row) use($annotation, $_row) {
                            return count($row) > 2 && $row[0] === $_row[0] && $row[1] === $annotation;
                        });
                        $clusters = array_column($_clusters, 3);
                        $clusters = array_values(array_unique($clusters));
                        foreach($clusters as $cluster) {
                            $annotations_clusters[] = ['annotation' => $annotation, 'cluster' => $cluster];
                        }


                    }


                    if(!in_array($_row[2], $tmp_annot)) {

                        $parts = explode('_', $annotation);

                        array_push($tmp_annot, $_row[2]);

                        //If the annotation was manually changed by the user, add a second option to the list
                        if($data[$index_annotation][1] !== $data[$index_annotation][2]) {
                            $annotations[] = ['label' => $label . ' - ' . $_row[2], 'value' => $_row[2]];
                        }


                        //Obtain clusters for this annotation
                        $_clusters = array_filter($data, function ($row) use($annotation, $_row) {
                            return count($row) > 3 && $row[0] === $_row[0] && $row[1] === $annotation;
                        });
                        $clusters = array_column($_clusters, 4);
                        $clusters = array_values(array_unique($clusters));
                        foreach($clusters as $cluster) {
                            $annotations_clusters[] = ['annotation' => $_row[2], 'cluster' => $cluster];
                        }

                    }



                }
            }
            sort($annotations);
            $params['annotation_variables'] = $annotations;
            $params['annotation_variables_clusters'] = $annotations_clusters;
        }

        return $params;

    }

    public function getSTdiffAnnotationsBySample($sddMethod = 'stclust') {

        $data = $this->getSTdiffData();

        if(!count($data)) return [];

        $result = [];
        foreach($this->samples as $sample) {
            $sampleName = $sample->name;
            $result[$sampleName] = [];

            //filter the annotations based on the method/module (stclust, insitutype)
            $annotationData = array_filter($data, function($annot) use($sampleName, $sddMethod) {
                return $annot[0] === $sampleName && str_starts_with($annot[1], $sddMethod);
            });

            $annotations = array_unique(array_column($annotationData, 1));
            foreach($annotations as $annotation) {

                $annotationName = '';
                $_clusters = array_filter($annotationData, function($annot) use($annotation, &$annotationName) {
                    if($annot[1] === $annotation) $annotationName = $annot[2];
                    return $annot[1] === $annotation;
                });
                $clusters = array_map(function ($cluster) {
                    return ['originalName' => $cluster[3], 'modifiedName' => $cluster[4]];
                }, $_clusters);
                $clusters = array_values($clusters);

                $result[$sampleName][$annotation] = ['originalName' => $annotation, 'modifiedName' => $annotationName, 'clusters' => $clusters];
            }
        }

        return $result;

    }

    public function getProjectParametersAttribute()
    {
        $params = [];
        foreach ($this->parameters as $param)
            $params[$param->parameter] = $param->type === 'number' ? intval($param->value) : $param->value;

        $params['total_genes'] = $this->genes()->count();

        if (array_key_exists('metadata', $params)) {
            $names = [];
            foreach (json_decode($params['metadata']) as $meta)
                $names[] = ['label' => $meta->name, 'value' => $meta->name];
            $params['metadata_names'] = $names;
        }


        if (array_key_exists('STdeconvolve', $params)) {
            $STdeconvolve = json_decode($params['STdeconvolve']);
            if (isset($STdeconvolve->suggested_k)) {
                $STdeconvolve->selected_k = $STdeconvolve->suggested_k; #assume the defaults

                $fileName = $this->workingDir() . 'stdeconvolve_selected_k.csv';
                if (Storage::fileExists($fileName)) {
                    $data = Storage::read($fileName);
                    $lines = explode("\n", $data);
                    $selected_k = [];
                    foreach ($lines as $line) {
                        if (strlen(trim($line))) {
                            $values = explode(',', $line);
                            $selected_k[$values[0]] = $values[1];
                        }
                    }
                    array_shift($selected_k);
                    $STdeconvolve->selected_k = $selected_k;
                }
            }

            $params['STdeconvolve'] = json_encode($STdeconvolve);
        }



        $params['downloadable'] = ProjectProcessFiles::where('project_id', $this->id)->select('process')->distinct()->get()->pluck(['process']);

        return $params;
    }

    public function getTasks($process) {
        return Task::where('project_id', $this->id)->where('process', $process)->orderByDesc('scheduled_at')->get();
    }

    public function getLatestTask($process) {
        $tasks = $this->getTasks($process);
        return $tasks->count() ? $tasks[0] : null;
    }


    public function getParametersUsedInJob($jobName) {

        $tasks = $this->getTasks($jobName);

        if(!$tasks->count()) return '';



        $dict = Storage::get('common/parameters_dictionary.json');
        $dict = json_decode($dict);
        $CSV = "";
        if(property_exists($dict, $jobName)) {

            $columnNames = ['Date', 'Parameter', 'Value', 'Description'];
            $CSV = implode(',', $columnNames) . "\n";

            foreach($tasks as $task) {

                $values = json_decode($task->payload);

                // Log::info($jobName);
                // Log::info(json_encode($values));

                if($values !== null && property_exists($values, 'parameters')) {

                    foreach(get_object_vars($dict->$jobName) as $attr => $value) {

                        $CSV .= $task->scheduled_at . ','; //Date
                        $CSV .= $attr . ','; //'Parameter name'

                        $tmp = $values->parameters->$attr;
                        if(is_array($tmp)) {
                            $tmp = '[' . implode('; ', $tmp) . ']' ;
                        }
                        if(is_object($tmp)) {
                            $tmp_str = '';
                            foreach(get_object_vars($tmp) as $key => $val) {
                                if($tmp_str !== '') { $tmp_str .= ';'; }
                                $tmp_str .= $key . ': ' . $val;
                            }
                            $tmp = '[' . $tmp_str . ']';
                        }

                        $CSV .= $tmp . ','; //Value
                        $CSV .= $value; //Parameter description

                        $CSV .= "\n";
                    }
                }
                $CSV .= "\n";
            }
        }











        /*$output = [];
        foreach($tasks as $task) {
            $data = json_decode($task->payload);
            unset($data->parameters->__task);
            $output[] = ['date' => $task->scheduled_at, 'parameters' => $data->parameters];
        }*/

        /*$dict = Storage::get('common/parameters_dictionary.json');
        $dict = json_decode($dict);
        $CSV = "";
        if(property_exists($dict, $jobName)) {
            $columnNames = implode(', ', get_object_vars($dict->$jobName));
            $CSV = 'date, ' . $columnNames . "\n";
            foreach($tasks as $task) {
                $CSV .= $task->scheduled_at;
                $values = json_decode($task->payload);
                foreach(get_object_vars($dict->$jobName) as $attr => $value) {
                    $tmp = $values->parameters->$attr;
                    if(is_array($tmp)) {
                        $tmp = '[' . implode('; ', $tmp) . ']' ;
                    }
                    if(is_object($tmp)) {
                        $tmp_str = '';
                        foreach(get_object_vars($tmp) as $key => $val) {
                            if($tmp_str !== '') { $tmp_str .= ';'; }
                            $tmp_str .= $key . ': ' . $val;
                        }
                        $tmp = '[' . $tmp_str . ']';
                    }
                    $CSV .= ', ' . $tmp;
                }
                $CSV .= "\n";
            }
        }*/

        return $CSV;
    }




    public function getCurrentStepUrl()
    {
        if ($this->current_step === 1)
            return route('import-data', ['project' => $this->id]);

        if ($this->current_step === 2)
            return route('qc-data-transformation', ['project' => $this->id]);

        if ($this->current_step >= 3)
            return route('stplot-visualization', ['project' => $this->id]);


        return '/';
    }

    public function workingDir(): string
    {
        return '/users/' . $this->user_id . '/' . $this->id . '/';
    }

    public function workingDirHPC(): string
    {
        return env('HPC_FOLDER') . $this->id . '/';
    }

    public function workingDirPublic(): string
    {
        Storage::createDirectory('/public/users');
        Storage::createDirectory('/public/users/' . $this->user_id);
        Storage::createDirectory('/public/users/' . $this->user_id . '/' . $this->id);
        $workingDir = '/public/users/' . $this->user_id . '/' . $this->id . '/';
        #$workingDir = str_replace('\\', '/', $workingDir);
        return $workingDir;
    }

    public function workingDirPublicURL(): string
    {
        $workingDir = '/storage/users/' . $this->user_id . '/' . $this->id . '/';
        #$workingDir = str_replace('\\', '/', $workingDir);
        return $workingDir;
    }

    public function spatialExecute($command, $task_id, $container = 'SPATIALGE')
    {

        if (is_null($this->_container))
            $this->_container = new spatialContainer($this);

        $task = Task::where('task', $task_id)->firstOrFail();
        $task->started_at = DB::raw('CURRENT_TIMESTAMP');
        $task->save();

        $output = $this->_container->execute($command, $task_id, $container);

        //Update the output in the Task table
        $task->output = (($task->attempts > 1 || strlen($task->output)) ? $task->output . "\n\nATTEMPT $task->attempts:\n" : '') . $output;

        //Check output for possible strings that indicate an error during execution
        $error_strings_to_look_for = [
            'Execution halted',
            'Cannot allocate memory',
            'Error in',
            'Killed'
        ];
        $error_found = false;
        foreach ($error_strings_to_look_for as $item) {
            if (strpos(strtolower($task->output), strtolower($item))) {
                $error_found = true;
                break;
            }
        }

        $task->finished_at = DB::raw('CURRENT_TIMESTAMP');
        $task->completed = !strpos($task->output, 'spatialGE_PROCESS_COMPLETED') || $error_found ? 0 : 1;
        $task->save();

        return $output;
    }


    private function _saveStList($stlist)
    {

        $persistOn = env('PERSIST_DATA_ON', 'DISK');

        $command = '';
        if ($persistOn === 'DISK')
            $command = "save($stlist, file='$stlist.RData')";
        elseif ($persistOn === 'REDIS')
            $command = "
            r <- redux::hiredis()
            r\$SET('$stlist', redux::object_to_bin($stlist))
            #r\$HSET('spatialGE', '$stlist', serialize($stlist, NULL))
            ";

        return $command;
    }

    private function _loadStList($stlist)
    {

        $persistOn = env('PERSIST_DATA_ON', 'DISK');

        $command = '';
        if ($persistOn === 'DISK')
            $command = "load(file='$stlist.RData')";
        elseif ($persistOn === 'REDIS')
            $command = "
            r <- redux::hiredis()
            $stlist = redux::bin_to_object(r\$GET('$stlist'))
            ";

        return $command;
    }

    private function pca_max_var_genes($HPC = 0)
    {
        if ($HPC) {
            $file = $this->workingDirHPC() . 'pca_max_var_genes.csv';
        } else {
            $file = Storage::path($this->workingDir()) . 'pca_max_var_genes.csv';
        }

        //if(Storage::fileExists($file)) {
        if (file_exists($file)) {
            //$data = trim(Storage::read($file));
            $data = trim(file_get_contents($file));
            ProjectParameter::updateOrCreate(['parameter' => 'pca_max_var_genes', 'project_id' => $this->id, 'tag' => 'import'], ['type' => 'number', 'value' => $data]);
            return intval($data);
        }
        return 0;
    }

    private function filter_meta_options()
    {
        $file = $this->workingDir() . 'filter_meta_options.csv';
        $json = json_encode([]);
        if (Storage::fileExists($file)) {
            $data = Storage::read($file);
            $options = explode("\n", $data);
            $_options = [];
            foreach ($options as $option)
                if (strlen($option))
                    $_options[] = ['label' => $option, 'value' => $option];
            $json = json_encode($_options);
            ProjectParameter::updateOrCreate(['parameter' => 'filter_meta_options', 'project_id' => $this->id, 'tag' => 'import'], ['type' => 'json', 'value' => $json]);
        }

        return $json;
    }

    public function createGeneList($genes_file, $context)
    {
        if(Storage::fileExists($genes_file)) {
        //if (file_exists($genes_file)) {

            /***************** COPIAR EL ARCHIVO LOCAL o utilizar File:: *******/
            //$data = file_get_contents($genes_file); //FALLA AL LEER EL ARCHIVO

            /*$fileName = Storage::path($this->workingDir() . 'genesNormalized.csv');

            //copy($genes_file, $fileName);

            $command = 'copy ' . $genes_file . ' ' . $fileName;
            $command = str_replace('/', '\\', $command);
            Log::info('=================>  ' . $command);
            $process = Process::run($command);

            $data = file_get_contents($fileName);*/

            $data = Storage::get($genes_file);


            $genes = explode("\n", $data);
            $genes = array_unique($genes);

            $__genes = [];
            foreach ($genes as $gene) {
                if (strlen($gene)) {
                    $__genes[] = ['name' => $gene];
                }
            }

            //Insert whatever new genes have been detected applying this process in the table that contains all genes
            DB::table('genes')->insertOrIgnore($__genes);
            //Delete previously stored genes for this project
            DB::delete("delete from project_gene where context='$context' and project_id=" . $this->id);
            //Associate the genes in this project with the general gene list
            DB::insert("INSERT INTO project_gene(context, project_id, gene_id) select '$context',{$this->id}, id FROM genes WHERE name IN('" . implode("','", $genes) . "')");
        }
    }

    public function createStList($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'STList.R';
        $script = $workingDir . $scriptName;

        $scriptContents = $this->getStListScript();
        Storage::put($script, $scriptContents);

        //delete all existing STlists
        foreach (Storage::files($workingDir) as $file) {
            if (stripos($file, '.rdata'))
                Storage::delete($file);
        }

        //Create the initial_stlist
        $output = $this->spatialExecute("Rscript $scriptName", $parameters['__task']);

        $task = Task::where('task', $parameters['__task'])->firstOrFail();
        if ($task->completed !== 1) {
            return ['output' => $output, 'script' => ''];
        }


        //Load genes present in samples into the DB
        $genes_file = $workingDir . 'genes.csv';
        $this->createGeneList($genes_file, 'I');

        //Delete previously generated parameters, if any
        DB::delete("delete from project_parameters where parameter<>'metadata' and not(parameter like 'job.createStList%') and project_id=" . $this->id);

        $this->pca_max_var_genes();

        //Load other parameters generated by the R script
        $file = $workingDir . 'max_spot_counts.csv';
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::updateOrCreate(['parameter' => 'max_spot_counts', 'project_id' => $this->id, 'tag' => 'import'], ['type' => 'number', 'value' => $data]);
        }
        $file = $workingDir . 'max_gene_counts.csv';
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::updateOrCreate(['parameter' => 'max_gene_counts', 'project_id' => $this->id, 'tag' => 'import'], ['type' => 'number', 'value' => $data]);
        }
        $file = $workingDir . 'max_spots_number.csv';
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::updateOrCreate(['parameter' => 'max_spots_number', 'project_id' => $this->id, 'tag' => 'import'], ['type' => 'number', 'value' => $data]);
        }

        $file = $workingDir . 'initial_stlist_summary.csv';
        $file_public = $this->workingDirPublic() . 'initial_stlist_summary.csv';
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::updateOrCreate(['parameter' => 'initial_stlist_summary', 'project_id' => $this->id, 'tag' => 'import'], ['type' => 'string', 'value' => $data]);
            Storage::copy($file, $file_public);
            ProjectParameter::updateOrCreate(['parameter' => 'initial_stlist_summary_url', 'project_id' => $this->id, 'tag' => 'import'], ['type' => 'string', 'value' => $this->workingDirPublicURL() . 'initial_stlist_summary.csv']);


            if($this->isCosmxPlatform()) {
                //Delete initial samples containing multiple FOVs
                foreach($this->samples as $sample) {
                    $sample->delete();
                }

                //Insert each detected FOV as a new sample
                $fovs = explode("\n", $data);
                for($i = 1; $i < count($fovs); $i++) {
                    $fields = explode(',', $fovs[$i]);
                    $sample = Sample::create(['name' => $fields[0]]);
                    $sample->projects()->save($this);
                }
            }


        }

        $this->filter_meta_options();

        //Data imported for this project, proceed to step 2 of the wizard
        $this->current_step = 2;
        $this->save();


        //Move tissue images to its respective sample folder
        foreach ($this->samples as $sample) {
            $tissue_image = $workingDir . 'image_' . $sample->name . '.png';
            $tissue_image_destination = $workingDir . '/' . $sample->name . '/spatial/image_' . $sample->name . '.png';
            if (Storage::fileExists($tissue_image))
                Storage::move($tissue_image, $tissue_image_destination);
        }


        return ['output' => $output, 'script' => $scriptContents];
    }

    public function getStListScript(): string
    {

        $params = $this->getProjectParametersAttribute();
        $sampleNames = array_key_exists('metadata_names', $params) ? sizeof($params['metadata_names']) : 0;
        $sampleNames = $sampleNames ? "'clinical_data.csv'" : "c('" . $this->samples()->pluck('samples.name')->join("','") . "')";

        $countFiles = "''";
        $coordinateFiles = "''";
        $createSTlistCommand = '';
        $loadImagesCommand = '';

        $cosMxFovList = '';

        $expressionFileExtension = $this->samples[0]->expression_file->extension;

        if ($expressionFileExtension === 'h5') {

            $countFiles = $this->samples()->pluck('samples.name')->join("/','");
            $countFiles = "'" . $countFiles . "/'";

            $createSTlistCommand = 'initial_stlist <- STlist(rnacounts=count_files, samples=samplenames)';
        }
        else if (in_array($expressionFileExtension, ['csv', 'txt', 'tsv'/*, 'zip'*/]) && ($this->isGenericPlatform() || $this->isCosmxPlatform())) {

            $countFiles = [];
            $coordinateFiles = [];
            $imageFiles = [];
            foreach ($this->samples as $sample) {
                $countFiles[] = $sample->name . '/' . $sample->expression_file->filename;
                $coordinateFiles[] = $sample->name . '/spatial/' . $sample->coordinates_file->filename;
                if ($sample->has_image) {
                    $imageFiles[] = $sample->name . '/spatial/' . $sample->image_file->filename;
                }
            }

            $countFiles = "'" . join("','", $countFiles) . "'";
            $coordinateFiles = "'" . join("','", $coordinateFiles) . "'";
            $createSTlistCommand = 'initial_stlist <- STlist(rnacounts=count_files, samples=samplenames, spotcoords=coords_files, cores=1)';

            if (count($imageFiles)) {
                $loadImagesCommand = "

                image_files = c('" . join("','", $imageFiles) . "')
                initial_stlist = load_images(initial_stlist, image_files)

                ";
            }

            if($this->isCosmxPlatform()) {
                $cosMxFovList = "
                # Slide x FOV table
                df_tmp = lapply(names(initial_stlist@counts), function(i){
                  values = tibble::tibble(fov_name=i, slide=gsub('_fov_[0-9]+$', '', i), fov_id=stringr::str_extract(i, 'fov_[0-9]+$'))
                  return(values)
                })
                write.csv(do.call(rbind, df_tmp), 'slide_x_fov_table.csv', quote=F, row.names=F)";
            }
        }

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Specify paths to files/directories containing counts data
count_files = c($countFiles)

# Specify paths to files containing coordinates data
coords_files = c($coordinateFiles)

# Specify sample names
samplenames = $sampleNames


# Create STlist
$createSTlistCommand
$loadImagesCommand

#Save the STList
{$this->_saveStList("initial_stlist")}

$cosMxFovList

#max_var_genes PCA
pca_max_var_genes = min(unlist(lapply(initial_stlist@counts, nrow)))
write.table(pca_max_var_genes, 'pca_max_var_genes.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

#Obtain gene names in samples and save it in a text file
gene_names = unique(unlist(lapply(initial_stlist@counts, function(i){ genes_tmp = rownames(i) })))
write.table(gene_names, 'genes.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Maximum number of counts per spot
max_spot_counts = max(unlist(lapply(initial_stlist@counts, function(i){  max_tmp = max(Matrix::colSums(i)) })))
write.table(max_spot_counts, 'max_spot_counts.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Maximum number of counts per gene
max_gene_counts = max(unlist(lapply(initial_stlist@counts, function(i){ max_tmp = max(Matrix::rowSums(i)) })))
write.table(max_gene_counts, 'max_gene_counts.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Maximum number of spots
max_spots_number = max(unlist(lapply(initial_stlist@counts, function(i){ max_tmp = max(ncol(i)) })))
write.table(max_spots_number, 'max_spots_number.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

#source('summary.R')
df_summary = summarize_STlist(initial_stlist)
write.csv(df_summary, 'initial_stlist_summary.csv', row.names=FALSE, quote=FALSE)

# Options for quilt plot
filter_meta_options = unique(unlist(lapply(initial_stlist@spatial_meta, function(i){ max_tmp = grep(paste0(c('libname', 'xpos', 'ypos'), collapse='|'), colnames(i), value=T, invert=T) })))
write.table(filter_meta_options, 'filter_meta_options.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Prepare images
tissues = plot_image(initial_stlist)
# Save images to PNG files
lapply(names(tissues), function(i){
  png(paste0(i, '.png'))
  print(tissues[[i]])
  dev.off()
})

";

        return $script;
    }


    public function applyFilter($parameters)
    {

        $workingDir = $this->workingDir();
        $workingDirPublic = $this->workingDirPublic();

        $scriptName = 'Filter.R';
        $script = $workingDir . $scriptName;

        $scriptContents = $this->getFilterDataScript($parameters);
        Storage::put($script, $scriptContents);

        //delete all existing STlists except: initial_stlist
        foreach (Storage::files($workingDir) as $file) {
            if (stripos($file, '.rdata') && !stripos($file, 'initial_stlist.rdata'))
                Storage::delete($file);
        }

        //Delete previously generated parameters, if any
        DB::delete("delete from project_parameters where tag not in ('import','') and not(parameter like 'job.%') and project_id=" . $this->id);
        //DB::delete("delete from project_parameters where parameter<>'metadata' and not(parameter like 'job.%') and project_id=" . $this->id);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $result = [];


        $result['filter_meta_options'] = $this->filter_meta_options();


        $result['pca_max_var_genes'] = $this->pca_max_var_genes();

        $parameterNames = ['filter_violin', 'filter_boxplot'];
        foreach ($parameterNames as $parameterName) {

            $file_extensions = ['svg', 'pdf', 'png'];

            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $workingDirPublic . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id, 'tag' => 'filter'], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                    $result[$parameterName] = $this->workingDirPublicURL() . $parameterName;

                    if($file_extension === 'pdf') {
                        $_process_files[] = $fileName;
                    }

                }
            }
        }

        $parameterName = 'filtered_stlist_summary';
        $file = $workingDir . $parameterName . '.csv';
        $file_public = $workingDirPublic . $parameterName . '.csv';
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id, 'tag' => 'filter'], ['type' => 'string', 'value' => $data]);
            $result[$parameterName] = $data;

            Storage::delete($file_public);
            Storage::move($file, $file_public);

            $_process_files[] = $parameterName . '.csv';
        }

        ProjectParameter::updateOrCreate(['parameter' => 'applyFilter', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters])]);

        ProjectProcessFiles::updateOrCreate(['process' => 'applyFilter', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        $result['output'] = $output;
        $result['script'] = $scriptContents;

        return $result;
    }


    public function getFilterDataScript($parameters): string
    {

        $str_params = '';
        foreach ($parameters as $key => $value) {
            if (!is_array($value) && strlen($value) && $key !== '__task') {
                $str_params .= strlen($str_params) ? ', ' : '';
                $quote = in_array($key, ['rm_genes_expr', 'spot_pct_expr']) ? "'" : '';
                $str_params .= $key . '=' . $quote . $value . $quote;
            }
        }

        $samples = $this->getSampleList($parameters['samples']);
        $str_params .= ', samples=' . $samples;

        $plots = $this->getExportFilesCommands('filter_violin', 'vp');
        $plots .= $this->getExportFilesCommands('filter_boxplot', 'bp');

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load STList
" .
            $this->_loadStList('initial_stlist')
            . "

# Apply defined filter to the initial STList
filtered_stlist = filter_data(initial_stlist, $str_params)
" .
            $this->_saveStList('filtered_stlist')
            .
            "

#max_var_genes PCA
pca_max_var_genes = min(unlist(lapply(filtered_stlist@counts, nrow)))
write.table(pca_max_var_genes, 'pca_max_var_genes.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)


#### Plots Filter Data
# Options for plot
filter_meta_options = unique(unlist(lapply(filtered_stlist@spatial_meta, function(i){ max_tmp = grep(paste0(c('libname', 'xpos', 'ypos'), collapse='|'), colnames(i), value=T, invert=T) })))
write.table(filter_meta_options, 'filter_meta_options.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)


gene_names = unique(unlist(lapply(filtered_stlist@counts, function(i){ genes_tmp = rownames(i) })))
write.table(gene_names, 'genesFiltered.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

#source('summary.R')
df_summary = summarize_STlist(filtered_stlist)
write.csv(df_summary, 'filtered_stlist_summary.csv', row.names=FALSE, quote=FALSE)

#### Violin plot
#library('magrittr')
#source('violin_plots.R')
#source('utils.R')
vp = distribution_plots(filtered_stlist, samples=$samples, plot_meta='total_counts', color_pal='" . ($this->samples->count() < 12 ? "Spectral" : "smoothrainbow") . "')
#ggpubr::ggexport(filename = 'filter_violin.png', vp, width = 800, height = 800)

#### Box plot
bp = distribution_plots(filtered_stlist, samples=$samples, plot_meta='total_counts', color_pal='" . ($this->samples->count() < 12 ? "Spectral" : "smoothrainbow") . "', plot_type='box')
#ggpubr::ggexport(filename = 'filter_boxplot.png', bp, width = 800, height = 800)

#### Save plots to file

$plots

";

        //dd($script);

        return $script;
    }





    public function generateFilterPlots($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'generateFilterPlots.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getFilterPlotsScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $parameterNames = ['filter_violin', 'filter_boxplot'];
        foreach ($parameterNames as $parameterName) {

            $file_extensions = ['svg', 'pdf', 'png'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id, 'tag' => 'filter'], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);

                    if($file_extension === 'pdf') {
                        $_process_files[] = $fileName;
                    }

                }
            }
        }

        ProjectProcessFiles::updateOrCreate(['process' => 'generateFilterPlots', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
    }


    public function getFilterPlotsScript($parameters): string
    {

        $color_palette = $parameters['color_palette'];
        $variable = $parameters['variable'];


        $samples = $this->getSampleList($parameters['samples']);

        /*$samples = $parameters['samples'];
        if(is_array($samples) && count($samples)) {
            $samples = "c('" . join("','", $samples) . "')";
        }
        else {
            $samples = "c('" . $this->samples()->pluck('samples.name')->join("','") . "')";
        }*/


        $plots = $this->getExportFilesCommands('filter_violin', 'vp');
        $plots .= $this->getExportFilesCommands('filter_boxplot', 'bp');

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load filtered STList
" .
            $this->_loadStList('filtered_stlist')
            . "

#### Violin plot
#library('magrittr')
#source('violin_plots.R')
#source('utils.R')
vp = distribution_plots(filtered_stlist, samples=$samples, plot_meta='$variable', color_pal='$color_palette')
#ggpubr::ggexport(filename = 'filter_violin.png', vp, width = 800, height = 800)

#### Box plot
bp = distribution_plots(filtered_stlist, samples=$samples, plot_meta='$variable', color_pal='$color_palette', plot_type='box')
#ggpubr::ggexport(filename = 'filter_boxplot.png', bp, width = 800, height = 800)

#### save plots to file
$plots

";

        return $script;
    }


    public function applyNormalization($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'Normalization';
        $scriptFilename = 'Normalization.R';

        $script = $workingDir . $scriptFilename;

        $scriptContents = $this->getNormalizationScript($parameters);
        Storage::put($script, $scriptContents);

        //Delete project parameters that need to be recreated
        DB::delete("delete from project_parameters where tag not in ('import', 'filter','') and not(parameter like 'job.%') and project_id=" . $this->id);

        //delete all existing STlists except: initial_stlist, filtered_stlist
        foreach (Storage::files($workingDir) as $file) {
            if (stripos($file, '.rdata') && !stripos($file, 'initial_stlist.rdata') && !stripos($file, 'filtered_stlist.rdata'))
                Storage::delete($file);
        }


        if (array_key_exists('executeIn', $parameters) && $parameters['executeIn'] === 'HPC') {
            $this->sendJobHPC($parameters['__task'], $scriptName, ['filtered_stlist.RData', 'initial_stlist.RData']);

            $task = Task::where('task', $parameters['__task'])->firstOrFail();
            $task->started_at = DB::raw('CURRENT_TIMESTAMP');
            $task->save();
        } else {
            $output = $this->spatialExecute('Rscript ' . $scriptFilename, $parameters['__task']);
            $this->applyNormalizationCompleted();
        }
    }

    public function applyNormalizationCompleted($HPC = 0)
    {


        if ($HPC) {
            $workingDir = $this->workingDirHPC();
        } else {
            // $workingDir = Storage::path($this->workingDir());
            $workingDir = $this->workingDir();
        }

        $_process_files = [];

        //Load genes present in the normalized STlist into the DB
        $genes_file = $workingDir . 'genesNormalized.csv';
        $this->createGeneList($genes_file, 'N');

        $result = [];

        $parameterNames = ['normalized_boxplot_1', 'normalized_boxplot_2', 'normalized_violin_1', 'normalized_violin_2', 'normalized_density_1', 'normalized_density_2'];
        foreach ($parameterNames as $parameterName) {

            $file_extensions = ['svg', 'pdf', 'png'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (($HPC && file_exists($file)) || Storage::fileExists($file)) {

                    //Delete, if exists, any previously generated file in the public folder
                    if (Storage::fileExists($file_public)) { Storage::delete($file_public); }

                    if($HPC) {
                        $file_public = Storage::path($file_public);

                        //if (file_exists($file_public)) unlink($file_public);

                        //copy($file, $file_public);
                        $command = 'copy ' . $file . ' ' . $file_public;
                        $command = str_replace('/', '\\', $command); //TODO: works only on Windows
                        Log::info('=================>  ' . $command);
                        $process = Process::run($command);
                    } else {
                        Storage::move($file, $file_public);
                    }

                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id, 'tag' => 'normalize'], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                    $result[$parameterName] = $this->workingDirPublicURL() . $parameterName;

                    if($file_extension === 'pdf') {
                        $_process_files[] = $fileName;
                    }

                }
            }
        }

        $result['pca_max_var_genes'] = $this->pca_max_var_genes();

        //Delete (if any) previously generated normalized data, the user has to generate it again from the interface
        ProjectParameter::where('parameter', 'normalizedData')->where('project_id', $this->id)->delete();

        ProjectProcessFiles::updateOrCreate(['process' => 'applyNormalization', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);


        $this->current_step = 6;
        $this->save();

        $result['output'] = ''; // $output;
        $result['script'] = ''; // $scriptContents;
        return $result;
    }


    public function getNormalizationScript($parameters): string
    {

        //If there's no filtered stlist use the initial stlist
        $stlist = 'filtered_stlist';
        if (!Storage::fileExists($this->workingDir() . "$stlist.RData")) $stlist = 'initial_stlist';

        $samples = $this->getSampleList(NULL);

        $str_params = '';
        foreach ($parameters as $key => $value) {
            if (strlen($value) && $key !== '__task' && $key !== 'executeIn') {
                $str_params .= strlen($str_params) ? ', ' : '';
                $quote = in_array($key, ['method']) ? "'" : '';
                $str_params .= $key . '=' . $quote . $value . $quote;
            }
        }

        //$plots = $this->getExportFilesCommands('normalized_violin', 'vp');
        //$plots .= $this->getExportFilesCommands('normalized_boxplot', 'bp');
        $plots = $this->getExportFilesCommands('normalized_boxplot_1', "den_raw\$boxplot");
        $plots .= $this->getExportFilesCommands('normalized_boxplot_2', "den_tr\$boxplot");
        $plots .= $this->getExportFilesCommands('normalized_density_1', "den_raw\$density");
        $plots .= $this->getExportFilesCommands('normalized_density_2', "den_tr\$density");
        $plots .= $this->getExportFilesCommands('normalized_violin_1', "den_raw\$violin");
        $plots .= $this->getExportFilesCommands('normalized_violin_2', "den_tr\$violin");

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load filtered STList

{$this->_loadStList($stlist)}

normalized_stlist = transform_data($stlist, $str_params, cores = 4)

{$this->_saveStList('normalized_stlist')}

gene_names = unique(unlist(lapply(normalized_stlist@counts, function(i){ genes_tmp = rownames(i) })))
write.table(gene_names, 'genesNormalized.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

#max_var_genes PCA
pca_max_var_genes = min(unlist(lapply(normalized_stlist@counts, nrow)))
write.table(pca_max_var_genes, 'pca_max_var_genes.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

#### Violin & Box plots
den_raw = plot_counts(normalized_stlist, distrib_subset=0.01, data_type='raw', plot_type=c('density', 'violin', 'box'), samples=$samples)
den_tr = plot_counts(normalized_stlist, distrib_subset=0.01, plot_type=c('density', 'violin', 'box'), samples=$samples)

$plots

";

        return $script;
    }


    public function generateNormalizationPlots($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'generateNormalizedPlots.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getNormalizedPlotsScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $parameterNames = ['normalized_violin', 'normalized_boxplot'];
        foreach ($parameterNames as $parameterName) {
            $file_extensions = ['svg', 'pdf', 'png'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id, 'tag' => 'normalize'], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);

                    if($file_extension === 'pdf') {
                        $_process_files[] = $fileName;
                    }
                }
            }
        }

        ProjectProcessFiles::updateOrCreate(['process' => 'generateNormalizationPlots', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
    }


    public function getNormalizedPlotsScript($parameters): string
    {

        //If there's no normalizes stlist use the initial stlist
        $stlist = 'normalized_stlist';
        if (!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'filtered_stlist';
        if (!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'initial_stlist';

        $color_palette = $parameters['color_palette'];
        $gene = $parameters['gene'];

        $plots = $this->getExportFilesCommands('normalized_violin', 'vp');
        $plots .= $this->getExportFilesCommands('normalized_boxplot', 'bp');

        $samples = $this->getSampleList(NULL);

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList

{$this->_loadStList($stlist)}

#### Violin plot
vp = distribution_plots($stlist, color_pal='$color_palette', data_type='tr', genes='$gene', samples=$samples)
#### Box plot
bp = distribution_plots($stlist, color_pal='$color_palette', plot_type='box', data_type='tr', genes='$gene', samples=$samples)

$plots

";

        return $script;
    }


    public function generateNormalizationData($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'generateNormalizedData.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getNormalizedDataScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $parameterNames = ['normalizedData'];
        foreach ($parameterNames as $parameterName) {
            $file_extensions = ['xlsx'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id, 'tag' => 'normalize'], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                }
            }
        }

        return ['output' => $output, 'script' => $scriptContents];
    }

    public function getNormalizedDataScript(): string
    {

        //If there's no normalizes stlist use the initial stlist
        $stlist = 'normalized_stlist';

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')
library('magrittr')

# Load normalized STList
{$this->_loadStList($stlist)}

## Extract spot/cell normalized data for all samples
norm_data = lapply($stlist@tr_counts, function(i){
  df_tmp = as.data.frame(as.matrix(i)) %>%
    tibble::rownames_to_column('gene')

  return(df_tmp)
})
openxlsx::write.xlsx(norm_data, 'normalizedData.xlsx')
";

        return $script;
    }


    public function applyPca($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'generatePca.R';

        $script = $workingDir . $scriptName;

        $scriptContents  = $this->getPcaScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $result = [];

        //Delete any previously generated plots
        ProjectParameter::where('project_id', $this->id)->whereIn('parameter', ['pseudo_bulk_pca', 'pseudo_bulk_heatmap'])->delete();

        //To indicate that the PCA has been calculated
        ProjectParameter::updateOrCreate(['parameter' => 'qc_pca', 'project_id' => $this->id, 'tag' => 'pseudo_bulk_pca'], ['type' => 'number', 'value' => 1]);

        $result['output'] = $output;
        $result['script'] = $scriptContents;
        return $result;
    }



    public function getPcaScript($parameters): string
    {

        //If there's no normalizes stlist use the initial stlist
        $stlist = 'normalized_stlist';
        if (!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'filtered_stlist';
        if (!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'initial_stlist';

        $n_genes = $parameters['n_genes'];

        $script = "
setwd('/spatialGE')
# Load the package
library('svglite')
library('spatialGE')

# Load normalized STList
{$this->_loadStList($stlist)}

pca_stlist = pseudobulk_samples($stlist, max_var_genes=$n_genes)

#Save stlist to generate PCA Plots
{$this->_saveStList('pca_stlist')}

";

        return $script;
    }



    public function pcaPlots($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'generatePcaPlots.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getPcaPlotsScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $result = [];

        $parameterNames = ['pseudo_bulk_pca', 'pseudo_bulk_heatmap'];
        foreach ($parameterNames as $parameterName) {

            $file_extensions = ['svg', 'pdf', 'png'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id, 'tag' => 'pseudo_bulk_pca'], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                    $result[$parameterName] = $this->workingDirPublicURL() . $parameterName;

                    if($file_extension === 'pdf') {
                        $_process_files[] = $fileName;
                    }
                }
            }
        }

        ProjectProcessFiles::updateOrCreate(['process' => 'pcaPlots', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        $result['output'] = $output;
        $result['script'] = $scriptContents;
        return $result;
    }


    public function getPcaPlotsScript($parameters): string
    {

        //If there's no normalizes stlist use the initial stlist
        $stlist = 'pca_stlist';

        $plot_meta = $parameters['plot_meta'];
        $plot_meta = strlen($plot_meta) ? ", plot_meta='$plot_meta'" : '';

        $color_pal = $parameters['color_pal'];
        $hm_display_genes = $parameters['hm_display_genes'];

        $plots = $this->getExportFilesCommands('pseudo_bulk_pca', "pca_p");
        $plots .= $this->getExportFilesCommands('pseudo_bulk_heatmap', "hm_p");

        $script = "
setwd('/spatialGE')
# Load the package
library('svglite')
library('spatialGE')

# Load PCA STList
{$this->_loadStList($stlist)}

pca_p = pseudobulk_pca_plot(pca_stlist $plot_meta, color_pal='$color_pal', ptsize=5)
hm_p = pseudobulk_heatmap(pca_stlist $plot_meta, hm_display_genes=$hm_display_genes, color_pal='$color_pal')

$plots

";
        return $script;
    }


    public function quiltPlot($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'quiltPlot.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getQuiltPlotScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $result = [];

        $parameterNames = ['quilt_plot_1_initial', 'quilt_plot_2_initial'];
        //If there's a filtered or normalized stlist generate the other plots
        if (Storage::fileExists($workingDir . 'normalized_stlist.RData') || Storage::fileExists($workingDir . 'filtered_stlist.RData'))
            $parameterNames = array_merge($parameterNames, ['quilt_plot_1', 'quilt_plot_2']);
        foreach ($parameterNames as $parameterName) {
            $file_extensions = ['svg', 'pdf', 'png'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id, 'tag' => 'quilt_plot'], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                    $result[$parameterName] = $this->workingDirPublicURL() . $parameterName;

                    if($file_extension === 'pdf') {
                        $_process_files[] = $fileName;
                    }
                }
            }
        }

        ProjectProcessFiles::updateOrCreate(['process' => 'quiltPlot', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        $result['output'] = $output;
        $result['script'] = $scriptContents;
        return $result;
    }



    public function getQuiltPlotScript($parameters): string
    {

        //If there's no normalizes stlist use the initial stlist
        $stlist = 'normalized_stlist';
        if (!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'filtered_stlist';
        if (!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'initial_stlist';

        $initial_stlist = 'initial_stlist';

        $plot_meta = $parameters['plot_meta'];
        $color_pal = $parameters['color_pal'];
        $sample1 = $parameters['sample1'];
        $sample2 = $parameters['sample2'];

        $plots = $this->getExportFilesCommands('quilt_plot_1', "plist1[[1]]");
        $plots .= $this->getExportFilesCommands('quilt_plot_2', "plist2[[1]]");

        $plots_initial = $this->getExportFilesCommands('quilt_plot_1_initial', "plist1_initial[[1]]");
        $plots_initial .= $this->getExportFilesCommands('quilt_plot_2_initial', "plist2_initial[[1]]");

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList
{$this->_loadStList($stlist)}
# Load initial STList
{$this->_loadStList($initial_stlist)}

#### Normalized Plots
plist1 = STplot($stlist, samples=c('$sample1'), plot_meta='$plot_meta', color_pal='$color_pal', ptsize=2)
plist2 = STplot($stlist, samples=c('$sample2'), plot_meta='$plot_meta', color_pal='$color_pal', ptsize=2)
#### Initial Plots
plist1_initial = STplot($initial_stlist, samples=c('$sample1'), plot_meta='$plot_meta', color_pal='$color_pal', ptsize=2)
plist2_initial = STplot($initial_stlist, samples=c('$sample2'), plot_meta='$plot_meta', color_pal='$color_pal', ptsize=2)


$plots

$plots_initial

";

        return $script;
    }



    public function STplotQuilt($parameters)
    {
        $workingDir = $this->workingDir();

        $scriptName = 'STplot-quiltPlot.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTplotQuiltScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $result = [];
        foreach ($parameters['genes'] as $gene) {
            $result[$gene] = [];
            foreach ($this->samples as $sample) {

                $baseName = 'stplot-quilt-' . $gene . '-' . $sample->name;
                $parameterNames = [$baseName, $baseName . '-sbs'];

                foreach ($parameterNames as $parameterName) {

                    $file_extensions = ['svg', 'pdf', 'png'];

                    foreach ($file_extensions as $file_extension) {
                        $fileName = $parameterName . '.' . $file_extension;
                        $file = $workingDir . $fileName;
                        $file_public = $this->workingDirPublic() . $fileName;
                        if (Storage::fileExists($file)) {
                            Storage::delete($file_public);
                            Storage::move($file, $file_public);
                            $result[$gene][$sample->name] = $this->workingDirPublicURL() . $baseName; // $fileName;

                            if($file_extension === 'pdf') {
                                $_process_files[] = $fileName;
                            }

                        }
                    }
                }

                //process CSV files for client-side plotting
                $fileName = $sample->name . '_expr_quilt_data.csv';
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    $result['plot_data'][$sample->name] = $this->workingDirPublicURL() . $fileName;
                }

            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stplot_quilt', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode($result)]);
        ProjectProcessFiles::updateOrCreate(['process' => 'STplotQuilt', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
    }

    public function getSTplotQuiltScript($parameters): string
    {

        $genes = $parameters['genes'];
        $ptsize = $parameters['ptsize'];
        $col_pal = $parameters['col_pal'];
        $data_type = $parameters['data_type'];

        $_genes = "c('" . join("','", $genes) . "')";

        $samples = $this->getSampleList(NULL);
        $samples_array = $this->getSampleList(NULL, true);

        $export_files = '';
        $export_files_side_by_side = '';
        foreach ($genes as $gene) {
            $_samples = $this->samples()->whereIn('name', $samples_array)->get();
            foreach ($_samples as $sample) {
                $export_files .= $this->getExportFilesCommands("stplot-quilt-$gene-" . $sample->name, "qp\$" . $gene . "_" . $sample->name);
                if ($sample->has_image) {
                    $export_files_side_by_side .= "tp = cowplot::ggdraw() + cowplot::draw_image('{$sample->image_file_path(true)}')" . PHP_EOL;
                    $export_files_side_by_side .= "qptp = ggpubr::ggarrange(qp\${$gene}_$sample->name, tp, ncol=2)" . PHP_EOL;
                    $export_files_side_by_side .= $this->getExportFilesCommands("stplot-quilt-$gene-" . $sample->name . '-sbs', 'qptp', 1400, 600) . PHP_EOL;
                }
            }
        }

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList
{$this->_loadStList('normalized_stlist')}

qp = STplot(normalized_stlist, genes=$_genes, ptsize=$ptsize, color_pal='$col_pal', data_type='$data_type', samples=$samples)

$export_files


{$this->getSTplotQuiltScript_exportCSV(['_stlist' => 'normalized_stlist', 'genes' => $_genes, 'samples' => $samples, 'data_type' => $data_type])}

";
//$export_files_side_by_side

        return $script;
    }



    private function getSTplotQuiltScript_exportCSV($params)
    {
        $script = Storage::get("/common/templates/STplot_quilt_export_data.R");

        foreach ($params as $key => $param) {
            $script = $this->replaceRscriptParameter($key, $param, $script);
        }

        return $script;
    }




    public function STplotExpressionSurface($parameters)
    {
        $workingDir = $this->workingDir();

        $scriptName = 'STplot-ExpressionSurface.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTplotExpressionSurfaceScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $result = [];
        foreach ($parameters['genes'] as $gene) {
            $result[$gene] = [];
            foreach ($this->samples as $sample) {
                $parameterName = 'stplot-expression-surface-' . $gene . '-' . $sample->name;

                $file_extensions = ['svg', 'pdf', 'png'];

                foreach ($file_extensions as $file_extension) {
                    $fileName = $parameterName . '.' . $file_extension;
                    $file = $workingDir . $fileName;
                    $file_public = $this->workingDirPublic() . $fileName;
                    if (Storage::fileExists($file)) {
                        Storage::delete($file_public);
                        Storage::move($file, $file_public);
                        $result[$gene][$sample->name] = $this->workingDirPublicURL() . $parameterName; // $fileName;

                        if($file_extension === 'pdf') {
                            $_process_files[] = $fileName;
                        }
                    }
                }
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stplot_expression_surface', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode($result)]);
        ProjectProcessFiles::updateOrCreate(['process' => 'STplotExpressionSurface', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
        //return json_encode($result);
    }

    public function getSTplotExpressionSurfaceScript($parameters): string
    {

        $samples = $this->getSampleList(NULL, true);
        $sample_list = $this->getSampleList(NULL);
        $samples = $this->samples()->whereIn('name', $samples)->get();

        $genes = $parameters['genes'];
        $col_pal = array_key_exists('col_pal', $parameters) ? $parameters['col_pal'] : '';
        $col_pal = ($col_pal !== null && strlen($col_pal)) ? $col_pal : 'sunset';

        $_genes = "c('" . join("','", $genes) . "')";

        $export_files = '';
        foreach ($genes as $gene)
            foreach (/*$this->samples*/ $samples as $sample)
                $export_files .= $this->getExportFilesCommands("stplot-expression-surface-$gene-" . $sample->name, "krp\$" . $gene . "_" . $sample->name);

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList

{$this->_loadStList('normalized_stlist')}


stlist_expression_surface = gene_interpolation(normalized_stlist, genes=$_genes, samples=$sample_list)

{$this->_saveStList('stlist_expression_surface')}

krp = STplot_interpolation(stlist_expression_surface, samples=$sample_list, genes=$_genes, color_pal='$col_pal')

$export_files

";

        return $script;
    }


    public function STplotExpressionSurfacePlots($parameters)
    {
        $workingDir = $this->workingDir();

        $scriptName = 'STplot-ExpressionSurfacePlots.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTplotExpressionSurfacePlotsScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $result = [];
        foreach ($parameters['genes'] as $gene) {
            $result[$gene] = [];
            foreach ($this->samples as $sample) {
                $parameterName = 'stplot-expression-surface-' . $gene . '-' . $sample->name;

                $file_extensions = ['svg', 'pdf', 'png'];

                foreach ($file_extensions as $file_extension) {
                    $fileName = $parameterName . '.' . $file_extension;
                    $file = $workingDir . $fileName;
                    $file_public = $this->workingDirPublic() . $fileName;
                    if (Storage::fileExists($file)) {
                        Storage::delete($file_public);
                        Storage::move($file, $file_public);
                        $result[$gene][$sample->name] = $this->workingDirPublicURL() . $parameterName; // $fileName;

                        if($file_extension === 'pdf') {
                            $_process_files[] = $fileName;
                        }
                    }
                }
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stplot_expression_surface', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode($result)]);
        ProjectProcessFiles::updateOrCreate(['process' => 'STplotExpressionSurfacePlots', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
        //return json_encode($result);
    }


    public function getSTplotExpressionSurfacePlotsScript($parameters): string
    {

        $genes = $parameters['genes'];
        $col_pal = array_key_exists('col_pal', $parameters) ? $parameters['col_pal'] : '';
        $col_pal = ($col_pal !== null && strlen($col_pal)) ? $col_pal : 'sunset';

        $_genes = "c('" . join("','", $genes) . "')";

        $export_files = '';
        foreach ($genes as $gene)
            foreach ($this->samples as $sample)
                $export_files .= $this->getExportFilesCommands("stplot-expression-surface-$gene-" . $sample->name, "krp\$" . $gene . "_" . $sample->name);

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList
" .
            $this->_loadStList('normalized_stlist')
            . "

#stlist_expression_surface = gene_interpolation(normalized_stlist, genes=$_genes)

{$this->_loadStList('stlist_expression_surface')}

krp = STplot_interpolation(stlist_expression_surface, genes=$_genes, color_pal='$col_pal')

$export_files

";

        return $script;
    }



    public function SThet($parameters)
    {
        $workingDir = $this->workingDir();

        $scriptName = 'SThet.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSThetScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $result = [];

        $prevoius_genes = array_key_exists('sthet_genes', $this->project_parameters) ? json_decode($this->project_parameters['sthet_genes']) : [];
        $new_genes = array_merge($parameters['genes'], $prevoius_genes);
        ProjectParameter::updateOrCreate(['parameter' => 'sthet_genes', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode($new_genes)]);

        $parameterNames = ['sthet_plot_table_results'];
        foreach ($parameterNames as $parameterName) {
            $file_extensions = ['xlsx'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                    $result[$parameterName] = $this->workingDirPublicURL() . $parameterName;

                    $_process_files[] = $fileName;
                }
            }
        }

        ProjectProcessFiles::updateOrCreate(['process' => 'SThet', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        $result['output'] = $output;
        $result['script'] = $scriptContents;
        return $result;
    }

    private function getSThetScript($parameters)
    {

        $stlist = 'stlist_sthet';
        if (!Storage::fileExists($this->workingDir() . "$stlist.RData")) $stlist = 'normalized_stlist';

        $genes = $parameters['genes'];
        $method = $parameters['method'];

        $_genes = "c('" . join("','", $genes) . "')";
        $_method = "c('" . join("','", $method) . "')";

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList

{$this->_loadStList($stlist)}

stlist_sthet = SThet($stlist, genes=$_genes, method=$_method)

{$this->_saveStList('stlist_sthet')}

# Get table with SThet results
sthet_table = get_gene_meta(stlist_sthet, sthet_only=T)
openxlsx::write.xlsx(sthet_table, file='sthet_plot_table_results.xlsx')

";

        return $script;
    }


    public function SThetPlot($parameters)
    {
        $workingDir = $this->workingDir();

        $scriptName = 'SThetPlot.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSThetPlotScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $result = [];

        $parameterNames = ['sthet_plot'];
        foreach ($parameterNames as $parameterName) {
            $file_extensions = ['svg', 'pdf', 'png'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                    $result[$parameterName] = $this->workingDirPublicURL() . $parameterName;

                    if($file_extension === 'pdf') {
                        $_process_files[] = $fileName;
                    }
                }
            }
        }

        ProjectProcessFiles::updateOrCreate(['process' => 'SThetPlot', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        $result['output'] = $output;
        $result['script'] = $scriptContents;
        return $result;
    }



    private function getSThetPlotScript($parameters)
    {

        $genes = $parameters['genes'];
        $color_pal = $parameters['color_pal'];

        //$plot_meta = $parameters['plot_meta'];
        $plot_meta = $parameters['plot_meta'];
        $plot_meta = strlen($plot_meta) ? "'$plot_meta'" : "NULL";

        $_genes = "c('" . join("','", $genes) . "')";

        $export_files = $this->getExportFilesCommands("sthet_plot", "sthet_plot");

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load STList
{$this->_loadStList('stlist_sthet')}

sthet_plot = compare_SThet(stlist_sthet, samplemeta=$plot_meta, genes=$_genes, color_pal='$color_pal')

$export_files

";

        return $script;
    }

    private function stdiff_top_deg($csv_file) {

        $workingDir = $this->workingDir();

        $column_names = [
            'gene' => 'Gene',
            'avg_log2fc' => 'Average log-Fold Change',
            'cluster_1' => 'Cluster 1',
            'cluster_2' => 'Cluster 2',
            'wilcox_p_val' => 'Wilcoxon’s p-value',
            'ttest_p_val' => 'T-test p-value',
            'mm_p_val' => 'Mixed model p-value',
            'adj_p_val' => 'Adjusted p-value'
        ];


        $file = $workingDir . $csv_file;
        if (Storage::fileExists($file)) {
            // info('****-----EXISTS------*****');
            $data = trim(Storage::read($file));
            // info('****-----DATA------*****' . $data);
            foreach (explode(',', $data) as $file) {
                if (Storage::fileExists($workingDir . $file . '.csv')) {
                    // info('****-----EXISTS------*****' . $file);
                    $this->csv2json($workingDir . $file . '.csv', 1, $column_names);
                    $files = [];
                    $files[] = $file . '.csv';
                    $files[] = $file . '.json';
                    foreach ($files as $file) {
                        if (Storage::fileExists($workingDir . $file)) {
                            $file_public = $this->workingDirPublic() . $file;
                            $file_to_move = $workingDir . $file;
                            Storage::delete($file_public);
                            Storage::move($file_to_move, $file_public);
                        }
                    }

                }
            }
        }
    }

    public function STclust($parameters)
    {
        $parameters['samples'] = $this->getSampleList(NULL);
        $samples = $this->getSampleList(NULL, true);

        $workingDir = $this->workingDir();

        $scriptName = 'STclust.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTclustScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $file = $workingDir . 'stclust_plots.csv';
        $plots = [];
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $data) as $plot) {
                $plots[] = $this->workingDirPublicURL() . $plot;
                $file_extensions = ['svg', 'pdf', 'png'];

                $plot_files = [$plot, "$plot-sbs"];
                foreach ($plot_files as $plot_file) {
                    foreach ($file_extensions as $file_extension) {
                        $fileName = $plot_file . '.' . $file_extension;
                        $file = $workingDir . $fileName;
                        $file_public = $this->workingDirPublic() . $fileName;
                        if (Storage::fileExists($file)) {
                            Storage::delete($file_public);
                            Storage::move($file, $file_public);

                            if($file_extension === 'pdf') {
                                $_process_files[] = $fileName;
                            }
                        }
                    }
                }
            }
        }


        $file = $workingDir . 'stclust_quilt_data.csv';
        $plot_data = [];
        foreach ($samples as $sample) {
            $fileName = $sample . '_stclust_quilt_data.csv';
            $file = $workingDir . $fileName;
            $file_public = $this->workingDirPublic() . $fileName;
            if (Storage::fileExists($file)) {
                Storage::delete($file_public);
                Storage::move($file, $file_public);
                $plot_data[$sample] = $this->workingDirPublicURL() . $fileName;
                $_process_files[] = $fileName;
            }
        }


        ProjectParameter::updateOrCreate(['parameter' => 'stclust', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'plots' => $plots, 'plot_data' => $plot_data])]);
        ProjectProcessFiles::updateOrCreate(['process' => 'STclust', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        $this->stdiff_top_deg('stclust_top_deg.csv');

        $this->current_step = 8;
        $this->save();

        return ['output' => $output, 'script' => $scriptContents];
    }


    private function getSTclustScript($parameters)
    {

        $script = Storage::get("/common/templates/STclust.R");

        //If there's no stclust_stlist use the normalized_stlist
        $_stlist = 'stclust_stlist';
        if (!Storage::fileExists($this->workingDir() . "$_stlist.RData")) $_stlist = 'normalized_stlist';

        $parameters['_stlist'] = $_stlist;

        $params = ['_stlist', 'ws', 'ks', 'topgenes', 'deepSplit', 'samples'];
        foreach ($params as $param) {
            $script = $this->replaceRscriptParameter($param, $parameters[$param], $script);
        }

        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);

        return $script;
    }

    private function saveSTdiffAnnotationChanges($_annotations) {
        $data = $this->getSTdiffData();

        $samples = [];
        $annotations = [];
        $changes = false;
        foreach($_annotations as $change) {

            $samples[] = $change['sampleName'];
            $annotations[] = $change['originalName'];

            foreach($data as $index => $row) {
                if($change['sampleName'] === $row[0] && $change['originalName'] === $row[1]) {
                    foreach($change['clusters'] as $cluster) {
                        if($row[3] === $cluster['originalName'] && $row[4] !== $cluster['newName']) {
                            $data[$index][4] = $cluster['newName'];
                            $changes = true;
                        }
                    }

                    if($change['newName'] !== $row[2]) {
                        $data[$index][2] = $change['newName'];
                        $changes = true;
                    }

                    if($changes && $row[1] === $data[$index][2]) {
                        $data[$index][2] = $row[1] . '_mod';
                    }
                }
            }

        }

        if($changes) {
            $this->setSTdiffData($data);
        }

        $samples = array_unique($samples);
        $annotations = array_unique($annotations);

        return ['annotations' => $annotations, 'samples' => $samples];
    }


    public function STclustRename($parameters)
    {
        $workingDir = $this->workingDir();

        $result = $this->saveSTdiffAnnotationChanges($parameters['annotations']);

        $parameters['_samples'] = "c('" . join("','", $result['samples']) . "')";
        $parameters['_annotations'] = "c('" . join("','", $result['annotations']) . "')";


        $scriptName = 'STclustRename.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTclustRenameScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);


        foreach($parameters['annotations'] as $change) {
            $file_extensions = ['svg', 'pdf', 'png'];
            $plot_file = $change['sampleName'] . '_' . $change['originalName'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $plot_file . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                }
            }
        }

        return ['output' => $output, 'script' => $scriptContents];
    }

    private function getSTclustRenameScript($parameters)
    {

        $script = Storage::get("/common/templates/STclust2_rename.R");

        //If there's no stclust_stlist use the normalized_stlist
        $_stlist = 'stclust_stlist';
        if (!Storage::fileExists($this->workingDir() . "$_stlist.RData")) $_stlist = 'normalized_stlist';
        $parameters['_stlist'] = $_stlist;

        $params = ['_stlist', '_samples', '_annotations'];
        foreach ($params as $param) {
            $script = $this->replaceRscriptParameter($param, $parameters[$param], $script);
        }

        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);

        return $script;
    }





    private function getSimpleSTlistScript($parameters)
    {

        $script = Storage::get("/common/templates/_simple_stlist.R");

        //If there's no stclust_stlist use the normalized_stlist
        $_stlist = 'stclust_stlist';
        if (!Storage::fileExists($this->workingDir() . "$_stlist.RData")) $_stlist = 'normalized_stlist';
        $parameters['_stlist'] = $_stlist;

        $params = ['_stlist', '_samples'];
        foreach ($params as $param) {
            $script = $this->replaceRscriptParameter($param, $parameters[$param], $script);
        }

        return $script;
    }


    public function SpaGCN($parameters)
    {
        $samples = $this->getSampleList(NULL, true);
        $sampleList = "'" . join("','", $samples) . "'";
        $parameters['_samples'] = $this->getSampleList(NULL);

        $workingDir = $this->workingDir();

        //Create simple STlist as input for SpaGCN
        $scriptName = 'SpaGCN_1_simpleSTlist.R';
        $script = $workingDir . $scriptName;
        $scriptContents = $this->getSimpleSTlistScript($parameters);
        Storage::put($script, $scriptContents);
        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        //Run SpaGCN on the simple STList
        $scriptName = 'SpaGCN1.py';
        $scriptContents = Storage::get("/common/templates/$scriptName");
        $params = ['p', 'user_seed', 'number_of_domains_min', 'number_of_domains_max', 'refine_clusters'];
        foreach ($params as $param) {
            $scriptContents = str_replace("{param_$param}", $parameters[$param], $scriptContents);
        }
        $scriptContents = str_replace("{param_sample_list}", $sampleList, $scriptContents);
        Storage::put("$workingDir/$scriptName", $scriptContents);
        $output .= $this->spatialExecute('python ' . $scriptName, $parameters['__task'], 'SPAGCN');

        //Import back results from SpaGCN
        $scriptName = 'SpaGCN_3_import.R';
        $script = $workingDir . $scriptName;
        $scriptContents = $this->getSpaGCN_ImportClassifications($parameters);
        Storage::put($script, $scriptContents);
        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $plot_data = [];
        foreach ($samples as $sample) {
            $fileName = 'spagcn_predicted_domains_sample_' . $sample . '.csv';
            $file = $workingDir . $fileName;
            $file_public = $this->workingDirPublic() . $fileName;
            if (Storage::fileExists($file)) {
                Storage::delete($file_public);
                Storage::move($file, $file_public);
                $plot_data[$sample] = $this->workingDirPublicURL() . $fileName;
                $_process_files[] = $fileName;
            }
        }


        $file = $workingDir . 'spagcn_plots.csv';
        if (Storage::fileExists($file)) {

            /*$zip = new \ZipArchive();
            $zipFileName = 'SpaGCN.zip';
            $addToZip = $zip->open(Storage::path($this->workingDirPublic() . $zipFileName), \ZipArchive::CREATE) == TRUE;*/

            $data = trim(Storage::read($file));
            $plots = [];
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $data) as $plot) {
                $plots[] = $this->workingDirPublicURL() . $plot;
                $file_extensions = ['svg', 'pdf', 'png'];

                $plot_files = [$plot, "$plot-sbs"];
                foreach ($plot_files as $plot_file) {
                    foreach ($file_extensions as $file_extension) {
                        $fileName = $plot_file . '.' . $file_extension;
                        $file = $workingDir . $fileName;
                        $file_public = $this->workingDirPublic() . $fileName;
                        if (Storage::fileExists($file)) {
                            Storage::delete($file_public);
                            Storage::move($file, $file_public);

                            if($file_extension === 'pdf') {
                                $_process_files[] = $fileName;
                            }

                            //if ($addToZip) $zip->addFile(Storage::path($file_public), basename($file_public));
                        }
                    }
                }
            }

            /*$task = Task::where('task', $parameters['__task'])->firstOrFail();
            $parameterLog = json_decode($task->payload)->parameters;
            $logFileName = $this->workingDirPublicURL() . 'SpaGCN_execution_log.txt';
            Storage::put($logFileName, json_encode($parameterLog));*/

            /*if ($addToZip) {
                $zip->addFile(Storage::path($logFileName), basename($logFileName));
                $zip->close();
            }*/

            ProjectParameter::updateOrCreate(['parameter' => 'spagcn', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'plots' => $plots, 'plot_data' => $plot_data])]);
            ProjectProcessFiles::updateOrCreate(['process' => 'SpaGCN', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);
        }


        $this->stdiff_top_deg('spagcn_top_deg.csv');

        $this->current_step = 8;
        $this->save();



        return ['output' => $output, 'script' => $scriptContents];
    }


    private function getSpaGCN_ImportClassifications($parameters)
    {

        $script = Storage::get("/common/templates/SpaGCN1_import_classifications.R");

        //If there's no stclust_stlist use the normalized_stlist
        $_stlist = 'stclust_stlist';
        if (!Storage::fileExists($this->workingDir() . "$_stlist.RData")) $_stlist = 'normalized_stlist';

        $parameters['_stlist'] = $_stlist;

        $params = ['_stlist', '_samples'];
        foreach ($params as $param) {
            $script = $this->replaceRscriptParameter($param, $parameters[$param], $script);
        }

        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);

        return $script;
    }


    public function SpaGCN_SVG($parameters)
    {

        $workingDir = $this->workingDir();

        $sampleList = "'" . implode("','", $this->getSampleList(NULL, true)) . "'";

        //Run R script to prepare annotations
        $scriptName = 'SpaGCN2_SVG.R';
        $scriptContents = Storage::get("/common/templates/$scriptName");
        $scriptContents = $this->replaceRscriptParameter('_stlist', 'stclust_stlist', $scriptContents);
        $scriptContents = $this->replaceRscriptParameter('annotation_to_test', $parameters['annotation_to_test'], $scriptContents);
        $scriptContents = $this->replaceRscriptParameter('sample_list', $sampleList, $scriptContents);
        Storage::put("$workingDir/$scriptName", $scriptContents);
        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        //Run SpaGCN_SVG on the simple STList
        $scriptName = 'SpaGCN2_SVG.py';
        $scriptContents = Storage::get("/common/templates/$scriptName");
        $scriptContents = str_replace("{param_annotation_to_test}", $parameters['annotation_to_test'], $scriptContents);
        $scriptContents = str_replace("{param_sample_list}", $sampleList, $scriptContents);
        Storage::put("$workingDir/$scriptName", $scriptContents);
        $output .= $this->spatialExecute('python ' . $scriptName, $parameters['__task'], 'SPAGCN');

        $_process_files = [];

        $file = $workingDir . 'spagcn_svg_files.json';
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            $filesCSV = json_decode($data);
        }


        $column_names = [
            'genes' => 'Gene',
            'in_group_fraction' => 'In-group fraction',
            'out_group_fraction' => 'Out-group fraction',
            'in_out_group_ratio' => 'In/out-group ratio',
            'in_group_mean_exp' => 'In-group mean expr.',
            'out_group_mean_exp' => 'Out-group mean expr.',
            'fold_change' => 'Fold change',
            'pvals_adj' => 'Adjusted p-value'
        ];

        $filesJSON = [];
        $k = 0;
        foreach ($filesCSV as $sampleName => $files) {
            $filesJSON[$sampleName] = [];
            $k_tmp = 0;
            foreach ($files as $file) {
                if (Storage::fileExists($workingDir . $file)) {

                    $this->csv2json($workingDir . $file, 0, $column_names);

                    $fileJSON = str_replace('.csv', '.json', $file);

                    $file_public = $this->workingDirPublic() . $fileJSON;
                    $file_to_move = $workingDir . $fileJSON;
                    Storage::delete($file_public);
                    Storage::move($file_to_move, $file_public);

                    $file_public = $this->workingDirPublic() . $file;
                    $file_to_move = $workingDir . $file;
                    Storage::delete($file_public);
                    Storage::move($file_to_move, $file_public);

                    $filesJSON[$sampleName][] = $this->workingDirPublicURL() . $fileJSON;

                    $_process_files[] = $file;

                    $k_tmp++;
                }
            }
            $k = $k_tmp;
        }

        ProjectParameter::updateOrCreate(['parameter' => 'spagcn_svg', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'json_files' => $filesJSON, 'k' => $k])]);
        ProjectProcessFiles::updateOrCreate(['process' => 'SpaGCN_SVG', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
    }



    public function SpaGCNRename($parameters)
    {
        $workingDir = $this->workingDir();

        $result = $this->saveSTdiffAnnotationChanges($parameters['annotations']);

        $parameters['_samples'] = "c('" . join("','", $result['samples']) . "')";
        $parameters['_annotations'] = "c('" . join("','", $result['annotations']) . "')";


        $scriptName = 'SpaGCNRename.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSpaGCNRenameScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);


        foreach($parameters['annotations'] as $change) {
            $file_extensions = ['svg', 'pdf', 'png'];
            $plot_file = $change['sampleName'] . '_' . $change['originalName'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $plot_file . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                }
            }
        }

        return ['output' => $output, 'script' => $scriptContents];
    }

    private function getSpaGCNRenameScript($parameters)
    {

        $script = Storage::get("/common/templates/SpaGCN1_rename.R");

        //If there's no stclust_stlist use the normalized_stlist
        $_stlist = 'stclust_stlist';
        if (!Storage::fileExists($this->workingDir() . "$_stlist.RData")) $_stlist = 'normalized_stlist';
        $parameters['_stlist'] = $_stlist;

        $params = ['_stlist', '_samples', '_annotations'];
        foreach ($params as $param) {
            $script = $this->replaceRscriptParameter($param, $parameters[$param], $script);
        }

        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);

        return $script;
    }



    public function MILWRM($parameters)
    {
        $workingDir = $this->workingDir();

        //Create simple STlist as input for MILWRM
        $scriptName = 'MILWRM_1_simpleSTlist.R';
        $script = $workingDir . $scriptName;
        $parameters['_samples'] = $this->getSampleList($parameters['samples']);
        $scriptContents = $this->getSimpleSTlistScript($parameters);
        Storage::put($script, $scriptContents);
        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        //Run MILWRM on the simple STList
        $scriptName = 'MILWRM_script.py';
        $scriptContents = Storage::get("/common/templates/$scriptName");
        $parameters['sample_list'] = "'" . join("','", $this->getSampleList($parameters['samples'], true)) . "'";
        $params = ['alpha', 'max_pc', 'sample_list'];
        foreach ($params as $param) {
            $scriptContents = str_replace("{param_$param}", $parameters[$param], $scriptContents);
        }
        Storage::put("$workingDir/$scriptName", $scriptContents);
        $output .= $this->spatialExecute('python ' . $scriptName, $parameters['__task'], 'MILWRM');

        $_process_files = [];
        $plot_data = [];
        foreach ($this->getSampleList($parameters['samples'], true) as $sample) {
            $fileName = 'milwrm_predicted_domains_sample_' . $sample . '.csv';
            $file = $workingDir . $fileName;
            $file_public = $this->workingDirPublic() . $fileName;
            if (Storage::fileExists($file)) {
                Storage::delete($file_public);
                Storage::move($file, $file_public);
                $plot_data[$sample] = $this->workingDirPublicURL() . $fileName;
                $_process_files[] = $fileName;
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'milwrm', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'plot_data' => $plot_data])]);
        ProjectProcessFiles::updateOrCreate(['process' => 'MILWRM', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        //$this->current_step = 8;
        $this->save();

        return ['output' => $output, 'script' => $scriptContents];
    }




    public function STDiffNonSpatial($parameters)
    {
        $workingDir = $this->workingDir();
        $workingDirPublic = $this->workingDirPublic();

        $scriptName = 'STDiff_NonSpatial.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTDiffNonSpatialScript($parameters);
        Storage::put($script, $scriptContents);

        $this->clusterTestSTDiffNonSpatial();

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $column_names = [
            'gene' => 'Gene',
            'avg_log2fc' => 'Average log-Fold Change',
            'cluster_1' => 'Cluster 1',
            'cluster_2' => 'Cluster 2',
            'wilcox_p_val' => 'Wilcoxon’s p-value',
            'ttest_p_val' => 'T-test p-value',
            'mm_p_val' => 'Mixed model p-value',
            'adj_p_val' => 'Adjusted p-value'
        ];


        $files = [];
        foreach ($parameters['samples_array'] as $sample) {
            $files[] = 'stdiff_ns_results_' . $sample . '.xlsx';
            $files[] = 'stdiff_ns_' . $sample . '.csv';
            $files[] = 'stdiff_ns_' . $sample . '.json';
        }
        foreach ($files as $file) {
            if (Storage::fileExists($workingDir . $file)) {

                if (explode('.', $file)[1] === 'csv')
                    $this->csv2json($workingDir . $file, 1, $column_names);

                $file_public = $workingDirPublic . $file;
                $file_to_move = $workingDir . $file;
                Storage::delete($file_public);
                Storage::move($file_to_move, $file_public);

                if (explode('.', $file)[1] !== 'json') {
                    $_process_files[] = $file;
                }

            }
        }


        $file = $workingDir . 'stdiff_ns_volcano_plots.csv';
        $vps = [];
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            foreach (explode(',', $data) as $plot) {
                $vps[] = $this->workingDirPublicURL() . $plot;
                $file_extensions = ['svg', 'pdf', 'png'];
                foreach ($file_extensions as $file_extension) {
                    $fileName = $plot . '.' . $file_extension;
                    $file = $workingDir . $fileName;
                    $file_public = $this->workingDirPublic() . $fileName;
                    if (Storage::fileExists($file)) {
                        Storage::delete($file_public);
                        Storage::move($file, $file_public);

                        if($file_extension === 'pdf') {
                            $_process_files[] = $fileName;
                        }
                    }
                }
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stdiff_ns', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(), 'samples' => $parameters['samples_array'], 'volcano_plots' => $vps])]);
        ProjectProcessFiles::updateOrCreate(['process' => 'STDiffNonSpatial', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
    }


    private function getSTDiffNonSpatialScript($parameters)
    {

        $params = [
            '_stlist' => 'stclust_stlist',
            'samples' => $parameters['samples'],
            'topgenes' => $parameters['topgenes'],
            'annot' => $parameters['annotation'],
            'test_type' => $parameters['test_type'],
            'pairwise' => $parameters['pairwise'],
            'clusters' => $parameters['clusters']
        ];



        $script = Storage::get("/common/templates/STdiff_nonSpatial.R");

        foreach ($params as $param => $value) {
            $script = $this->replaceRscriptParameter($param, $value, $script);
        }

        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);

        return $script;
    }


    private function DELETE_getSTDiffNonSpatialScript($parameters)
    {

        $samples = $parameters['samples'];
        $annotation = $parameters['annotation'];
        $topgenes = $parameters['topgenes'];
        $test_type = $parameters['test_type'];
        $pairwise = $parameters['pairwise'];
        $clusters = $parameters['clusters'];

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList
" .
            $this->_loadStList('stclust_stlist')
            . "

de_genes_results = STdiff(stclust_stlist, #### STCLUST STList
                          samples=$samples,   #### Users should be able to select which samples to include in analysis
                          annot='$annotation',  #### Name of variable to use in analysis... Dropdown to select one of `annot_variables`
                          topgenes=$topgenes, #### !!! Defines a lot of the speed. 100 are too few genes. Minimally would like 5000 but is SLOW. Can be a slider as in pseudobulk
                          sp_topgenes = 0,
                          test_type='$test_type', #### Other options are 't_test' and 'mm',
                          pairwise=$pairwise, #### Check box
                          clusters=$clusters, #### Need ideas for this one. Values in `cluster_values` and after user selected value in annot dropdown
                          cores=12) #### You know, the more the merrier

# Get workbook with results (samples in spreadsheets)
openxlsx::write.xlsx(de_genes_results, file='stdiff_ns_results.xlsx')

# Each sample as a CSV
lapply(names(de_genes_results), function(i){
  write.csv(de_genes_results[[i]], paste0('stdiff_ns_', i, '.csv'), row.names=T, quote=F)
})


# Create volcano plots
ps = STdiff_volcano(de_genes_results, samples=$samples, clusters=$clusters, pval_thr=0.05, color_pal=NULL)

#save file with plots names list
write.table(names(ps), 'stdiff_ns_volcano_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
# Save plots to file
lapply(names(ps), function(i){
  {$this->getExportFilesCommands('paste0(\'stdiff_ns_vp_\', i)', 'ps[[i]]')}
})

";

        return $script;
    }





    public function STDiffSpatial($parameters)
    {
        $workingDir = $this->workingDir();
        $workingDirPublic = $this->workingDirPublic();

        $scriptName = 'STDiff_Spatial.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTDiffSpatialScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $column_names = [
            'gene' => 'Gene',
            'avg_log2fc' => 'Average log-Fold Change',
            'cluster_1' => 'Cluster 1',
            'cluster_2' => 'Cluster 2',
            'mm_p_val' => 'Mixed model p-value',
            'adj_p_val' => 'Adjusted p-value',
            'exp_p_val' => 'Spatial p-value',
            'exp_adj_p_val' => 'Adjusted spatial p-value',
            'comments' => 'Comments'
        ];


        $files = ['stdiff_s_results.xlsx'];
        foreach ($parameters['samples_array'] as $sample) {
            $files[] = 'stdiff_s_' . $sample . '.csv';
            $files[] = 'stdiff_s_' . $sample . '.json';
        }
        foreach ($files as $file) {
            if (Storage::fileExists($workingDir . $file)) {

                if (explode('.', $file)[1] === 'csv')
                    $this->csv2json($workingDir . $file, 2, $column_names);

                $file_public = $workingDirPublic . $file;
                $file_to_move = $workingDir . $file;
                Storage::delete($file_public);
                Storage::move($file_to_move, $file_public);

                if (explode('.', $file)[1] !== 'json') {
                    $_process_files[] = $file;
                }
            }
        }


        $file = $workingDir . 'stdiff_s_volcano_plots.csv';
        $vps = [];
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $data) as $_plot) {
                $plot = 'stdiff_s_vp_' . $_plot;
                $vps[] = $this->workingDirPublicURL() . $plot;
                $file_extensions = ['svg', 'pdf', 'png'];
                foreach ($file_extensions as $file_extension) {
                    $fileName = $plot . '.' . $file_extension;
                    $file = $workingDir . $fileName;
                    $file_public = $this->workingDirPublic() . $fileName;
                    if (Storage::fileExists($file)) {
                        Storage::delete($file_public);
                        Storage::move($file, $file_public);

                        if($file_extension === 'pdf') {
                            $_process_files[] = $fileName;
                        }
                    }
                }
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stdiff_s', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(),  'samples' => $parameters['samples_array'], 'volcano_plots' => $vps])]);
        ProjectProcessFiles::updateOrCreate(['process' => 'STDiffSpatial', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
    }



    private function getSTDiffSpatialScript($parameters)
    {

        $samples = $parameters['samples'];
        $annotation = $parameters['annotation'];
        $topgenes = $parameters['topgenes'];
        $pairwise = $parameters['pairwise'];
        $clusters = $parameters['clusters'];

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList
" .
            $this->_loadStList('stclust_stlist')
            . "

spatial_de_genes_results = STdiff(stclust_stlist, #### NORMALIZED STList
                          samples=$samples,   #### Users should be able to select which samples to include in analysis
                          annot='$annotation',  #### Name of variable to use in analysis... Dropdown to select one of `annot_variables`
                          topgenes=$topgenes, #### !!! Defines a lot of the speed. 100 are too few genes. Minimally would like 5000 but is SLOW. Can be a slider as in pseudobulk
                          sp_topgenes = 0.2,
                          test_type='mm', #### Other options are 't_test' and 'mm',
                          pairwise=$pairwise, #### Check box
                          clusters=$clusters, #### Need ideas for this one. Values in `cluster_values` and after user selected value in annot dropdown
                          cores=1) #### You know, the more the merrier

{$this->_saveStList('spatial_de_genes_results')}

# Get workbook with results (samples in spreadsheets)
openxlsx::write.xlsx(spatial_de_genes_results, file='stdiff_s_results.xlsx')

# Each sample as a CSV
lapply(names(spatial_de_genes_results), function(i){
  write.csv(spatial_de_genes_results[[i]], paste0('stdiff_s_', i, '.csv'), row.names=T, quote=F)
})

# Create volcano plots
ps = STdiff_volcano(spatial_de_genes_results, samples=$samples, clusters=$clusters, pval_thr=0.05, color_pal=NULL)

#save file with plots names list
write.table(names(ps), 'stdiff_s_volcano_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
# Save plots to file
lapply(names(ps), function(i){
  {$this->getExportFilesCommands('paste0(\'stdiff_s_vp_\', i)', 'ps[[i]]')}
})

";

        return $script;
    }




    public function STEnrich($parameters)
    {

        $workingDir = $this->workingDir();
        $workingDirPublic = $this->workingDirPublic();

        $scriptName = 'STEnrich.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTEnrichScript($parameters);
        Storage::put($script, $scriptContents);

        $this->clusterTestSTEnrich($parameters['gene_sets'] . '.gmt');

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $column_names = [
            'gene_set' => 'Gene set',
            'size_test' => 'Genes in sample',
            'size_gene_set' => 'Genes in set',
            'p_value' => 'p-value',
            'adj_p_value' => 'Adjusted p-value'
        ];


        $files = ['stenrich_results.xlsx'];
        foreach ($this->samples->pluck('name') as $sample) {
            $files[] = 'stenrich_' . $sample . '.csv';
            $files[] = 'stenrich_' . $sample . '.json';
        }

        //Heatmap
        $files[] = 'stenrich_heatmap.svg';
        $files[] = 'stenrich_heatmap.pdf';
        $files[] = 'stenrich_heatmap.png';

        foreach ($files as $file) {
            if (Storage::fileExists($workingDir . $file)) {

                if (explode('.', $file)[1] === 'csv')
                    $this->csv2json($workingDir . $file, 2, $column_names);

                $file_public = $workingDirPublic . $file;
                $file_to_move = $workingDir . $file;
                Storage::delete($file_public);
                Storage::move($file_to_move, $file_public);

                if (explode('.', $file)[1] !== 'json') {
                    $_process_files[] = $file;
                }
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stenrich', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(), 'heatmap' => 'stenrich_heatmap', 'samples' => $this->samples->pluck('name')])]);
        ProjectProcessFiles::updateOrCreate(['process' => 'STEnrich', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
    }



    private function getSTEnrichScript($parameters)
    {

        $gene_sets_file = 'common/stenrich/' . $parameters['gene_sets'] . '.gmt';
        Storage::copy($gene_sets_file, $this->workingDir() . $parameters['gene_sets'] . '.gmt');

        $params = [
            '_stlist' => 'normalized_stlist',
            'permutations' => $parameters['permutations'],
            'num_sds' => $parameters['num_sds'],
            'min_spots' => $parameters['min_spots'],
            'min_genes' => $parameters['min_genes'],
            'seed' => $parameters['seed'],
            'gene_sets_file' => $parameters['gene_sets'] . '.gmt'
        ];



        $script = Storage::get("/common/templates/STenrich.R");

        foreach ($params as $param => $value) {
            $script = $this->replaceRscriptParameter($param, $value, $script);
        }

        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);

        return $script;
    }




    private function DELETE_getSTEnrichScript($parameters)
    {

        $gene_sets_file = 'common/stenrich/' . $parameters['gene_sets'] . '.gmt';
        Log::info('source: ' . $gene_sets_file);
        Log::info('destination: ' . $this->workingDir() . $parameters['gene_sets'] . '.gmt');

        Storage::copy($gene_sets_file, $this->workingDir() . $parameters['gene_sets'] . '.gmt');
        $gene_sets_file = $parameters['gene_sets'] . '.gmt';


        $permutations = $parameters['permutations'];
        $num_sds = $parameters['num_sds'];
        $min_spots = $parameters['min_spots'];
        $min_genes = $parameters['min_genes'];
        $seed = $parameters['seed'];


        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# This first part to parse the input files containing gene sets
# These lines produce a named list to be passed to the `gene_sets` parameter
# in the STenrich function
fp = '$gene_sets_file'
pws_raw = readLines(fp)
pws = lapply(pws_raw, function(i){
  pw_tmp = unlist(strsplit(i, split='\\t'))
  pw_name_tmp = pw_tmp[1]
  pw_genes_tmp = pw_tmp[-c(1:2)]
  return(list(pw_name=pw_name_tmp,
              pw_genes=pw_genes_tmp))
})
rm(pws_raw, fp)

pws_names = c()
for(i in 1:length(pws)){
  pws_names = append(pws_names, pws[[i]][['pw_name']])
  pws[[i]] = pws[[i]][['pw_genes']]
}
names(pws) = pws_names


# Load normalized STList
" .
            $this->_loadStList('normalized_stlist')
            . "
#print(pws)

# Run STenrich
sp_enrichment = STenrich(normalized_stlist,
                         gene_sets=pws,
                         reps=$permutations,
                         num_sds=$num_sds,
                         min_units=$min_spots,
                         min_genes=$min_genes,
                         seed=$seed,
                         cores=4)

# Get workbook with results (samples in spreadsheets)
# Similar output to STdiff
openxlsx::write.xlsx(sp_enrichment, file='./stenrich_results.xlsx')

# Each sample as a CSV
lapply(names(sp_enrichment), function(i){
  write.csv(sp_enrichment[[i]], paste0('./stenrich_', i, '.csv'), row.names=T, quote=F)
})


";

        return $script;
    }



    public function STGradients($parameters)
    {
        $workingDir = $this->workingDir();
        $workingDirPublic = $this->workingDirPublic();

        $scriptName = 'STGradients.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTGradientsScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $column_names = [
            'gene' => 'Gene',
            //TODO: min and avg can be simplified
            'min_lm_coef' => 'Linear model slope',
            'min_lm_pval' => 'Linear model p-value',
            'min_spearman_r' => 'Spearman’s coefficient',
            'min_spearman_r_pval' => 'Spearman’s p-value',
            'min_spearman_r_pval_adj' => 'Spearman’s adjusted p-value',
            'min_pval_comment' => 'Comment',
            'avg_lm_coef' => 'Linear model slope',
            'avg_lm_pval' => 'Linear model p-value',
            'avg_spearman_r' => 'Spearman’s coefficient',
            'avg_spearman_r_pval' => 'Spearman’s p-value',
            'avg_spearman_r_pval_adj' => 'Spearman’s adjusted p-value',
            'avg_pval_comment' => 'Comment'
        ];

        $files = [];
        foreach ($parameters['samples_array'] as $sample) {
            $files[] = 'stgradients_results_' . $sample . '.xlsx';
            $files[] = 'stgradients_' . $sample . '.csv';
            $files[] = 'stgradients_' . $sample . '.json';
        }

        //Heatmap
        $files[] = 'stgradients_heatmap.svg';
        $files[] = 'stgradients_heatmap.pdf';
        $files[] = 'stgradients_heatmap.png';

        foreach ($files as $file)
            if (Storage::fileExists($workingDir . $file)) {

                if (explode('.', $file)[1] === 'csv')
                    $this->csv2json($workingDir . $file, 1, $column_names);

                $file_public = $workingDirPublic . $file;
                $file_to_move = $workingDir . $file;
                Storage::delete($file_public);
                Storage::move($file_to_move, $file_public);

                if (explode('.', $file)[1] !== 'json') {
                    $_process_files[] = $file;
                }
            }

        ProjectParameter::updateOrCreate(['parameter' => 'stgradients', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(), 'heatmap' => 'stgradients_heatmap', 'samples' => $parameters['samples_array']])]);
        ProjectProcessFiles::updateOrCreate(['process' => 'STGradients', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
    }


    private function getSTGradientsScript($parameters)
    {

        $params = [
            '_stlist' => 'stclust_stlist',
            'samples' => $parameters['samples'],
            'topgenes' => $parameters['topgenes'],
            'annot' => $parameters['annot'],
            'ref' => $parameters['ref'],
            'exclude_string' => $parameters['exclude_string'],
            'out_rm' => $parameters['out_rm'] ? 'T' : 'F',
            'limit' => is_numeric($parameters['limit']) && floatval($parameters['limit']) > 0 ? $parameters['limit'] : 'NULL',
            'distsumm' => $parameters['distsumm'],
            'min_nb' => is_numeric($parameters['min_nb']) && intval($parameters['min_nb']) >= 0 ? intval($parameters['min_nb']) : '0',
            'robust' => $parameters['robust'] ? 'T' : 'F'
        ];



        $script = Storage::get("/common/templates/STgradients.R");

        foreach ($params as $param => $value) {
            $script = $this->replaceRscriptParameter($param, $value, $script);
        }

        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);

        return $script;
    }


    private function DELETE_getSTGradientsScript($parameters)
    {

        $samples = $parameters['samples'];
        $topgenes = $parameters['topgenes'];
        $annot = $parameters['annot'];
        $ref = $parameters['ref'];
        $exclude = $parameters['exclude_string'];
        $out_rm = $parameters['out_rm'] ? 'T' : 'F';
        $limit = is_numeric($parameters['limit']) && floatval($parameters['limit']) > 0 ? $parameters['limit'] : 'NULL';
        $distsumm = $parameters['distsumm'];
        $min_nb = is_numeric($parameters['min_nb']) && intval($parameters['min_nb']) >= 0 ? intval($parameters['min_nb']) : '0';
        $robust = $parameters['robust'] ? 'T' : 'F';

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load stclust STList
" .
            $this->_loadStList('stclust_stlist')
            . "

grad_res = STgradient(x=stclust_stlist, # STCLUST STLIST
                      samples=$samples,
                      topgenes=$topgenes,
                      annot='$annot',
                      ref=$ref,
                      exclude=$exclude,
                      out_rm=$out_rm,
                      limit=$limit,
                      distsumm='$distsumm',
                      min_nb=$min_nb,
                      robust=$robust,
                      cores=1)

# Get workbook with results (samples in spreadsheets)
openxlsx::write.xlsx(grad_res, file='stgradients_results.xlsx')

# Each sample as a CSV
lapply(names(grad_res), function(i){
  write.csv(grad_res[[i]], paste0('stgradients_', i, '.csv'), row.names=T, quote=F)
})

";

        return $script;
    }



    public function STdeconvolve($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'STdeconvolve.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTdeconvolve1Script($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $file = $workingDir . 'stdeconvolve_plots.csv';
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            $plots = [];
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $data) as $plot) {

                $fileplot = 'stdeconvolve_' . $plot;

                $plots[] = $this->workingDirPublicURL() . $fileplot;
                $file_extensions = ['svg', 'pdf', 'png'];

                $plot_files = [$fileplot, "$fileplot-sbs"];
                foreach ($plot_files as $plot_file) {
                    foreach ($file_extensions as $file_extension) {
                        $fileName = $plot_file . '.' . $file_extension;
                        $file = $workingDir . $fileName;
                        $file_public = $this->workingDirPublic() . $fileName;
                        if (Storage::fileExists($file)) {
                            Storage::delete($file_public);
                            Storage::move($file, $file_public);

                            if($file_extension === 'pdf') {
                                $_process_files[] = $fileName;
                            }
                        }
                    }
                }
            }

            $suggested_k = [];
            $file = $workingDir . 'stdeconvolve_suggested_k.csv';
            if (Storage::fileExists($file)) {
                $data = trim(Storage::read($file));
                foreach (preg_split("/((\r?\n)|(\r\n?))/", $data) as $line) {
                    $tmp = explode(',', $line);
                    $suggested_k[$tmp[0]] = $tmp[1];
                }
                array_shift($suggested_k);
            }

            ProjectParameter::updateOrCreate(['parameter' => 'STdeconvolve', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'plots' => $plots, 'suggested_k' => $suggested_k])]);
            ProjectProcessFiles::updateOrCreate(['process' => 'STdeconvolve', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);
        }

        return ['output' => $output, 'script' => $scriptContents];
    }

    private function getSTdeconvolve1Script($parameters)
    {

        $script = Storage::get("/common/templates/STdeconvolve1_model_fitting.R");
        $params = ['rm_mt', 'rm_rp', 'use_var_genes', 'use_var_genes_n', 'min_k', 'max_k'];
        foreach ($params as $param) {
            $script = $this->replaceRscriptParameter($param, $parameters[$param], $script);
        }
        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);
        $script = $this->replaceRscriptParameter('samples_with_tissue', $this->samplesWithTissue(), $script);
        $sampleList = "'" . $this->samples()->pluck('samples.name')->join("','") . "'";
        $script = $this->replaceRscriptParameter('sample_list', $sampleList, $script);

        return $script;
    }



    public function STdeconvolve2($parameters)
    {

        //tabs per sample
        //Spatial plot,  Log-fold change

        $workingDir = $this->workingDir();

        $scriptName = 'STdeconvolve2.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTdeconvolve2Script($parameters);

        if(strlen($parameters['celltype_markers'])) {
            $file_ext = [/*'csv',*/ 'RDS'];
            foreach ($file_ext as $ext) {
                Storage::copy("/common/stdeconvolve/{$parameters['celltype_markers']}.$ext", $this->workingDir() . $parameters['celltype_markers'] . ".$ext");
            }
            $scriptContents = $this->replaceRscriptParameter('celltype_markers', $parameters['celltype_markers'], $scriptContents);
            $scriptContents = $this->replaceRscriptParameter('included_celltype_markers', '', $scriptContents);
        }
        elseif(strlen($parameters['uploaded_celltype_markers']) && $parameters['uploaded_celltype_markers'] === "1") {
            if (request()->hasFile('uploaded_celltype_markers_file') && request()->file('uploaded_celltype_markers_file')->isValid()) {

            }
        }

        Storage::put($script, $scriptContents);

        $selected_k = "sample_name,suggested_k\n";
        foreach ($parameters['selected_k'] as $sample => $k) {
            $selected_k .= $sample . ',' . $k . "\n";
        }
        Storage::put($workingDir . 'stdeconvolve_selected_k.csv', $selected_k);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $_process_files = [];

        $topic_annotations = [];
        foreach ($this->samples as $sample) {
            $fileName = 'topic_annotations_' . $sample->name . '.csv';
            $file = $workingDir . $fileName;
            $sample_topics = [];
            if (Storage::fileExists($file)) {
                $data = trim(Storage::read($file));
                $data = str_replace('"', '', $data);
                foreach (preg_split("/((\r?\n)|(\r\n?))/", $data) as $line) {
                    $tmp = explode(',', $line);
                    $plotName = "stdeconvolve2_topic_logfc_" . $sample->name . "_" . $tmp[0];
                    $sample_topics[$tmp[0]] = ["annotation" => $tmp[1], "new_annotation" => $tmp[2], "plot" => $this->workingDirPublicURL() . $plotName];
                    $_process_files[] = $plotName . '.pdf';
                }
                array_shift($sample_topics);

                $file_public = $this->workingDirPublic() . $fileName;
                Storage::delete($file_public);
                Storage::move($file, $file_public);
                $_process_files[] = $fileName;

            }
            $topic_annotations[$sample->name] = $sample_topics;
        }


        $gsea_results = [];
        if (count($topic_annotations)) {

            $column_names = [
                'gene_set' => 'Gene set',
                'p_val' => 'p-value',
                'q_val' => 'q-value',
                'sscore' => 'Enrichment score'
            ];

            foreach($topic_annotations as $sampleName => $topics) {
                $gsea_results[$sampleName] = [];
                foreach($topics as $topicName => $topic) {

                    $fileName = 'gsea_results_' . $sampleName . '_' . $topicName . '.csv';
                    $file = $this->workingDir() . $fileName;

                    if (Storage::fileExists($file)) {

                        $contents = Storage::read($file);
                        $contents = str_replace('p.val', 'p_val', $contents);
                        $contents = str_replace('q.val', 'q_val', $contents);
                        Storage::put($file, $contents);

                        $gsea_results[$sampleName][$topicName] = json_decode($this->csv2json($file, 0, $column_names, true));

                        $file_public = $this->workingDirPublic() . $fileName;
                        Storage::delete($file_public);
                        Storage::move($file, $file_public);
                        $_process_files[] = $fileName;
                    }
                }
            }
        }

        $_scatterpie_plots = $this->STdeconvolveMovePlotsToPublic();
        $scatterpie_plots = $_scatterpie_plots['scatterpie_plots'];
        $_process_files = array_merge($_process_files, $_scatterpie_plots['scatterpie_plots_names']);

        ProjectParameter::updateOrCreate(['parameter' => 'STdeconvolve2', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'logfold_plots' => $topic_annotations, 'scatterpie_plots' => $scatterpie_plots, 'gsea_results' => $gsea_results])]);
        ProjectProcessFiles::updateOrCreate(['process' => 'STdeconvolve2', 'project_id' => $this->id], ['files' => json_encode($_process_files)]);

        return ['output' => $output, 'script' => $scriptContents];
    }

    private function STdeconvolveMovePlotsToPublic() {
        $workingDir = $this->workingDir();
        $file = $workingDir . 'stdeconvolve2_logfold_plots.csv';
        if (Storage::fileExists($file)) {
            $data = explode(',', trim(Storage::read($file)));
            foreach ($data as $plot) {
                $file_extensions = ['svg', 'pdf', 'png'];

                foreach ($file_extensions as $file_extension) {
                    $fileName = $plot . '.' . $file_extension;
                    $file = $workingDir . $fileName;
                    $file_public = $this->workingDirPublic() . 'stdeconvolve2_' . $fileName;
                    if (Storage::fileExists($file)) {
                        Storage::delete($file_public);
                        Storage::move($file, $file_public);
                    }
                }
            }
        }

        $file = $workingDir . 'stdeconvolve2_scatterpie_plots.csv';
        $scatterpie_plots = [];
        $scatterpie_plots_names = [];
        if (Storage::fileExists($file)) {
            $data = explode(',', trim(Storage::read($file)));
            foreach ($data as $plot) {

                $scatterpie_plots[] = $this->workingDirPublicURL() . 'stdeconvolve2_' . $plot;
                $file_extensions = ['svg', 'pdf', 'png'];

                foreach ($file_extensions as $file_extension) {
                    $fileName = $plot . '.' . $file_extension;
                    $file = $workingDir . $fileName;
                    $file_public = $this->workingDirPublic() . 'stdeconvolve2_' . $fileName;
                    if (Storage::fileExists($file)) {
                        Storage::delete($file_public);
                        Storage::move($file, $file_public);

                        if($file_extension === 'pdf') {
                            $scatterpie_plots_names[] = 'stdeconvolve2_' . $fileName;
                        }

                    }
                }
            }
        }

        return compact('scatterpie_plots', 'scatterpie_plots_names');
    }

    private function getSTdeconvolve2Script($parameters)
    {

        $script = Storage::get("/common/templates/STdeconvolve2_biological_identification.R");
        $params = ['q_val', 'user_radius', 'color_pal'];
        foreach ($params as $param) {
            $script = $this->replaceRscriptParameter($param, $parameters[$param], $script);
        }
        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);

        return $script;
    }


    public function STdeconvolve3($parameters)
    {
        $data = $parameters['logfold_plots'];

        throw_if(!is_array($data), new Exception("Error processing new topic annotations for project " . $this->id));

        //read parameter value from DB
        $STdeconvolve2 = $this->parameters()->where('parameter', 'STdeconvolve2')->firstOrFail();
        $project_param = json_decode($STdeconvolve2->value);

        $newLine = "\n";
        $q = '"';
        $annotations = [];
        foreach($data as $sampleName => $topics) {
            $annotations[$sampleName] = '"topic","annotation","new_annotation"' . $newLine;
            foreach($topics as $topicName => $topic) {
                $annotations[$sampleName] .= $q . $topicName . $q . ',' . $q . $topic['annotation'] . $q . ',' . $q . $topic['current_annotation'] . $q . $newLine;
                $project_param->logfold_plots->$sampleName->$topicName->new_annotation = $topic['current_annotation'];
            }
        }

        //update paramaters in DB
        $STdeconvolve2->value = json_encode($project_param);
        $STdeconvolve2->save();

        foreach($annotations as $sampleName => $data) {
            Storage::put($this->workingDir() . 'topic_annotations_' . $sampleName . '.csv', $data);
        }

        $workingDir = $this->workingDir();

        $scriptName = 'STdeconvolve3_rename_topics.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTdeconvolve3Script();
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $this->STdeconvolveMovePlotsToPublic();

        return ['output' => $output, 'script' => $scriptContents];
    }

    private function getSTdeconvolve3Script()
    {

        $script = Storage::get("/common/templates/STdeconvolve3_rename_topics.R");
        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);

        return $script;
    }




    public function InSituType($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'InSituType.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getInSituTypeScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $file = $workingDir . 'insitutype_results.RData';
        if (Storage::fileExists($file)) {
            ProjectParameter::updateOrCreate(['parameter' => 'InSituType', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters])]);
        }

        $this->current_step = 8;
        $this->save();

        return ['output' => $output, 'script' => $scriptContents];
    }

    private function getInSituTypeScript($parameters)
    {

        $script = Storage::get("/common/templates/InSituType1.R");

        $_stlist = 'stclust_stlist';
        if (!Storage::fileExists($this->workingDir() . "$_stlist.RData")) $_stlist = 'normalized_stlist';

        $_parameters = [];
        $_parameters['_species'] = explode('-',$parameters['cell_profile'])[0];
        $_parameters['_cell_prof_db'] = explode('-',$parameters['cell_profile'])[1];
        $_parameters['_refine_cells'] = $parameters['refine_cells'] ? 'T' : 'F';
        $_parameters['_stlist'] = $_stlist;

        $params = ['_species', '_cell_prof_db', '_refine_cells', '_stlist'];
        foreach ($params as $param) {
            $script = $this->replaceRscriptParameter($param, $_parameters[$param], $script);
        }

        return $script;
    }

    public function InSituType2($parameters)
    {

        $workingDir = $this->workingDir();

        $scriptName = 'InSituType2.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getInSituType2Script($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        $file = $workingDir . 'insitutype_results.RData';
        if (Storage::fileExists($file)) {

            $workingDirPublic = $this->workingDirPublicURL();

            $samples = $this->getSampleList($parameters['samples'], true);
            $plots = [];
            foreach($samples as $sample) {

                $plot = 'insitutype_plot_spatial_' . $sample . '_insitutype_cell_types';
                $plots[$sample] = $workingDirPublic . $plot;

                $file_extensions = ['svg', 'pdf', 'png'];
                foreach ($file_extensions as $file_extension) {
                    $fileName = $plot . '.' . $file_extension;
                    $file = $workingDir . $fileName;
                    $file_public = $this->workingDirPublic() . $fileName;
                    if (Storage::fileExists($file)) {
                        Storage::delete($file_public);
                        Storage::move($file, $file_public);
                    }
                }
            }

            $files = ['insitutype_flightpath', 'insitutype_umap'];
            foreach($files as $plot) {

                $plots[$plot] = $workingDirPublic . $plot;

                $file_extensions = ['svg', 'pdf', 'png'];
                foreach ($file_extensions as $file_extension) {
                    $fileName = $plot . '.' . $file_extension;
                    $file = $workingDir . $fileName;
                    $file_public = $this->workingDirPublic() . $fileName;
                    if (Storage::fileExists($file)) {
                        Storage::delete($file_public);
                        Storage::move($file, $file_public);
                    }
                }
            }

            $this->stdiff_top_deg('insitutype_cell_types_top_deg.csv');


            ProjectParameter::updateOrCreate(['parameter' => 'InSituType2', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['plots' => $plots, 'parameters' => $parameters])]);
        }

        return ['output' => $output, 'script' => $scriptContents];
    }

    private function getInSituType2Script($parameters)
    {
        $script = Storage::get("/common/templates/InSituType2.R");

        $samples =  $this->getSampleList($parameters['samples']);

        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);
        $script = $this->replaceRscriptParameter('_color_palette_function', $this->getColorPaletteFunctionRscript(), $script);
        $script = $this->replaceRscriptParameter('_samples', $samples, $script);

        $params = ['color_pal', 'ptsize'];
        foreach ($params as $param) {
            $script = $this->replaceRscriptParameter($param, $parameters[$param], $script);
        }

        return $script;
    }



    public function InSituTypeRename($parameters)
    {
        $workingDir = $this->workingDir();

        $result = $this->saveSTdiffAnnotationChanges($parameters['annotations']);

        $parameters['_samples'] = "c('" . join("','", $result['samples']) . "')";
        $parameters['_annotations'] = "c('" . join("','", $result['annotations']) . "')";


        $scriptName = 'InSituTypeRename.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getInSituTypeRenameScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);


        foreach($parameters['annotations'] as $change) {
            $file_extensions = ['svg', 'pdf', 'png'];
            $plot_file = 'insitutype_plot_spatial_' . $change['sampleName'] . '_' . $change['originalName'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $plot_file . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                }
            }
        }

        return ['output' => $output, 'script' => $scriptContents];
    }

    private function getInSituTypeRenameScript($parameters)
    {

        $script = Storage::get("/common/templates/InSituType_rename.R");

        //If there's no stclust_stlist use the normalized_stlist
        $_stlist = 'stclust_stlist';
        if (!Storage::fileExists($this->workingDir() . "$_stlist.RData")) $_stlist = 'normalized_stlist';
        $parameters['_stlist'] = $_stlist;

        $params = ['_stlist', '_samples', '_annotations'];
        foreach ($params as $param) {
            $script = $this->replaceRscriptParameter($param, $parameters[$param], $script);
        }

        $script = $this->replaceRscriptParameter('HEADER', $this->getSavePlotFunctionRscript(), $script);

        return $script;
    }


    private function getDefaultSamplesToProcess() {
        $workingDir = $this->workingDir();
        $samples_fovs = Storage::get($workingDir . "slide_x_fov_table.csv");

        // Convert the CSV string to an array of rows
        $rows = explode("\n", $samples_fovs);

        // Extract column names from the first row
        $columns = explode(",", array_shift($rows));

        // Initialize an empty array to store data
        $data = [];

        // Process each row and create associative array
        foreach ($rows as $row) {
            if(!strlen(trim($row))) continue;
            // Parse the row into an associative array
            $rowData = array_combine($columns, explode(",", $row));
            // Add the row data to the main array
            $data[] = $rowData;
        }

        $slides = array_unique(array_column($data, 'slide'));
        $max_fovs = count($slides) > 4 ? 2 : 3;

        $samples = [];
        foreach($slides as $slide) {
            $n_fovs = 0;
            foreach($data as $row) {
                if($row['slide'] === $slide) {
                    $samples[] = $row['fov_name'];
                    $n_fovs++;
                }
                if($n_fovs === $max_fovs) break;
            }
        }

        return $samples;

    }

    private function getSampleList($_samples, $as_array = false) {

        $samples = 'NULL';
        if(is_array($_samples) && count($_samples)) {
            $samples = $_samples;
        }
        else {
            //$samples = "c('" . $this->samples()->pluck('samples.name')->join("','") . "')";

            if(!$this->isCosmxPlatform()) {
                $samples = $this->samples()->pluck('samples.name')->toArray();
            } else {
                $samples = $this->getDefaultSamplesToProcess();
            }

            //$samples = "c('" . implode("','", $samples) . "')";
        }
        $samples = count($samples) > 10 ? array_slice($samples, 0, 10) : $samples;

        return $as_array ? $samples : $samples = "c('" . join("','", $samples) . "')";

    }


    private function getSavePlotFunctionRscript()
    {

        return Storage::get("/common/templates/_save_plots.R");
    }

    private function getColorPaletteFunctionRscript()
    {

        return Storage::get("/common/templates/_get_color_palette.R");
    }

    private function replaceRscriptParameter($parameter, $value, $script)
    {
        return str_replace('#{' . $parameter . '}#', $value, $script);
    }

    private function samplesWithTissue()
    {
        $samples_with_tissue = '';
        foreach ($this->samples as $sample) {
            if ($sample->has_image) {
                if (strlen($samples_with_tissue)) $samples_with_tissue .= ',';
                $samples_with_tissue .= "'" . $sample->name . "'";
            }
        }
        return $samples_with_tissue;
    }

    private function getExportFilesCommands($file, $plot, $width = 800, $height = 600): string
    {

        //if $fileName doesn't seem to contain R code, add quotes, so it can be treated as a string
        if (!preg_match('/[\(\)\[\]]/', $file))
            $file = "'" . $file . "'";

        $str = "if(!is.null($plot)){" . PHP_EOL;

        //PNG
        $str .= "ggpubr::ggexport(filename = paste0($file,'.png'), $plot, width = $width, height = $height)" . PHP_EOL;

        //PDF
        $str .= "ggpubr::ggexport(filename = paste0($file,'.pdf'), $plot, width = " . intval($width / 100) . ", height = " . intval($height / 100) . ")" . PHP_EOL;

        //SVG
        $str .= "library('svglite')" . PHP_EOL;
        $str .= "svglite(paste0($file,'.svg'), width = " . intval($width / 100) . ", height = " . intval($height / 100) . ")" . PHP_EOL;
        $str .= "print($plot)" . PHP_EOL;
        $str .= "dev.off()" . PHP_EOL;

        $str .= "}" . PHP_EOL . PHP_EOL;

        return $str;
    }


    public function createJob($description, $command, $parameters, $queue = 'default'): int
    {

        Log::info("---------1--");

        $min_delay = env('QUEUE_INITIAL_DELAY_SECONDS_MIN', 5);
        $max_delay = env('QUEUE_INITIAL_DELAY_SECONDS_MAX', 7);

        $startAt = now()->addSeconds(rand($min_delay, $max_delay));

        if (!isset($parameters['__task'])) {

            $project_id = $this->id;

            //Create a unique name or id for the task based on the current timestamp
            $parameters['__task'] = 'spatialGE_' . $this->user->id . '_' . $this->id . '_' . now()->format('YmdHis_u');

            //Information necessary to run the job again in case it fails
            $payload = json_encode(compact('description', 'project_id', 'command', 'parameters', 'queue'));

            //insert record in Tasks table to gather statistics
            Task::create(['task' => $parameters['__task'], 'project_id' => $this->id, 'samples' => $this->samples->count(), 'user_id' => $this->user->id, 'process' => $command, 'payload' => $payload]);

            Log::info("---------2--");
        } else {
            Log::info("---------3--");
            $task = Task::where('task', $parameters['__task'])->firstOrFail();
            $task->attempts++;
            $task->save();

            $min_delay = env('QUEUE_FAILED_JOB_DELAY_MINUTES_MIN', 12);
            $max_delay = env('QUEUE_FAILED_JOB_DELAY_MINUTES_MAX', 16);
            $startAt = now()->addMinutes(rand($min_delay, $max_delay) * ($task->attempts - 1));
        }

        //create the job instance
        $job = new RunScript($description, $this, $command, $parameters);

        //push the job to que queue and get the jobId
        $jobId = Queue::connection()->laterOn($queue, $startAt, $job);   //$jobId = Queue::connection()->pushOn($queue, $job);

        //save the jobId to the project parameters table
        ProjectParameter::updateOrCreate(['parameter' => 'job.' . $command, 'project_id' => $this->id], ['type' => 'number', 'value' => $jobId]);
        //Set the email notification to off by default
        $this->setJobEmailNotification($command, -1);

        return $jobId;
    }



    private function getMemoryLoad(): float
    {

        $latestTimestamp = TaskStat::max('timestamp');

        $latestJobs = TaskStat::where('timestamp', $latestTimestamp)->get();

        if (!$latestJobs->count()) return 0;

        $taskIds = $latestJobs->pluck('task');

        $stillRunningJobs = Task::whereIn('task', $taskIds)->whereNull('finished_at')->whereNull('cancelled_at')->where('completed', 0);

        if (!$stillRunningJobs->count()) return 0;

        $memoryUsage = array_sum($latestJobs->pluck('memory')->toArray());

        $memoryThreshold = intval(env('MEMORY_THRESHOLD', 28 * 1024));

        return round($memoryUsage / $memoryThreshold, 1);
    }


    private function getJobClassification($command, $parameters)
    {

        $lightJobs = [
            'generateFilterPlots',
            'generateNormalizationPlots',
            'pcaPlots',
            'quiltPlot',
            'STplotQuilt',
            'STplotExpressionSurfacePlots',
            'SThetPlot',
        ];
    }

    public function setJobEmailNotification($command, $sendEmail)
    {

        if ($sendEmail === -1) {
            $param = ProjectParameter::where('parameter',  'job.' . $command . '.email')->where('project_id', $this->id)->get();
            $sendEmail = $param->count() ? $param[0]->value : 0;
        }

        ProjectParameter::updateOrCreate(['parameter' => 'job.' . $command . '.email', 'project_id' => $this->id], ['type' => 'number', 'value' => $sendEmail ? 1 : 0]);
    }

    public function csv2json($file, $column_offset = 2, $column_names = [], $return_string = false)
    {
        $data = Storage::read($file);
        $lines = explode("\n", $data);
        $headers = [];
        $body = [];
        if (sizeof($lines) >= 2) {
            //process the headers
            $fields = explode(',', $lines[0]);
            if (sizeof($fields) > $column_offset) {
                for ($i = $column_offset; $i < sizeof($fields); $i++) {
                    $fields[$i] = str_replace('"', '', $fields[$i]);
                    $headers[] = '{ "value": "' . $fields[$i] . '", "text": "' . (array_key_exists($fields[$i], $column_names) ? $column_names[$fields[$i]] : $fields[$i]) . '", "sortable": "true" }';
                }

                //process the body
                for ($k = 1; $k < sizeof($lines); $k++) {
                    if (strlen($lines[$k])) {
                        $body_line = '{';
                        $body_items = explode(',', $lines[$k]);
                        if (sizeof($fields) === sizeof($body_items))
                            for ($i = $column_offset; $i < sizeof($fields); $i++) {
                                if (strlen($body_line) > 1) $body_line .= ',';

                                //if numeric value, round it up to 3 decimal places
                                $value = $body_items[$i];
                                if (is_numeric($value) && (stripos($value, 'e') || (abs(floatval($value)) < 0.001 && abs(floatval($value)) > 0)))
                                    $value  = sprintf("%.3e", $value);
                                elseif (is_numeric($value))
                                    $value = round($value, 3);

                                //if($fields[$i] === 'gene')
                                //    $value = '<a href="https://www.genecards.org/cgi-bin/carddisp.pl?gene=' . $value . '" target="_blank">';

                                //wrap everything in quotes to prevent javascript from auto-formatting scientific notation
                                $value = '"' . str_replace('"', '', $value) . '"';

                                $body_line .= '"' . $fields[$i] . '":' . $value;
                            }
                        $body_line .= '}';
                        $body[] = $body_line;
                    }
                }
            }
        }
        $contents = '{' . "\n" . '"headers": [' . "\n" . implode(",\n", $headers) . '],' . "\n" . '"items": [' . "\n" . implode(",\n", $body) . "\n" . ']' . "\n" . '}';

        if($return_string) return $contents;

        $_parts = explode('.', $file);
        array_pop($_parts);
        Storage::put(join('.', $_parts) . '.json', $contents);
    }

    public function getJobPositionInQueue($jobId): int
    {

        try {

            $job = Job::findOrFail($jobId);

            if ($job->isRunning()) return 1; //Job running, return 1 to indicate that it is first in line and being run

            return $job->currentPosition();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function cancelJobInQueue($jobId, $task): int
    {

        $job = null;

        try {
            $job = Job::findOrFail($jobId);

            //Kill the container, if running
            $spatial = new spatialContainer($this);
            $spatial->killProcess($task->task);
            sleep(1);
            //Try to delete the job from the queue, if still there
            $job->delete();

            /*if(!$job->isRunning()) {
                $job->delete();
            }
            else {
                $spatial = new spatialContainer($this);
                $spatial->killProcess($task->task);
            }*/

            return 1;
        } catch (\Exception $e) {
            Log::error('Cancelling job with id: ' . $jobId . (!is_null($task) ? ' - container id: ' . $task->id : ''));
            return 0;
        }
    }

    public function getJobsInQueue($except = '')
    {
        try {

            //Get all projectIds for the current user
            $projectIds = $this->user->projects->pluck('id');

            //Get jobIds for all the user's processes
            $jobIds = ProjectParameter::whereIn('project_id', $projectIds)->where('parameter', 'REGEXP', '^job\.[a-zA-Z0-9_]+$');
            if (strlen($except))
                $jobIds = $jobIds->whereNot('parameter', 'job.' . $except);

            $jobIds = $jobIds->get()->pluck('value');

            return Job::whereIn('id', $jobIds)->get()->count();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function clusterTestSTEnrich($gene_set)
    {

        try {

            $sharedFolderBase = env('HPC_FOLDER');

            if (!File::isDirectory($sharedFolderBase . $this->id)) {
                File::makeDirectory($sharedFolderBase . $this->id);
            }


            $replace = '

local_library_path <- "/home/4476777/R_libraries"
.libPaths(c(local_library_path, .libPaths()))
setwd("/share/dept_bbsr/Projects/Manjarres_Betancur_Roberto/spatialGE/1")

            ';

            $text = Storage::get($this->workingDir() . 'STEnrich.R');
            $text = str_replace("setwd('/spatialGE')", $replace, $text);
            File::put(Storage::path($this->workingDir() . 'STEnrich2.R'), $text);

            $filesToCopy = ['STEnrich2.R', 'normalized_stlist.RData', $gene_set /*'hallmark.gmt'*/];

            foreach ($filesToCopy as $file) {

                $source = Storage::path($this->workingDir() . $file);
                $destination = $sharedFolderBase . $this->id . '/' . $file;

                if (File::exists($source)) {

                    $command = 'copy ' . $source . ' ' . $destination;

                    $command = str_replace('/', '\\', $command);

                    Log::info('=================>  ' . $command);

                    $process = Process::run($command);
                }

                //File::copy(Storage::path($this->workingDir() . $file), $sharedFolderBase . $this->id . '/' . $file);
            }

            $command = 'copy ' . $sharedFolderBase . 'template.spatialGE.sub' . ' ' . $sharedFolderBase . 'run.spatialGE.sub';
            $command = str_replace('/', '\\', $command);
            $process = Process::run($command);

            return

                $text = Storage::get($this->workingDir() . 'STplot-ExpressionSurface.R');
            File::put($sharedFolderBase . $this->id . '/' . 'STplot-ExpressionSurface.R', $text);
        } catch (\Exception $e) {
            Log::error('Error while copying files to the HPC:');
            Log::error($e->getMessage());
        }
    }
    private function clusterTestSTDiffNonSpatial()
    {

        try {

            $sharedFolderBase = env('HPC_FOLDER');

            if (!File::isDirectory($sharedFolderBase . $this->id)) {
                File::makeDirectory($sharedFolderBase . $this->id);
            }


            $replace = '

local_library_path <- "/home/4476777/R_libraries"
.libPaths(c(local_library_path, .libPaths()))
setwd("/share/dept_bbsr/Projects/Manjarres_Betancur_Roberto/spatialGE/' . $this->id . '")

            ';

            $text = Storage::get($this->workingDir() . 'STDiff_NonSpatial.R');
            $text = str_replace("setwd('/spatialGE')", $replace, $text);
            File::put(Storage::path($this->workingDir() . 'STDiff_NonSpatial2.R'), $text);

            $filesToCopy = ['STDiff_NonSpatial2.R', 'stclust_stlist.RData'];

            foreach ($filesToCopy as $file) {

                $source = Storage::path($this->workingDir() . $file);
                $destination = $sharedFolderBase . $this->id . '/' . $file;

                if (File::exists($source)) {

                    $command = 'copy ' . $source . ' ' . $destination;

                    $command = str_replace('/', '\\', $command);

                    Log::info('=================>  ' . $command);

                    $process = Process::run($command);
                }

                //File::copy(Storage::path($this->workingDir() . $file), $sharedFolderBase . $this->id . '/' . $file);
            }

            $command = 'copy ' . $sharedFolderBase . 'template.spatialGE.sub' . ' ' . $sharedFolderBase . 'run.spatialGE.sub';
            $command = str_replace('/', '\\', $command);
            $process = Process::run($command);

            return

                $text = Storage::get($this->workingDir() . 'STplot-ExpressionSurface.R');
            File::put($sharedFolderBase . $this->id . '/' . 'STplot-ExpressionSurface.R', $text);
        } catch (\Exception $e) {
            Log::error('Error while copying files to the HPC:');
            Log::error($e->getMessage());
        }
    }

    private function getHPCscript($jobName, $scriptName, $cpus = 5, $ram = '32G', $time = '24:00:00')
    {

        $template = Storage::get('common/templates/spatialGE.sub');

        $template = str_replace('{{job-name}}', $jobName, $template);

        $template = str_replace('{{script-name}}', $scriptName, $template);

        $template = str_replace('{{project-id}}', $this->id, $template);

        $template = str_replace('{{CPUs}}', $cpus, $template);

        $template = str_replace('{{RAM}}', $ram, $template);

        $template = str_replace('{{time}}', $time, $template);

        $template = str_replace('{{hpc-local-folder}}', env('HPC_LOCAL_FOLDER'), $template);

        return $template;
    }

    private function sendJobHPC($jobName, $scriptName, $filesToCopy)
    {

        try {

            $sharedFolderBase = env('HPC_FOLDER');

            if (!File::isDirectory($sharedFolderBase . $this->id)) {
                File::makeDirectory($sharedFolderBase . $this->id);
            }


            /************ create R script to RUN in the HPC ****************/
            $replace = '
local_library_path <- "/home/4476777/R_libraries"
.libPaths(c(local_library_path, .libPaths()))
setwd("/share/dept_bbsr/Projects/Manjarres_Betancur_Roberto/spatialGE/' . $this->id . '")
options(bitmapType="cairo")
            ';
            $scriptNameHPC = $jobName . '_' . $scriptName . '_HPC';
            $text = Storage::get($this->workingDir() . $scriptName . '.R');
            $text = str_replace("setwd('/spatialGE')", $replace, $text);
            Storage::put($this->workingDir() . $scriptNameHPC . '.R', $text);
            /******************* end R script*******************************************/


            /************ create SLURM script to RUN in the HPC ****************/
            $text = $this->getHPCscript($jobName, $scriptNameHPC, $this->samples->count(), (($this->samples->count() + 1) * 2) . 'G');
            $slurmScriptNameHPC = $jobName . '_' . $scriptName . '_HPC.sub';
            Storage::put($this->workingDir() . $slurmScriptNameHPC, $text);
            /******************* end SLURM script*******************************************/

            $filesToCopy[] = $scriptNameHPC . '.R';
            $filesToCopy[] = $slurmScriptNameHPC;

            foreach ($filesToCopy as $file) {

                $source = Storage::path($this->workingDir() . $file);
                $destination = $sharedFolderBase . $this->id . '/' . $file;

                if (File::exists($source)) {

                    $command = 'copy ' . $source . ' ' . $destination;

                    $command = str_replace('/', '\\', $command);

                    Log::info('=================>  ' . $command);

                    $process = Process::run($command);
                }

                //File::copy(Storage::path($this->workingDir() . $file), $sharedFolderBase . $this->id . '/' . $file);
            }

            $taskFileName = $this->id . '.' . $jobName . '_' . $scriptName . '_HPC.spatialGE';
            $command = 'copy ' . $sharedFolderBase . 'template.spatialGE.sub' . ' ' . $sharedFolderBase . $taskFileName;
            $command = str_replace('/', '\\', $command);
            $process = Process::run($command);

            return;

            /*$text = Storage::get($this->workingDir() . 'STplot-ExpressionSurface.R');
            File::put($sharedFolderBase . $this->id . '/' . 'STplot-ExpressionSurface.R', $text);*/
        } catch (\Exception $e) {
            Log::error('Error while copying files to the HPC:');
            Log::error($e->getMessage());
        }
    }
}

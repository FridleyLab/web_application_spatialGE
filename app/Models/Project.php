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
use Monolog\Logger;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects';

    protected $fillable = ['name', 'description', 'user_id', 'project_platform_id'];

    protected $appends = ['url', 'assets_url', 'project_parameters', 'platform_name'];

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

    public function getPlatformNameAttribute()
    {

        if ($this->project_platform_id === 1)
            return 'VISIUM';
        elseif ($this->project_platform_id === 8)
            return 'GENERIC';


        return 'UNKNOWN';
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

        $fileName = $this->workingDir() . 'stdiff_annotation_variables.csv';
        if (Storage::fileExists($fileName)) {
            $data = Storage::read($fileName);
            $lines = explode("\n", $data);
            sort($lines);
            $annotations = [];
            foreach ($lines as $annotation) {
                if (strlen(trim($annotation))) {
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

                    if ($label === '') $label = $annotation;

                    $annotations[] = ['label' => $label, 'value' => $annotation];
                }
            }
            sort($annotations);
            $params['annotation_variables'] = $annotations;
        }

        $fileName = $this->workingDir() . 'stdiff_annotation_variables_clusters.csv';
        if (Storage::fileExists($fileName)) {
            $data = Storage::read($fileName);
            $lines = explode("\n", $data);
            $annotations_clusters = [];
            foreach ($lines as $annotation) {
                if (strlen(trim($annotation))) {
                    $values = explode(',', $annotation);
                    $annotations_clusters[] = ['annotation' => $values[0], 'cluster' => $values[1]];
                    //$annotations_clusters[] = ['annotation' => $values[0], 'cluster' => $values[1]];
                }
            }
            $params['annotation_variables_clusters'] = $annotations_clusters;
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

        return $params;
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

    private function createGeneList($genes_file, $context)
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

        $expressionFileExtension = $this->samples[0]->expression_file->extension;

        if ($expressionFileExtension === 'h5') {

            $countFiles = $this->samples()->pluck('samples.name')->join("/','");
            $countFiles = "'" . $countFiles . "/'";

            $createSTlistCommand = 'initial_stlist <- STlist(rnacounts=count_files, samples=samplenames)';
        } else if (in_array($expressionFileExtension, ['csv', 'txt', 'tsv'])) {

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



        $result = [];


        $result['filter_meta_options'] = $this->filter_meta_options();


        $result['pca_max_var_genes'] = $this->pca_max_var_genes();

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
                    $result[$parameterName] = $this->workingDirPublicURL() . $parameterName;
                }
            }
        }

        $parameterName = 'filtered_stlist_summary';
        $file = $workingDir . $parameterName . '.csv';
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id, 'tag' => 'filter'], ['type' => 'string', 'value' => $data]);
            $result[$parameterName] = $data;
        }


        ProjectParameter::updateOrCreate(['parameter' => 'applyFilter', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters])]);

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

        $samples = $parameters['samples'];
        if(is_array($samples) && count($samples)) {
            $samples = "c('" . join("','", $samples) . "')";
        }
        else {
            $samples = "c('" . $this->samples()->pluck('samples.name')->join("','") . "')";
        }

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
vp = distribution_plots(filtered_stlist, plot_meta='total_counts', color_pal='" . ($this->samples->count() < 12 ? "Spectral" : "smoothrainbow") . "')
#ggpubr::ggexport(filename = 'filter_violin.png', vp, width = 800, height = 800)

#### Box plot
bp = distribution_plots(filtered_stlist, plot_meta='total_counts', color_pal='" . ($this->samples->count() < 12 ? "Spectral" : "smoothrainbow") . "', plot_type='box')
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
                }
            }
        }

        return ['output' => $output, 'script' => $scriptContents];
    }


    public function getFilterPlotsScript($parameters): string
    {

        $color_palette = $parameters['color_palette'];
        $variable = $parameters['variable'];


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
vp = distribution_plots(filtered_stlist, plot_meta='$variable', color_pal='$color_palette')
#ggpubr::ggexport(filename = 'filter_violin.png', vp, width = 800, height = 800)

#### Box plot
bp = distribution_plots(filtered_stlist, plot_meta='$variable', color_pal='$color_palette', plot_type='box')
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
            $workingDir = Storage::path($this->workingDir());
        }

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
                $file_public = Storage::path($this->workingDirPublic()) . $fileName;
                Log::info('Checking if exists ==> ' . $file);
                if (($HPC && file_exists($file)) || Storage::fileExists($file)) {

                    Log::info('Exists! ==> ' . $file);

                    //Delete, if exists, any previously generated file in the public folder
                    if (Storage::fileExists($file_public)) { Storage::delete($file_public); }

                    if($HPC) {
                        //if (file_exists($file_public)) unlink($file_public);

                        //copy($file, $file_public);
                        $command = 'copy ' . $file . ' ' . $file_public;
                        $command = str_replace('/', '\\', $command); //TODO: works only on Windows
                        Log::info('=================>  ' . $command);
                        $process = Process::run($command);
                    } else {
                        Log::info($file . '   ==>   ' . $file_public);
                        Storage::move($file, $file_public);
                    }

                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id, 'tag' => 'normalize'], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                    $result[$parameterName] = $this->workingDirPublicURL() . $parameterName;
                }
            }
        }

        $result['pca_max_var_genes'] = $this->pca_max_var_genes();

        //Delete (if any) previously generated normalized data, the user has to generate it again from the interface
        ProjectParameter::where('parameter', 'normalizedData')->where('project_id', $this->id)->delete();


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
den_raw = plot_counts(normalized_stlist, distrib_subset=0.01, data_type='raw', plot_type=c('density', 'violin', 'box'))
den_tr = plot_counts(normalized_stlist, distrib_subset=0.01, plot_type=c('density', 'violin', 'box'))

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
                }
            }
        }

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

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList

{$this->_loadStList($stlist)}

#### Violin plot
vp = distribution_plots($stlist, color_pal='$color_palette', data_type='tr', genes='$gene')
#### Box plot
bp = distribution_plots($stlist, color_pal='$color_palette', plot_type='box', data_type='tr', genes='$gene')

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
                }
            }
        }

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
                }
            }
        }

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
                        }
                    }
                }
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stplot_quilt', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode($result)]);

        return ['output' => $output, 'script' => $scriptContents];
    }

    public function getSTplotQuiltScript($parameters): string
    {

        $genes = $parameters['genes'];
        $ptsize = $parameters['ptsize'];
        $col_pal = $parameters['col_pal'];
        $data_type = $parameters['data_type'];

        $_genes = "c('" . join("','", $genes) . "')";

        $export_files = '';
        $export_files_side_by_side = '';
        foreach ($genes as $gene)
            foreach ($this->samples as $sample) {
                $export_files .= $this->getExportFilesCommands("stplot-quilt-$gene-" . $sample->name, "qp\$" . $gene . "_" . $sample->name);
                if ($sample->has_image) {
                    $export_files_side_by_side .= "tp = cowplot::ggdraw() + cowplot::draw_image('{$sample->image_file_path(true)}')" . PHP_EOL;
                    $export_files_side_by_side .= "qptp = ggpubr::ggarrange(qp\${$gene}_$sample->name, tp, ncol=2)" . PHP_EOL;
                    $export_files_side_by_side .= $this->getExportFilesCommands("stplot-quilt-$gene-" . $sample->name . '-sbs', 'qptp', 1400, 600) . PHP_EOL;
                }
            }

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList
" .
            $this->_loadStList('normalized_stlist')
            . "

qp = STplot(normalized_stlist, genes=$_genes, ptsize=$ptsize, color_pal='$col_pal', data_type='$data_type')

$export_files

$export_files_side_by_side

";

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
                    }
                }
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stplot_expression_surface', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode($result)]);

        return ['output' => $output, 'script' => $scriptContents];
        //return json_encode($result);
    }

    public function getSTplotExpressionSurfaceScript($parameters): string
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

{$this->_loadStList('normalized_stlist')}


stlist_expression_surface = gene_interpolation(normalized_stlist, genes=$_genes)

{$this->_saveStList('stlist_expression_surface')}

krp = STplot_interpolation(stlist_expression_surface, genes=$_genes, color_pal='$col_pal')

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
                    }
                }
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stplot_expression_surface', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode($result)]);

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
                }
            }
        }

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
                }
            }
        }

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


    public function STclust($parameters)
    {
        $workingDir = $this->workingDir();

        $scriptName = 'STclust.R';

        $script = $workingDir . $scriptName;

        $scriptContents = $this->getSTclustScript($parameters);
        Storage::put($script, $scriptContents);

        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);


        $file = $workingDir . 'stclust_plots.csv';
        if (Storage::fileExists($file)) {
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
                        }
                    }
                }
            }
            ProjectParameter::updateOrCreate(['parameter' => 'stclust', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'plots' => $plots])]);
        }

        $this->current_step = 8;
        $this->save();

        return ['output' => $output, 'script' => $scriptContents];
    }

    public function getSTclustScript($parameters): string
    {

        //If there's no stclust_stlist use the normalized_stlist
        $stlist = 'stclust_stlist';
        if (!Storage::fileExists($this->workingDir() . "$stlist.RData")) $stlist = 'normalized_stlist';

        $samples_with_tissue = '';
        foreach ($this->samples as $sample) {
            if ($sample->has_image) {
                if (strlen($samples_with_tissue)) $samples_with_tissue .= ',';
                $samples_with_tissue .= "'" . $sample->name . "'";
            }
        }


        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')
library('magrittr')

# Load normalized STList
{$this->_loadStList($stlist)}

stclust_stlist = STclust(x=$stlist,
                         ws={$parameters['ws']},
                         ks={$parameters['ks']},
                         topgenes={$parameters['topgenes']},
                         deepSplit={$parameters['deepSplit']})

#annot_variables used for Differential Expression analyses
annot_variables = unique(unlist(lapply(stclust_stlist@spatial_meta, function(i){ var_cols=grep('spagcn_|stclust_', colnames(i), value=T); return(var_cols) })))
write.table(annot_variables, 'stdiff_annotation_variables.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
##clusters_by_annot_variables used for Differential Expression analyses
cluster_values = tibble::tibble()
for(i in names(stclust_stlist@spatial_meta)){
  for(cl in grep('spagcn_|stclust_', colnames(stclust_stlist@spatial_meta[[i]]), value=T)){
    cluster_values = dplyr::bind_rows(cluster_values,
                                      tibble::tibble(cluster=unique(stclust_stlist@spatial_meta[[i]][[cl]])) %>%
                                        tibble::add_column(annotation=cl))
  }}
cluster_values = dplyr::distinct(cluster_values) %>%
  dplyr::select(annotation, cluster)
write.table(cluster_values, 'stdiff_annotation_variables_clusters.csv', quote=F, row.names=F, col.names=F, sep=',')


{$this->_saveStList('stclust_stlist')}

ps = STplot(x=stclust_stlist, ks={$parameters['ks']}, ws={$parameters['ws']}, ptsize=2, txsize=14, color_pal='smoothrainbow')
n_plots = names(ps)
write.table(n_plots, 'stclust_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
library('svglite')
for(p in n_plots) {
    #print(p)
    ggpubr::ggexport(filename = paste(p,'.png', sep=''), ps[[p]], width = 800, height = 600)
    ggpubr::ggexport(filename = paste(p,'.pdf', sep=''), ps[[p]], width = 8, height = 6)
    svglite(paste(p,'.svg', sep=''), width = 8, height = 6)
    print(ps[[p]])
    dev.off()

    #generate side-by-side for samples with tissue image
    for(sample in list($samples_with_tissue)) {
        if(grepl(sample, p, fixed=TRUE)) {
            tp = cowplot::ggdraw() + cowplot::draw_image(paste0(sample, '/spatial/image_', sample, '.png'))
            ptp = ggpubr::ggarrange(ps[[p]], tp, ncol=2)
            {$this->getExportFilesCommands("paste0(p, '-sbs')", 'ptp', 1400, 600)}
        }
    }
}
";

        return $script;
    }






    public function SpaGCN($parameters)
    {

        $workingDir = $this->workingDir();

        //Create simple STlist as input for SpaGCN
        $scriptName = 'SpaGCN_1_simpleSTlist.R';
        $script = $workingDir . $scriptName;
        $scriptContents = $this->getSpaGCN_SimpleSTlistScript();
        Storage::put($script, $scriptContents);
        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);

        //Run SpaGCN on the simple STList
        $scriptName = 'SpaGCN1.py';
        $scriptContents = Storage::get("/common/templates/$scriptName");
        $params = ['p', 'user_seed', 'number_of_domains_min', 'number_of_domains_max', 'refine_clusters'];
        foreach ($params as $param) {
            $scriptContents = str_replace("{param_$param}", $parameters[$param], $scriptContents);
        }
        $sampleList = "'" . $this->samples()->pluck('samples.name')->join("','") . "'";
        $scriptContents = str_replace("{param_sample_list}", $sampleList, $scriptContents);
        Storage::put("$workingDir/$scriptName", $scriptContents);
        $output .= $this->spatialExecute('python ' . $scriptName, $parameters['__task'], 'SPAGCN');

        //Import back results from SpaGCN
        $scriptName = 'SpaGCN_3_import.R';
        $script = $workingDir . $scriptName;
        $scriptContents = $this->getSpaGCN_ImportClassifications($parameters);
        Storage::put($script, $scriptContents);
        $output = $this->spatialExecute('Rscript ' . $scriptName, $parameters['__task']);


        $file = $workingDir . 'spagcn_plots.csv';
        if (Storage::fileExists($file)) {

            $zip = new \ZipArchive();
            $zipFileName = 'SpaGCN.zip';
            $addToZip = $zip->open(Storage::path($this->workingDirPublic() . $zipFileName), \ZipArchive::CREATE) == TRUE;

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

                            if ($addToZip) $zip->addFile(Storage::path($file_public), basename($file_public));
                        }
                    }
                }
            }

            $task = Task::where('task', $parameters['__task'])->firstOrFail();
            $parameterLog = json_decode($task->payload)->parameters;
            //unset($parameterLog['__task']);
            $logFileName = $this->workingDirPublicURL() . 'SpaGCN_execution_log.txt';
            Storage::put($logFileName, json_encode($parameterLog));

            if ($addToZip) {
                $zip->addFile(Storage::path($logFileName), basename($logFileName));
                $zip->close();
            }

            ProjectParameter::updateOrCreate(['parameter' => 'spagcn', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'plots' => $plots])]);
        }

        $this->current_step = 8;
        $this->save();



        return ['output' => $output, 'script' => $scriptContents];
    }



    public function getSpaGCN_SimpleSTlistScript(): string
    {

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList
{$this->_loadStList('normalized_stlist')}

for(i in names(normalized_stlist@tr_counts)){
    tr_counts = as.matrix(normalized_stlist@tr_counts[[i]])
    spatial_meta = normalized_stlist@spatial_meta[[i]][, 1:3]
    col_names = colnames(normalized_stlist@tr_counts[[i]])
    gene_names = rownames(normalized_stlist@tr_counts[[i]])
    sample_name = i
    save('tr_counts', 'spatial_meta', 'col_names', 'gene_names', 'sample_name',
         file=paste0(i, '_simplestlist_example.RData'))
  }

";
        return $script;
    }



    public function getSpaGCN_ImportClassifications($parameters): string
    {

        $col_pal = $parameters['col_pal'];

        $stlist = 'stclust_stlist';
        if (!Storage::fileExists($this->workingDir() . "$stlist.RData")) $stlist = 'normalized_stlist';

        $samples_with_tissue = '';
        foreach ($this->samples as $sample) {
            if ($sample->has_image) {
                if (strlen($samples_with_tissue)) $samples_with_tissue .= ',';
                $samples_with_tissue .= "'" . $sample->name . "'";
            }
        }

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')
library('magrittr')

# Load normalized STList
{$this->_loadStList($stlist)}

spagcn_preds = './'
spagcn_preds = list.files(spagcn_preds, pattern='spagcn_predicted_domains_sample_', full.names=T)

stclust_stlist = $stlist
for(i in names(stclust_stlist@spatial_meta)){
  spagcn_tmp = read.csv(grep(paste0(i, '.csv'), spagcn_preds, value=T))
  # Convert doain classifications to factor
  spagcn_tmp[, -1] = lapply(spagcn_tmp[, -1], as.factor)

  # Check for repeated columns in STlist
  duplicated_cols = colnames(stclust_stlist@spatial_meta[[i]])[ colnames(stclust_stlist@spatial_meta[[i]]) %in% colnames(spagcn_tmp[, -1]) ]
  if(length(duplicated_cols) > 0){
    stclust_stlist@spatial_meta[[i]] = stclust_stlist@spatial_meta[[i]][, !(colnames(stclust_stlist@spatial_meta[[i]]) %in% duplicated_cols) ]
  }

  stclust_stlist@spatial_meta[[i]] = stclust_stlist@spatial_meta[[i]] %>%
    dplyr::left_join(., spagcn_tmp, by='libname')
}

# Annotation names for dropdown selection
annot_variables = unique(unlist(lapply(stclust_stlist@spatial_meta, function(i){ var_cols=grep('spagcn_|stclust_', colnames(i), value=T); return(var_cols) })))
write.table(annot_variables, 'stdiff_annotation_variables.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Save tables with spot/cell domain assigments (used in SpaGCN-SVG analysis to avoid reading STlist again)
for(i in names(stclust_stlist@spatial_meta)){
  df_tmp = stclust_stlist@spatial_meta[[i]] %>% dplyr::select(1, grep('stclust_|spagcn_', colnames(stclust_stlist@spatial_meta[[i]]), value=T))
  write.csv(df_tmp, paste0(i, '_domain_annotations_deg_svg.csv'), row.names=F, quote=F)
  rm(df_tmp) # Clean env
}

# Save unique lables for each annotation (to display in dropdowns)
cluster_values = tibble::tibble()
for(i in names(stclust_stlist@spatial_meta)){
  for(cl in grep('spagcn_|stclust_', colnames(stclust_stlist@spatial_meta[[i]]), value=T)){
    cluster_values = dplyr::bind_rows(cluster_values,
                                        tibble::tibble(cluster=as.vector(unique(stclust_stlist@spatial_meta[[i]][[cl]]))) %>%
                                        tibble::add_column(annotation=as.vector(cl)))
                                        #tibble::tibble(cluster=as.character(unique(stclust_stlist@spatial_meta[[i]][[cl]]))) %>%
                                        #tibble::add_column(annotation=as.character(cl)))
  }}
cluster_values = dplyr::distinct(cluster_values) %>%
  dplyr::select(annotation, cluster)
write.table(cluster_values, 'stdiff_annotation_variables_clusters.csv', quote=F, row.names=F, col.names=F, sep=',')

{$this->_saveStList('stclust_stlist')}

ps = STplot(stclust_stlist, plot_meta = annot_variables, ptsize = 2, txsize=14, color_pal = '$col_pal')

n_plots = names(ps)
write.table(n_plots, 'spagcn_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
library('svglite')
for(p in n_plots) {
    #print(p)
    ggpubr::ggexport(filename = paste(p,'.png', sep=''), ps[[p]], width = 800, height = 600)
    ggpubr::ggexport(filename = paste(p,'.pdf', sep=''), ps[[p]], width = 8, height = 6)
    svglite(paste(p,'.svg', sep=''), width = 8, height = 6)
    print(ps[[p]])
    dev.off()

    #generate side-by-side for samples with tissue image
    for(sample in list($samples_with_tissue)) {
        if(grepl(sample, p, fixed=TRUE)) {
            tp = cowplot::ggdraw() + cowplot::draw_image(paste0(sample, '/spatial/image_', sample, '.png'))
            ptp = ggpubr::ggarrange(ps[[p]], tp, ncol=2)
            {$this->getExportFilesCommands("paste0(p, '-sbs')", 'ptp', 1400, 600)}
        }
    }
}

";
        return $script;
    }




    public function SpaGCN_SVG($parameters)
    {

        $workingDir = $this->workingDir();

        //Run SpaGCN_SVG on the simple STList
        $scriptName = 'SpaGCN2_SVG.py';
        $scriptContents = Storage::get("/common/templates/$scriptName");

        $params = ['annotation_to_test'];
        foreach ($params as $param) {
            $scriptContents = str_replace("{param_$param}", $parameters[$param], $scriptContents);
        }
        $sampleList = "'" . $this->samples()->pluck('samples.name')->join("','") . "'";
        $scriptContents = str_replace("{param_sample_list}", $sampleList, $scriptContents);
        Storage::put("$workingDir/$scriptName", $scriptContents);
        $output = $this->spatialExecute('python ' . $scriptName, $parameters['__task'], 'SPAGCN');


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

                    $filesJSON[$sampleName][] = $this->workingDirPublicURL() . $fileJSON;

                    $k_tmp++;
                }
            }
            $k = $k_tmp;
        }

        ProjectParameter::updateOrCreate(['parameter' => 'spagcn_svg', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'json_files' => $filesJSON, 'k' => $k])]);

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


        $files = ['stdiff_ns_results.xlsx'];
        foreach ($parameters['samples_array'] as $sample) {
            $files[] = 'stdiff_ns_' . $sample . '.csv';
            $files[] = 'stdiff_ns_' . $sample . '.json';
        }
        foreach ($files as $file) {
            if (Storage::fileExists($workingDir . $file)) {

                if (explode('.', $file)[1] === 'csv')
                    $this->csv2json($workingDir . $file, 2, $column_names);

                $file_public = $workingDirPublic . $file;
                $file_to_move = $workingDir . $file;
                Storage::delete($file_public);
                Storage::move($file_to_move, $file_public);
            }
        }


        $file = $workingDir . 'stdiff_ns_volcano_plots.csv';
        $vps = [];
        if (Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $data) as $_plot) {
                $plot = 'stdiff_ns_vp_' . $_plot;
                $vps[] = $this->workingDirPublicURL() . $plot;
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
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stdiff_ns', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(), 'samples' => $parameters['samples_array'], 'volcano_plots' => $vps])]);

        return ['output' => $output, 'script' => $scriptContents];
    }


    private function getSTDiffNonSpatialScript($parameters)
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
                    }
                }
            }
        }



        ProjectParameter::updateOrCreate(['parameter' => 'stdiff_s', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(),  'samples' => $parameters['samples_array'], 'volcano_plots' => $vps])]);

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
        foreach ($files as $file) {
            if (Storage::fileExists($workingDir . $file)) {

                if (explode('.', $file)[1] === 'csv')
                    $this->csv2json($workingDir . $file, 2, $column_names);

                $file_public = $workingDirPublic . $file;
                $file_to_move = $workingDir . $file;
                Storage::delete($file_public);
                Storage::move($file_to_move, $file_public);
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stenrich', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(),  'samples' => $this->samples->pluck('name')])]);

        return ['output' => $output, 'script' => $scriptContents];
    }


    private function getSTEnrichScript($parameters)
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

        $files = ['stgradients_results.xlsx'];
        foreach ($parameters['samples_array'] as $sample) {
            $files[] = 'stgradients_' . $sample . '.csv';
            $files[] = 'stgradients_' . $sample . '.json';
        }
        foreach ($files as $file)
            if (Storage::fileExists($workingDir . $file)) {

                if (explode('.', $file)[1] === 'csv')
                    $this->csv2json($workingDir . $file, 1, $column_names);

                $file_public = $workingDirPublic . $file;
                $file_to_move = $workingDir . $file;
                Storage::delete($file_public);
                Storage::move($file_to_move, $file_public);
            }

        ProjectParameter::updateOrCreate(['parameter' => 'stgradients', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(),  'samples' => $parameters['samples_array']])]);

        return ['output' => $output, 'script' => $scriptContents];
    }


    private function getSTGradientsScript($parameters)
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


        $topic_annotations = [];
        foreach ($this->samples as $sample) {
            $file = $workingDir . 'topic_annotations_' . $sample->name . '.csv';
            $sample_topics = [];
            if (Storage::fileExists($file)) {
                $data = trim(Storage::read($file));
                $data = str_replace('"', '', $data);
                foreach (preg_split("/((\r?\n)|(\r\n?))/", $data) as $line) {
                    $tmp = explode(',', $line);
                    $sample_topics[$tmp[0]] = ["annotation" => $tmp[1], "new_annotation" => $tmp[2], "plot" => $this->workingDirPublicURL() . "stdeconvolve2_topic_logfc_" . $sample->name . "_" . $tmp[0]];
                }
                array_shift($sample_topics);
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

                    $fileName = $this->workingDir() . 'gsea_results_' . $sampleName . '_' . $topicName . '.csv';

                    if (Storage::fileExists($fileName)) {

                        $contents = Storage::read($fileName);
                        $contents = str_replace('p.val', 'p_val', $contents);
                        $contents = str_replace('q.val', 'q_val', $contents);
                        Storage::put($fileName, $contents);

                        $gsea_results[$sampleName][$topicName] = json_decode($this->csv2json($fileName, 0, $column_names, true));
                    }
                }
            }
        }



        $scatterpie_plots = $this->STdeconvolveMovePlotsToPublic();


        ProjectParameter::updateOrCreate(['parameter' => 'STdeconvolve2', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'logfold_plots' => $topic_annotations, 'scatterpie_plots' => $scatterpie_plots, 'gsea_results' => $gsea_results])]);

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
                    }
                }
            }
        }

        return $scatterpie_plots;
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





    private function getSavePlotFunctionRscript()
    {

        return Storage::get("/common/templates/_save_plots.R");
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

        Storage::put(explode('.', $file)[0] . '.json', $contents);
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

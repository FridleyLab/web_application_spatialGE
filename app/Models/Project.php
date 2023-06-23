<?php

namespace App\Models;

use App\Http\Controllers\spatialContainer;
use App\Jobs\RunScript;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Monolog\Logger;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects';

    protected $fillable = ['name', 'description', 'user_id'];

    protected $appends = ['url', 'project_parameters'];

    private ?spatialContainer $_container = null;

    //Relations

    public function user() : BelongsTo {
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

    public function genes(): HasMany
    {
        return $this->hasMany(ProjectGene::class);
    }

    //Attributes
    public function getUrlAttribute() {
        return route('open-project', ['project' => $this->id]);
    }

    public function getProjectParametersAttribute() {
        $params = [];
        foreach ($this->parameters as $param)
            $params[$param->parameter] = $param->type === 'number' ? intval($param->value) : $param->value;

        $params['total_genes'] = $this->genes()->count();

        if(array_key_exists('metadata', $params))
        {
            $names = [];
            foreach(json_decode($params['metadata']) as $meta)
                $names[] = ['label' =>$meta->name, 'value' => $meta->name];
            $params['metadata_names'] = $names;
        }

        $fileName = $this->workingDir() . 'stdiff_annotation_variables.csv';
        if(Storage::fileExists($fileName))
        {
            $data = Storage::read($fileName);
            $lines = explode("\n", $data);
            $annotations = [];
            foreach($lines as $annotation)
                if(strlen(trim($annotation)))
                    $annotations[] = ['label' =>$annotation, 'value' => $annotation];
            $params['annotation_variables'] = $annotations;
        }

        $fileName = $this->workingDir() . 'stdiff_annotation_variables_clusters.csv';
        if(Storage::fileExists($fileName))
        {
            $data = Storage::read($fileName);
            $lines = explode("\n", $data);
            $annotations_clusters = [];
            foreach($lines as $annotation) {
                if (strlen(trim($annotation))) {
                    $values = explode(',', $annotation);
                    $annotations_clusters[] = ['annotation' => $values[0], 'cluster' => $values[1]];
                    //$annotations_clusters[] = ['annotation' => $values[0], 'cluster' => $values[1]];
                }
            }
            $params['annotation_variables_clusters'] = $annotations_clusters;
        }

        return $params;
    }


    public function getCurrentStepUrl() {
        if($this->current_step === 1)
            return route('import-data', ['project' => $this->id]);

        if($this->current_step === 2)
            return route('qc-data-transformation', ['project' => $this->id]);

        if($this->current_step >= 3)
            return route('stplot-visualization', ['project' => $this->id]);


        return '/';
    }

    public function workingDir() : string {
        $workingDir = '/users/' . $this->user_id . '/' . $this->id . '/';
        $workingDir = str_replace('\\', '/', $workingDir);
        return $workingDir;
    }

    public function workingDirPublic() : string {
        Storage::createDirectory('/public/users');
        Storage::createDirectory('/public/users/' . $this->user_id);
        Storage::createDirectory('/public/users/' . $this->user_id . '/' . $this->id);
        $workingDir = '/public/users/' . $this->user_id . '/' . $this->id . '/';
        $workingDir = str_replace('\\', '/', $workingDir);
        return $workingDir;
    }

    public function workingDirPublicURL() : string {
        $workingDir = '/storage/users/' . $this->user_id . '/' . $this->id . '/';
        $workingDir = str_replace('\\', '/', $workingDir);
        return $workingDir;
    }

    public function spatialExecute($command) {

        if(is_null($this->_container))
            $this->_container = new spatialContainer($this);

        return $this->_container->execute($command);

    }


    private function _saveStList($stlist) {

        $persistOn = env('PERSIST_DATA_ON', 'DISK');

        $command = '';
        if($persistOn === 'DISK')
            $command = "save($stlist, file='$stlist.RData')";
        elseif ($persistOn === 'REDIS')
            $command = "
            r <- redux::hiredis()
            r\$SET('$stlist', redux::object_to_bin($stlist))
            #r\$HSET('spatialGE', '$stlist', serialize($stlist, NULL))
            ";

        return $command;
    }

    private function _loadStList($stlist) {

        $persistOn = env('PERSIST_DATA_ON', 'DISK');

        $command = '';
        if($persistOn === 'DISK')
            $command = "load(file='$stlist.RData')";
        elseif ($persistOn === 'REDIS')
            $command = "
            r <- redux::hiredis()
            $stlist = redux::bin_to_object(r\$GET('$stlist'))
            ";

        return $command;
    }

    private function pca_max_var_genes() {
        $file = $this->workingDir() . 'pca_max_var_genes.csv';
        if(Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::updateOrCreate(['parameter' => 'pca_max_var_genes', 'project_id' => $this->id], ['type' => 'number', 'value' => $data]);
            return intval($data);
        }
        return 0;
    }

    private function filter_meta_options() {
        $file = $this->workingDir() . 'filter_meta_options.csv';
        $json = json_encode([]);
        if(Storage::fileExists($file)) {
            $data = Storage::read($file);
            $options = explode("\n", $data);
            $_options = [];
            foreach ($options as $option)
                if(strlen($option))
                    $_options[] = ['label' => $option, 'value' => $option];
            $json = json_encode($_options);
            ProjectParameter::updateOrCreate(['parameter' => 'filter_meta_options', 'project_id' => $this->id], ['type' => 'json', 'value' => $json]);
        }

        return $json;
    }

    public function createStList($parameters) {

        $workingDir = $this->workingDir();

        $scriptName = 'STList.R';
        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getStListScript());

        //delete all existing STlists
        foreach(Storage::files($workingDir) as $file) {
            if(stripos($file, '.rdata'))
                Storage::delete($file);
        }

        //Create the initial_stlist
        $output = $this->spatialExecute("Rscript $scriptName");


        //Load genes present in samples into the DB
        $genes_file = $workingDir . 'genes.csv';
        if(Storage::fileExists($genes_file)) {
            $data = Storage::read($genes_file);
            $genes = explode("\n", $data);
            $_genes = [];
            foreach ($genes as $gene)
                if(strlen($gene))
                    $_genes[] = ['gene' => $gene, 'project_id' => $this->id, 'context' => 'initial'];
                //ProjectGene::create(['gene' => $gene, 'project_id' => $this->id]);
            DB::delete("delete from project_genes where context='initial' and project_id=" . $this->id);
            foreach (array_chunk($_genes,1000) as $chunk)
            {
                //DB::table('table_name')->insert($t);
                ProjectGene::insert($chunk);
            }
        }

        //Delete previously generated parameters, if any
        DB::delete("delete from project_parameters where parameter<>'metadata' and not(parameter like 'job.%') and project_id=" . $this->id);

        $this->pca_max_var_genes();

        //Load other parameters generated by the R script
        $file = $workingDir . 'max_spot_counts.csv';
        if(Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::insert(['parameter' => 'max_spot_counts', 'type' => 'number', 'value' => $data, 'project_id' => $this->id]);
        }
        $file = $workingDir . 'max_gene_counts.csv';
        if(Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::insert(['parameter' => 'max_gene_counts', 'type' => 'number', 'value' => $data, 'project_id' => $this->id]);
        }
        $file = $workingDir . 'max_spots_number.csv';
        if(Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::insert(['parameter' => 'max_spots_number', 'type' => 'number', 'value' => $data, 'project_id' => $this->id]);
        }
        $file = $workingDir . 'initial_stlist_summary.csv';
        if(Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::insert(['parameter' => 'initial_stlist_summary', 'type' => 'string', 'value' => $data, 'project_id' => $this->id]);
        }

        $this->filter_meta_options();

        //Data imported for this project, proceed to step 2 of the wizard
        $this->current_step = 2;
        $this->save();

        return ['output' => $output];


    }

    public function getStListScript() : string {

        $sampleDirs = $this->samples()->pluck('samples.name')->join("/','");
        $sampleDirs = "'" . $sampleDirs . "/'";

        $params = $this->getProjectParametersAttribute();
        $sampleNames = array_key_exists('metadata_names', $params) ? sizeof($params['metadata_names']) : 0;
        $sampleNames = $sampleNames ? "'clinical_data.csv'" : "c('" . $this->samples()->pluck('samples.name')->join("','") . "')";

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Specify paths to directories containing data
count_files = c($sampleDirs)

# Specify sample names
samplenames = $sampleNames


# Create STlist
initial_stlist <- STlist(rnacounts=count_files, samples=samplenames)
#initial_stlist <- STlist(rnacounts=count_files, samples=samplenames, spotcoords='segundo archivo csv')

#Save the STList
" .
$this->_saveStList("initial_stlist")
. "

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

";

        return $script;

    }


    public function applyFilter($parameters) {

        $workingDir = $this->workingDir();

        $scriptName = 'Filter.R';
        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getFilterDataScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $result = [];


        $result['filter_meta_options'] = $this->filter_meta_options();


        $result['pca_max_var_genes'] = $this->pca_max_var_genes();


        //Load genes present in the filtered STlist into the DB
        $genes_file = $workingDir . 'genesFiltered.csv';
        if(Storage::fileExists($genes_file)) {
            $data = Storage::read($genes_file);
            $genes = explode("\n", $data);
            $_genes = [];
            foreach ($genes as $gene)
                if(strlen($gene))
                    $_genes[] = ['gene' => $gene, 'project_id' => $this->id, 'context' => 'filtered'];
            //ProjectGene::create(['gene' => $gene, 'project_id' => $this->id]);
            DB::delete("delete from project_genes where context='filtered' and project_id=" . $this->id);

            foreach (array_chunk($_genes,1000) as $chunk)
            {
                //DB::table('table_name')->insert($t);
                ProjectGene::insert($chunk);
            }
        }


        $parameterNames = ['filter_violin', 'filter_boxplot'];
        foreach($parameterNames as $parameterName) {

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

        $parameterName = 'filtered_stlist_summary';
        $file = $workingDir . $parameterName .'.csv';
        if(Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id], ['type' => 'string', 'value' => $data]);
            $result[$parameterName] = $data;

        }

        $result['output'] = $output;

        return $result;

    }


    public function getFilterDataScript($parameters) : string {

        $str_params = '';
        foreach ($parameters as $key => $value) {
            if(strlen($value)) {
                $str_params .= strlen($str_params) ? ', ' : '';
                $quote = in_array($key, ['rm_genes_expr', 'spot_pct_expr']) ? "'" : '';
                $str_params .= $key . '=' . $quote . $value . $quote;
            }
        }

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
vp = violin_plots(filtered_stlist, plot_meta='total_counts', color_pal='Spectral')
#ggpubr::ggexport(filename = 'filter_violin.png', vp, width = 800, height = 800)

#### Box plot
bp = violin_plots(filtered_stlist, plot_meta='total_counts', color_pal='Spectral', plot_type='box')
#ggpubr::ggexport(filename = 'filter_boxplot.png', bp, width = 800, height = 800)

#### Save plots to file

$plots

";

        //dd($script);

        return $script;

    }





    public function generateFilterPlots($parameters) {

        $workingDir = $this->workingDir();

        $scriptName = 'generateFilterPlots.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getFilterPlotsScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $parameterNames = ['filter_violin', 'filter_boxplot'];
        foreach($parameterNames as $parameterName) {

            $file_extensions = ['svg', 'pdf', 'png'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                }
            }
        }

        return ['output' => $output];

    }


    public function getFilterPlotsScript($parameters) : string {

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
vp = violin_plots(filtered_stlist, plot_meta='$variable', color_pal='$color_palette')
#ggpubr::ggexport(filename = 'filter_violin.png', vp, width = 800, height = 800)

#### Box plot
bp = violin_plots(filtered_stlist, plot_meta='$variable', color_pal='$color_palette', plot_type='box')
#ggpubr::ggexport(filename = 'filter_boxplot.png', bp, width = 800, height = 800)

#### save plots to file
$plots

";

        return $script;

    }


    public function applyNormalization($parameters) {

        $workingDir = $this->workingDir();

        $scriptName = 'Normalization.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getNormalizationScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

//        $file = $workingDir . 'filter_meta_options.csv';
//        if(Storage::fileExists($file)) {
//            $data = Storage::read($file);
//            $options = explode("\n", $data);
//            $_options = [];
//            foreach ($options as $option)
//                if(strlen($option))
//                    $_options[] = ['label' => $option, 'value' => $option];
//            ProjectParameter::updateOrCreate(['parameter' => 'filter_meta_options', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode($_options)]);
//        }


        //Load genes present in the normalized STlist into the DB
        $genes_file = $workingDir . 'genesNormalized.csv';
        if(Storage::fileExists($genes_file)) {
            $data = Storage::read($genes_file);
            $genes = explode("\n", $data);
            $_genes = [];
            foreach ($genes as $gene)
                if(strlen($gene))
                    $_genes[] = ['gene' => $gene, 'project_id' => $this->id, 'context' => 'normalized'];
            //ProjectGene::create(['gene' => $gene, 'project_id' => $this->id]);
            DB::delete("delete from project_genes where context='normalized' and project_id=" . $this->id);

            foreach (array_chunk($_genes,1000) as $chunk)
            {
                //DB::table('table_name')->insert($t);
                ProjectGene::insert($chunk);
            }
        }


        $result = [];

        $parameterNames = [/*'normalized_violin', 'normalized_boxplot', */'normalized_boxplot_1', 'normalized_boxplot_2', 'normalized_violin_1', 'normalized_violin_2', 'normalized_density_1', 'normalized_density_2'];
        foreach($parameterNames as $parameterName) {

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

        $result['pca_max_var_genes'] = $this->pca_max_var_genes();

        //Delete (if any) previously generated normalized data
        ProjectParameter::where('parameter','normalizedData')->where('project_id', $this->id)->delete();


        $this->current_step = 6;
        $this->save();

        $result['output'] = $output;
        return $result;

    }


    public function getNormalizationScript($parameters) : string {

        //If there's no filtered stlist use the initial stlist
        $stlist = 'filtered_stlist';
        if(!Storage::fileExists($this->workingDir() . "$stlist.RData")) $stlist = 'initial_stlist';

        $str_params = '';
        foreach ($parameters as $key => $value) {
            if(strlen($value)) {
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

normalized_stlist = transform_data($stlist, $str_params)

{$this->_saveStList('normalized_stlist')}

gene_names = unique(unlist(lapply(normalized_stlist@counts, function(i){ genes_tmp = rownames(i) })))
write.table(gene_names, 'genesNormalized.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

#max_var_genes PCA
pca_max_var_genes = min(unlist(lapply(normalized_stlist@counts, nrow)))
write.table(pca_max_var_genes, 'pca_max_var_genes.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

#### Violin plot
#vp = violin_plots(normalized_stlist, color_pal='Spectral', data_type='tr', genes='RPL22')

#### Box plot
#bp = violin_plots(normalized_stlist, color_pal='Spectral', plot_type='box', data_type='tr', genes='RPL22')

den_raw = count_distribution(normalized_stlist, distrib_subset=0.01, data_type='raw', plot_type=c('density', 'violin', 'box'))
den_tr = count_distribution(normalized_stlist, distrib_subset=0.01, plot_type=c('density', 'violin', 'box'))

$plots

";

        return $script;

    }


    public function generateNormalizationPlots($parameters) {

        $workingDir = $this->workingDir();

        $scriptName = 'generateNormalizedPlots.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getNormalizedPlotsScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $parameterNames = ['normalized_violin', 'normalized_boxplot'];
        foreach($parameterNames as $parameterName) {
            $file_extensions = ['svg', 'pdf', 'png'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                }
            }
        }

        return ['output' => $output];

    }


    public function getNormalizedPlotsScript($parameters) : string {

        //If there's no normalizes stlist use the initial stlist
        $stlist = 'normalized_stlist';
        if(!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'filtered_stlist';
        if(!Storage::fileExists($this->workingDir() . "$stlist.RData"))
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
vp = violin_plots($stlist, color_pal='$color_palette', data_type='tr', genes='$gene')

#### Box plot
bp = violin_plots($stlist, color_pal='$color_palette', plot_type='box', data_type='tr', genes='$gene')

$plots

";

        return $script;

    }


    public function generateNormalizationData($parameters) {

        $workingDir = $this->workingDir();

        $scriptName = 'generateNormalizedData.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getNormalizedDataScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $parameterNames = ['normalizedData'];
        foreach($parameterNames as $parameterName) {
            $file_extensions = ['xlsx'];
            foreach ($file_extensions as $file_extension) {
                $fileName = $parameterName . '.' . $file_extension;
                $file = $workingDir . $fileName;
                $file_public = $this->workingDirPublic() . $fileName;
                if (Storage::fileExists($file)) {
                    Storage::delete($file_public);
                    Storage::move($file, $file_public);
                    ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $parameterName]);
                }
            }
        }

        return ['output' => $output];

    }

    public function getNormalizedDataScript() : string {

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


    public function applyPca($parameters) {

        $workingDir = $this->workingDir();

        $scriptName = 'generatePca.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getPcaScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $result = [];

        //Delete any previously generated plots
        ProjectParameter::where('project_id', $this->id)->whereIn('parameter', ['pseudo_bulk_pca', 'pseudo_bulk_heatmap'])->delete();

        //To indicate that the PCA has been calculated
        ProjectParameter::updateOrCreate(['parameter' => 'qc_pca', 'project_id' => $this->id], ['type' => 'number', 'value' => 1]);

        $result['output'] = $output;
        return $result;

    }



    public function getPcaScript($parameters) : string {

        //If there's no normalizes stlist use the initial stlist
        $stlist = 'normalized_stlist';
        if(!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'filtered_stlist';
        if(!Storage::fileExists($this->workingDir() . "$stlist.RData"))
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



    public function pcaPlots($parameters) {

        $workingDir = $this->workingDir();

        $scriptName = 'generatePcaPlots.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getPcaPlotsScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $result = [];

        $parameterNames = ['pseudo_bulk_pca', 'pseudo_bulk_heatmap'];
        foreach($parameterNames as $parameterName) {

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
        return $result;

    }


    public function getPcaPlotsScript($parameters) : string {

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


    public function quiltPlot($parameters) {

        $workingDir = $this->workingDir();

        $scriptName = 'quiltPlot.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getQuiltPlotScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $result = [];

        $parameterNames = ['quilt_plot_1_initial', 'quilt_plot_2_initial'];
        //If there's a filtered or normalized stlist generate the other plots
        if(Storage::fileExists($workingDir . 'normalized_stlist.RData') || Storage::fileExists($workingDir . 'filtered_stlist.RData'))
            $parameterNames = array_merge($parameterNames, ['quilt_plot_1', 'quilt_plot_2']);
        foreach($parameterNames as $parameterName) {
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
        return $result;

    }



    public function getQuiltPlotScript($parameters) : string {

        //If there's no normalizes stlist use the initial stlist
        $stlist = 'normalized_stlist';
        if(!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'filtered_stlist';
        if(!Storage::fileExists($this->workingDir() . "$stlist.RData"))
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



    public function STplotQuilt($parameters) {
        $workingDir = $this->workingDir();

        $scriptName = 'STplot-quiltPlot.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getSTplotQuiltScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $result = [];
        foreach($parameters['genes'] as $gene) {
            $result[$gene] = [];
            foreach ($this->samples as $sample) {
                $parameterName = 'stplot-quilt-' . $gene . '-' . $sample->name;

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

        ProjectParameter::updateOrCreate(['parameter' => 'stplot_quilt', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode($result)]);

        return ['output' => $output];
        //return json_encode($result);
    }

    public function getSTplotQuiltScript($parameters) : string {

        $genes = $parameters['genes'];
        $ptsize = $parameters['ptsize'];
        $col_pal = $parameters['col_pal'];
        $data_type = $parameters['data_type'];

        $_genes = "c('" . join("','", $genes) . "')";

        $export_files = '';
        foreach ($genes as $gene)
            foreach ($this->samples as $sample)
                $export_files .= $this->getExportFilesCommands("stplot-quilt-$gene-" . $sample->name, "qp\$" . $gene . "_" . $sample->name);

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

#/* TODO: *******///
#stlist_expression_surface = gene_interpolation(normalized_stlist, genes=$_genes)
#krp = STplot_interpolation(stlist_expression_surface, genes=$_genes)


";

        return $script;

    }




    public function STplotExpressionSurface($parameters) {
        $workingDir = $this->workingDir();

        $scriptName = 'STplot-ExpressionSurface.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getSTplotExpressionSurfaceScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $result = [];
        foreach($parameters['genes'] as $gene) {
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

        return ['output' => $output];
        //return json_encode($result);
    }

    public function getSTplotExpressionSurfaceScript($parameters) : string {

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


    public function STplotExpressionSurfacePlots($parameters) {
        $workingDir = $this->workingDir();

        $scriptName = 'STplot-ExpressionSurfacePlots.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getSTplotExpressionSurfacePlotsScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $result = [];
        foreach($parameters['genes'] as $gene) {
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

        return ['output' => $output];
        //return json_encode($result);
    }


    public function getSTplotExpressionSurfacePlotsScript($parameters) : string {

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



    public function SThet($parameters) {
        $workingDir = $this->workingDir();

        $scriptName = 'SThet.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getSThetScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $result = [];

        $prevoius_genes = array_key_exists('sthet_genes', $this->project_parameters) ? json_decode($this->project_parameters['sthet_genes']) : [];
        $new_genes = array_merge($parameters['genes'], $prevoius_genes);
        ProjectParameter::updateOrCreate(['parameter' => 'sthet_genes', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode($new_genes)]);

        $parameterNames = ['sthet_plot_table_results'];
        foreach($parameterNames as $parameterName) {
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
        return $result;
    }

    private function getSThetScript($parameters) {

        $stlist = 'stlist_sthet';
        if(!Storage::fileExists($this->workingDir() . "$stlist.RData")) $stlist = 'normalized_stlist';

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


    public function SThetPlot($parameters) {
        $workingDir = $this->workingDir();

        $scriptName = 'SThetPlot.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getSThetPlotScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $result = [];

        $parameterNames = ['sthet_plot'];
        foreach($parameterNames as $parameterName) {
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
        return $result;
    }



    private function getSThetPlotScript($parameters) {

        $genes = $parameters['genes'];
        $color_pal = $parameters['color_pal'];
        $plot_meta = $parameters['plot_meta'];

        $_genes = "c('" . join("','", $genes) . "')";

        $export_files = $this->getExportFilesCommands("sthet_plot", "sthet_plot");

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load STList
{$this->_loadStList('stlist_sthet')}

sthet_plot = compare_SThet(stlist_sthet, samplemeta='$plot_meta', genes=$_genes, color_pal='$color_pal')

$export_files

";

        return $script;
    }


    public function STclust($parameters) {
        $workingDir = $this->workingDir();

        $scriptName = 'STclust.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getSTclustScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);


        $file = $workingDir . 'stclust_plots.csv';
        if(Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            $plots = [];
            foreach(preg_split("/((\r?\n)|(\r\n?))/", $data) as $plot){
                $plots[] = $this->workingDirPublicURL() . $plot;
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
            ProjectParameter::updateOrCreate(['parameter' => 'stclust', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['parameters' => $parameters, 'plots' => $plots])]);
        }

        $this->current_step = 8;
        $this->save();

        return ['output' => $output];
    }

    public function getSTclustScript($parameters) : string {

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')
library('magrittr')

# Load normalized STList
{$this->_loadStList('normalized_stlist')}

stclust_stlist = STclust(x=normalized_stlist,
                         ws={$parameters['ws']},
                         ks={$parameters['ks']},
                         topgenes={$parameters['topgenes']},
                         deepSplit={$parameters['deepSplit']})

#annot_variables used for Differential Expression analyses
annot_variables = unique(unlist(lapply(stclust_stlist@spatial_meta, function(i){ var_cols=grep('stclust_', colnames(i), value=T); return(var_cols) })))
write.table(annot_variables, 'stdiff_annotation_variables.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
##clusters_by_annot_variables used for Differential Expression analyses
cluster_values = tibble::tibble()
for(i in names(stclust_stlist@spatial_meta)){
  for(cl in grep('stclust_', colnames(stclust_stlist@spatial_meta[[i]]), value=T)){
    cluster_values = dplyr::bind_rows(cluster_values,
                                      tibble::tibble(cluster=unique(stclust_stlist@spatial_meta[[i]][[cl]])) %>%
                                        tibble::add_column(annotation=cl))
  }}
cluster_values = dplyr::distinct(cluster_values) %>%
  dplyr::select(annotation, cluster)
write.table(cluster_values, 'stdiff_annotation_variables_clusters.csv', quote=F, row.names=F, col.names=F, sep=',')


{$this->_saveStList('stclust_stlist')}

ps = STplot(x=stclust_stlist, ks={$parameters['ks']}, ws={$parameters['ws']}, ptsize=2, color_pal='smoothrainbow')
n_plots = names(ps)
write.table(n_plots, 'stclust_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
library('svglite')
for(p in n_plots) {
    print(p)
    ggpubr::ggexport(filename = paste(p,'.png', sep=''), ps[[p]], width = 800, height = 600)
    ggpubr::ggexport(filename = paste(p,'.pdf', sep=''), ps[[p]], width = 8, height = 6)
    svglite(paste(p,'.svg', sep=''), width = 8, height = 6)
    print(ps[[p]])
    dev.off()
}
";

        return $script;
    }



    public function STDiffNonSpatial($parameters) {
        $workingDir = $this->workingDir();
        $workingDirPublic = $this->workingDirPublic();

        $scriptName = 'STDiff_NonSpatial.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getSTDiffNonSpatialScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);


        $column_names = [
            'gene' => 'Gene',
            'avg_log2fc' => 'Average log-Fold Change',
            'cluster_1' => 'Cluster 1',
            'cluster_2' => 'Cluster 2',
            'wilcox_p_val' => 'Wilcoxon’s p-value',
            'ttest_p_val' => 'T-test p-value',
            'adj_p_val' => 'Adjusted p-value'
        ];


        $files = ['stdiff_ns_results.xlsx'];
        foreach($parameters['samples_array'] as $sample) {
            $files[] = 'stdiff_ns_' . $sample . '.csv';
            $files[] = 'stdiff_ns_' . $sample . '.json';
        }
        foreach($files as $file)
            if(Storage::fileExists($workingDir . $file)) {

                if(explode('.', $file)[1] === 'csv')
                    $this->csv2json($workingDir . $file, 2, $column_names);

                $file_public = $workingDirPublic . $file;
                $file_to_move = $workingDir . $file;
                Storage::delete($file_public);
                Storage::move($file_to_move, $file_public);
            }

        ProjectParameter::updateOrCreate(['parameter' => 'stdiff_ns', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(),  'samples' => $parameters['samples_array']])]);

        //$this->current_step = 7;
        //$this->save();

        return ['output' => $output];
    }


    private function getSTDiffNonSpatialScript($parameters) {

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

de_genes_results = STdiff(stclust_stlist, #### NORMALIZED STList
                          samples=$samples,   #### Users should be able to select which samples to include in analysis
                          annot='$annotation',  #### Name of variable to use in analysis... Dropdown to select one of `annot_variables`
                          topgenes=$topgenes, #### !!! Defines a lot of the speed. 100 are too few genes. Minimally would like 5000 but is SLOW. Can be a slider as in pseudobulk
                          test_type='$test_type', #### Other options are 't_test' and 'mm',
                          pairwise=$pairwise, #### Check box
                          clusters=$clusters, #### Need ideas for this one. Values in `cluster_values` and after user selected value in annot dropdown
                          cores=4) #### You know, the more the merrier

# Get workbook with results (samples in spreadsheets)
openxlsx::write.xlsx(de_genes_results, file='stdiff_ns_results.xlsx')

# Each sample as a CSV
lapply(names(de_genes_results), function(i){
  write.csv(de_genes_results[[i]], paste0('stdiff_ns_', i, '.csv'), row.names=T, quote=F)
})

";

        return $script;
    }


    public function STEnrich($parameters) {

        $workingDir = $this->workingDir();
        $workingDirPublic = $this->workingDirPublic();

        $scriptName = 'STEnrich.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getSTEnrichScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);


        $column_names = [
            'gene_set' => 'Gene set',
            'size_test' => 'Genes in sample',
            'size_gene_set' => 'Genes in set',
            'p_value' => 'p-value',
            'adj_p_value' => 'Adjusted p-value'
        ];


        $files = ['stenrich_results.xlsx'];
        foreach($this->samples->pluck('name') as $sample) {
            $files[] = 'stenrich_' . $sample . '.csv';
            $files[] = 'stenrich_' . $sample . '.json';
        }
        foreach($files as $file) {
            if (Storage::fileExists($workingDir . $file)) {

                if(explode('.', $file)[1] === 'csv')
                    $this->csv2json($workingDir . $file, 2, $column_names);

                $file_public = $workingDirPublic . $file;
                $file_to_move = $workingDir . $file;
                Storage::delete($file_public);
                Storage::move($file_to_move, $file_public);
            }
        }

        ProjectParameter::updateOrCreate(['parameter' => 'stenrich', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(),  'samples' => $this->samples->pluck('name')])]);

        return ['output' => $output];
    }


    private function getSTEnrichScript($parameters) {

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



    public function STGradients($parameters) {
        $workingDir = $this->workingDir();
        $workingDirPublic = $this->workingDirPublic();

        $scriptName = 'STGradients.R';

        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getSTGradientsScript($parameters));

        $output = $this->spatialExecute('Rscript ' . $scriptName);

        $column_names = [
            'gene' => 'Gene',
            //TODO: min and avg can be simplified
            'min_lm_coef' => 'Linear model slope',
            'min_lm_pval' => 'Linear model p-value',
            'min_spearman_r' => 'Spearman’s coefficient',
            'min_spearman_r_pval' => 'Spearman’s p-value',
            'min_spearman_r_pval_adj' => 'Spearman’s adjusted p-value',
            'min_pval_warning' => 'Warning',
            'avg_lm_coef' => 'Linear model slope',
            'avg_lm_pval' => 'Linear model p-value',
            'avg_spearman_r' => 'Spearman’s coefficient',
            'avg_spearman_r_pval' => 'Spearman’s p-value',
            'avg_spearman_r_pval_adj' => 'Spearman’s adjusted p-value',
            'avg_pval_warning' => 'Warning'
        ];

        $files = ['stgradients_results.xlsx'];
        foreach($parameters['samples_array'] as $sample) {
            $files[] = 'stgradients_' . $sample . '.csv';
            $files[] = 'stgradients_' . $sample . '.json';
        }
        foreach($files as $file)
            if(Storage::fileExists($workingDir . $file)) {

                if(explode('.', $file)[1] === 'csv')
                    $this->csv2json($workingDir . $file, 1, $column_names);

                $file_public = $workingDirPublic . $file;
                $file_to_move = $workingDir . $file;
                Storage::delete($file_public);
                Storage::move($file_to_move, $file_public);
            }

        ProjectParameter::updateOrCreate(['parameter' => 'stgradients', 'project_id' => $this->id], ['type' => 'json', 'value' => json_encode(['base_url' => $this->workingDirPublicURL(),  'samples' => $parameters['samples_array']])]);

        $this->current_step = 7;
        $this->save();

        return ['output' => $output];
    }


    private function getSTGradientsScript($parameters) {

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







    private function getExportFilesCommands($file, $plot) : string {

        $str = "if(!is.null($plot)){\n";

        //PNG
        $str .= "ggpubr::ggexport(filename = '$file.png', $plot, width = 800, height = 600)\n";

        //PDF
        $str .= "ggpubr::ggexport(filename = '$file.pdf', $plot, width = 8, height = 6)\n";

        //SVG
        $str .= "library('svglite')\n";
        $str .= "svglite('$file.svg', width = 8, height = 6)\n";
        $str .= "print($plot)\n";
        $str .= "dev.off()\n";

        $str .= "}\n\n";

        return $str;
    }


    public function createJob($description, $command, $parameters, $queue = 'default') : int {

        //create the job instance
        $job = new RunScript($description, $this, $command, $parameters);
        //push the job to que queue and get the jobId
        $jobId = Queue::connection()->pushOn($queue, $job);

        //save the jobId to the project parameters table
        ProjectParameter::updateOrCreate(['parameter' => 'job.' . $command, 'project_id' => $this->id], ['type' => 'number', 'value' => $jobId]);
        //Set the email notification off by default
        $this->setJobEmailNotification($command, 0);

        return $jobId;

    }

    public function setJobEmailNotification($command, $sendEmail) {

        ProjectParameter::updateOrCreate(['parameter' => 'job.' . $command . '.email', 'project_id' => $this->id], ['type' => 'number', 'value' => $sendEmail ? 1 : 0]);

    }

    public function csv2json($file, $column_offset = 2, $column_names = []) {
        $data = Storage::read($file);
        $lines = explode("\n", $data);
        $headers = [];
        $body = [];
        if (sizeof($lines) >= 2) {
            //process the headers
            $fields = explode(',', $lines[0]);
            if(sizeof($fields) > $column_offset) {
                for ($i = $column_offset; $i < sizeof($fields); $i++)
                    $headers[] = '{ "value": "' . $fields[$i] . '", "text": "' . (array_key_exists($fields[$i], $column_names) ? $column_names[$fields[$i]] : $fields[$i]) . '", "sortable": "true" }';

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
                                if(is_numeric($value) && (stripos($value, 'e') || abs(floatval($value)) < 0.001))
                                    $value  = sprintf("%.3e", $value);
                                elseif(is_numeric($value))
                                    $value = round($value, 3);

                                //wrap everything in quotes to prevent javascript from auto-formatting scientific notation
                                $value = '"' . $value . '"';

                                $body_line .= '"' . $fields[$i] . '":' . $value;
                            }
                        $body_line .= '}';
                        $body[] = $body_line;
                    }
                }
            }
        }
        $contents = '{' . "\n" . '"headers": [' . "\n" . implode(",\n", $headers) . '],' . "\n" . '"items": [' . "\n" . implode(",\n", $body) . "\n" . ']' . "\n" . '}';
        Storage::put(explode('.',$file)[0] . '.json', $contents);
    }

    public function getJobPositionInQueue($jobId) : int {

        //$queueName = env('QUEUE_DEFAULT_NAME', 'default');

        $queuePosition = 0;

        try {
            $jobInfo = DB::table('jobs')
                ->where('id', $jobId)
                ->get();
            if(!$jobInfo->count())
                return $queuePosition;

            $queueName = $jobInfo[0]->queue;

            $queuePosition = DB::table('jobs')
                ->where('queue', $queueName)
                ->where('id', '<=', $jobId)
                ->count();
        }
        catch(\Exception $e) {}

        return $queuePosition;
    }


}



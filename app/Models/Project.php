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
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

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
                $names[] = $meta->name;
            $params['metadata_names'] = $names;
        }

        return $params;
    }


    public function getCurrentStepUrl() {
        if($this->current_step === 1)
            return route('import-data', ['project' => $this->id]);

        if($this->current_step === 2)
            return route('qc-data-transformation', ['project' => $this->id]);


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

    public function createStList($parameters) {

        $workingDir = $this->workingDir();

        $scriptName = 'STList.R';
        $script = $workingDir . $scriptName;

        Storage::put($script, $this->getStListScript());

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
        //$metadata = $this->project_parameters['metadata'];
        DB::delete("delete from project_parameters where parameter<>'metadata' and not(parameter like 'job.%') and project_id=" . $this->id);
        //DB::delete("delete from project_parameters where parameter<>'metadata' and project_id=" . $this->id);
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

        //ProjectParameter::insert(['parameter' => 'metadata', 'project_id' => $this->id, 'type' => 'json', 'value' => $metadata]);

        //Data imported for this project, proceed to step 2 of the wizard
        $this->current_step = 2;
        $this->save();

        return ['output' => $output];


    }




    public function getStListScript() : string {

        $sampleDirs = $this->samples()->pluck('samples.name')->join("/','");
        $sampleDirs = "'" . $sampleDirs . "/'";

        $sampleNames = "'" . $this->samples()->pluck('samples.name')->join("','") . "'";

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Specify paths to directories containing data
count_files = c($sampleDirs)

# Specify sample names
#samplenames = c($sampleNames)
samplenames = 'clinical_data.csv'

# Create STlist
initial_stlist <- STlist(rnacounts=count_files, samples=samplenames)
#initial_stlist <- STlist(rnacounts=count_files, samples=samplenames, spotcoords='segundo archivo csv')

#Save the STList
" .
$this->_saveStList("initial_stlist")
. "

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

        $file = $workingDir . 'filter_meta_options.csv';
        if(Storage::fileExists($file)) {
            $data = Storage::read($file);
            $options = explode("\n", $data);
            $_options = [];
            foreach ($options as $option)
                if(strlen($option))
                    $_options[] = ['label' => $option, 'value' => $option];
            $json = json_encode($_options);
            ProjectParameter::updateOrCreate(['parameter' => 'filter_meta_options', 'project_id' => $this->id], ['type' => 'json', 'value' => $json]);
            $result['filter_meta_options'] = $json;
        }


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
vp = violin_plots(filtered_stlist, plot_meta='total_counts', color_pal='okabeito')
#ggpubr::ggexport(filename = 'filter_violin.png', vp, width = 800, height = 800)

#### Box plot
bp = violin_plots(filtered_stlist, plot_meta='total_counts', color_pal='okabeito', plot_type='box')
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

        $parameterNames = ['normalized_violin', 'normalized_boxplot', 'normalized_boxplot_1', 'normalized_boxplot_2', 'normalized_violin_1', 'normalized_violin_2', 'normalized_density_1', 'normalized_density_2'];
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

        $file = $workingDir . 'pca_max_var_genes.csv';
        if(Storage::fileExists($file)) {
            $data = trim(Storage::read($file));
            ProjectParameter::updateOrCreate(['parameter' => 'pca_max_var_genes', 'project_id' => $this->id], ['type' => 'number', 'value' => $data]);
            $result['pca_max_var_genes'] = intval($data);
        }

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

        $plots = $this->getExportFilesCommands('normalized_violin', 'vp');
        $plots .= $this->getExportFilesCommands('normalized_boxplot', 'bp');
        $plots .= $this->getExportFilesCommands('normalized_boxplot_1', "den_raw\$boxplot");
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
" .
$this->_loadStList($stlist)
. "

normalized_stlist = transform_data($stlist, $str_params)
" .
$this->_saveStList('normalized_stlist')
. "

gene_names = unique(unlist(lapply(normalized_stlist@counts, function(i){ genes_tmp = rownames(i) })))
write.table(gene_names, 'genesNormalized.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

#max_var_genes PCA
pca_max_var_genes = min(unlist(lapply(normalized_stlist@counts, nrow)))
write.table(pca_max_var_genes, 'pca_max_var_genes.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

#### Violin plot
#library('magrittr')
#source('violin_plots.R')
#source('utils.R')
vp = violin_plots(normalized_stlist, color_pal='okabeito', data_type='tr', genes='RPL22')
#ggpubr::ggexport(filename = 'normalized_violin.png', vp, width = 800, height = 800)

#### Box plot
bp = violin_plots(normalized_stlist, color_pal='okabeito', plot_type='box', data_type='tr', genes='RPL22')
#ggpubr::ggexport(filename = 'normalized_boxplot.png', bp, width = 800, height = 800)



#library('magrittr')
#source('count_distribution.R')
#source('utils.R')
den_raw = count_distribution(normalized_stlist, distrib_subset=0.01, data_type='raw', plot_type=c('density', 'violin', 'box'))
#save(den_raw, './raw_distrib_plots.RData')
#png('./pre_densityplot.png'); print(den_raw\$density); dev.off()
#png('./pre_violinplot.png'); print(den_raw\$violin); dev.off()
#png('./pre_boxplot.png'); print(den_raw\$boxplot); dev.off()
den_tr = count_distribution(normalized_stlist, distrib_subset=0.01, plot_type=c('density', 'violin', 'box'))
#load('./raw_distrib_plots.RData')

#png('./densityplot.png'); print(ggpubr::ggarrange(den_raw\$density, den_tr\$density, ncol=1)); dev.off()
#png('./violinplot.png'); print(ggpubr::ggarrange(den_raw\$violin, den_tr\$violin, ncol=1)); dev.off()
#png('./boxplot.png'); print(ggpubr::ggarrange(den_raw\$boxplot, den_tr\$boxplot, ncol=1)); dev.off()
#ggpubr::ggexport(filename = 'boxplot.png', ggpubr::ggarrange(den_raw\$boxplot, den_tr\$boxplot, ncol=1), width = 800, height = 800)

#ggpubr::ggexport(filename = 'normalized_boxplot_1.png', den_raw\$boxplot, width = 800, height = 800)
#ggpubr::ggexport(filename = 'normalized_boxplot_2.png', den_tr\$boxplot, width = 800, height = 800)

#ggpubr::ggexport(filename = 'normalized_density_1.png', den_raw\$density, width = 800, height = 800)
#ggpubr::ggexport(filename = 'normalized_density_2.png', den_tr\$density, width = 800, height = 800)

#ggpubr::ggexport(filename = 'normalized_violin_1.png', den_raw\$violin, width = 800, height = 800)
#ggpubr::ggexport(filename = 'normalized_violin_2.png', den_tr\$violin, width = 800, height = 800)

$plots

";

        //dd($script);

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
        /*foreach($parameterNames as $parameterName) {
            $fileName = $parameterName;
            $file = $workingDir . $fileName;
            $file_public = $this->workingDirPublic() . $fileName;
            if (Storage::fileExists($file)) {
                Storage::delete($file_public);
                Storage::copy($file, $file_public);
                ProjectParameter::updateOrCreate(['parameter' => $parameterName, 'project_id' => $this->id], ['type' => 'string', 'value' => $this->workingDirPublicURL() . $fileName]);
            }
        }*/

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
" .
$this->_loadStList($stlist)
. "

#### Violin plot
#library('magrittr')
#source('violin_plots.R')
#source('utils.R')
vp = violin_plots($stlist, color_pal='$color_palette', data_type='tr', genes='$gene')
#ggpubr::ggexport(filename = 'normalized_violin.png', vp, width = 800, height = 800)

#### Box plot
bp = violin_plots($stlist, color_pal='$color_palette', plot_type='box', data_type='tr', genes='$gene')
#ggpubr::ggexport(filename = 'normalized_boxplot.png', bp, width = 800, height = 800)

$plots

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



    public function getPcaScript($parameters) : string {

        //If there's no normalizes stlist use the initial stlist
        $stlist = 'normalized_stlist';
        if(!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'filtered_stlist';
        if(!Storage::fileExists($this->workingDir() . "$stlist.RData"))
            $stlist = 'initial_stlist';

        $plot_meta = $parameters['plot_meta'];
        $color_pal = $parameters['color_pal'];
        $n_genes = $parameters['n_genes'];
        $hm_display_genes = $parameters['hm_display_genes'];

        $plots = $this->getExportFilesCommands('pseudo_bulk_pca', "plist\$pca");
        $plots .= $this->getExportFilesCommands('pseudo_bulk_heatmap', "plist\$heatmap");

        $script = "
setwd('/spatialGE')
# Load the package
library('svglite')
library('spatialGE')

# Load normalized STList
" .
$this->_loadStList($stlist)
. "

#### Box plot
#pca = pseudobulk_pca($stlist, plot_meta='$plot_meta', n_genes=$n_genes, color_pal='$color_pal', ptsize=7)

plist = pseudobulk_plots($stlist, plot_meta='$plot_meta', max_var_genes=$n_genes, hm_display_genes=$hm_display_genes, color_pal='$color_pal', ptsize=5)
#hm_display_genes --> text or slider

#ggpubr::ggexport(filename = 'pseudo_bulk_pca.png', plist\$pca, width = 800, height = 800)
#ggpubr::ggexport(filename = 'pseudo_bulk_heatmap.png', plist\$heatmap, width = 800, height = 800)

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

        $parameterNames = ['quilt_plot_1', 'quilt_plot_2'];
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

        $plot_meta = $parameters['plot_meta'];
        $color_pal = $parameters['color_pal'];
        $sample1 = $parameters['sample1'];
        $sample2 = $parameters['sample2'];

        $plots = $this->getExportFilesCommands('quilt_plot_1', "plist1[[1]]");
        $plots .= $this->getExportFilesCommands('quilt_plot_2', "plist2[[1]]");

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList
" .
$this->_loadStList($stlist)
. "

#### Plot
plist1 = STplot($stlist, samples=c('$sample1'), plot_meta='$plot_meta', color_pal='$color_pal', ptsize=2)
plist2 = STplot($stlist, samples=c('$sample2'), plot_meta='$plot_meta', color_pal='$color_pal', ptsize=2)
#ggpubr::ggexport(filename = 'quilt_plot_1.png', plist1[[1]], width = 800, height = 800)
#ggpubr::ggexport(filename = 'quilt_plot_2.png', plist2[[1]], width = 800, height = 800)

$plots

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
        $col_pal = $parameters['col_pal'];

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

stlist_expression_surface = gene_interpolation(normalized_stlist, genes=$_genes)
krp = STplot_interpolation(stlist_expression_surface, genes=$_genes, color_pal='$col_pal')

$export_files

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
        $method = $parameters['method'];
        $color_pal = $parameters['color_pal'];
        $plot_meta = $parameters['plot_meta'];

        $_genes = "c('" . join("','", $genes) . "')";
        $_method = "c('" . join("','", $method) . "')";

        $export_files = $this->getExportFilesCommands("sthet_plot", "sthet_plot");

        $script = "

setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load normalized STList
" .
$this->_loadStList('normalized_stlist')
. "

stlist_sthet = SThet(normalized_stlist, genes=$_genes, method=$_method)
sthet_plot = compare_SThet(stlist_sthet, samplemeta='$plot_meta', genes=$_genes, color_pal='$color_pal')

$export_files

";

        return $script;
    }


    private function getExportFilesCommands($file, $plot) : string {

        $str = "if(!is.null($plot)){\n";

        //PNG
        $str .= "ggpubr::ggexport(filename = '$file.png', $plot, width = 800, height = 800)\n";

        //PDF
        $str .= "ggpubr::ggexport(filename = '$file.pdf', $plot, width = 8, height = 8)\n";

        //SVG
        $str .= "library('svglite')\n";
        $str .= "svglite('$file.svg', width = 8, height = 8)\n";
        $str .= "print($plot)\n";
        $str .= "dev.off()\n";

        $str .= "}\n\n";

        return $str;
    }


    public function createJob($description, $command, $parameters) : int {

        //create the job instance
        $job = new RunScript($description, $this, $command, $parameters);
        //push the job to que queue and get the jobId
        $jobId = Queue::connection()->push($job);

        //save the jobId to the project parameters table
        ProjectParameter::updateOrCreate(['parameter' => 'job.' . $command, 'project_id' => $this->id], ['type' => 'number', 'value' => $jobId]);
        //Set the email notification off by default
        $this->setJobEmailNotification($command, 0);

        return $jobId;

    }

    public function setJobEmailNotification($command, $sendEmail) {

        ProjectParameter::updateOrCreate(['parameter' => 'job.' . $command . '.email', 'project_id' => $this->id], ['type' => 'number', 'value' => $sendEmail ? 1 : 0]);

    }

    public function getJobPositionInQueue($jobId) : int {

        $queueName = env('QUEUE_DEFAULT_NAME', 'default');

        $queuePosition = DB::table('jobs')
            ->where('queue', $queueName)
            ->where('id', '<=', $jobId)
            ->count();

        return $queuePosition;
    }


}



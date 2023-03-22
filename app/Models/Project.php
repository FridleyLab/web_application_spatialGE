<?php

namespace App\Models;

use App\Http\Controllers\spatialContainer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects';

    protected $fillable = ['name', 'description', 'user_id'];

    protected $appends = ['url'];

    private ?spatialContainer $_container = null;

    //Relations
    public function samples(): BelongsToMany
    {
        return $this->belongsToMany(Sample::class);
    }

    //Attributes
    public function getUrlAttribute() {
        return route('open-project', ['project' => $this->id]);
    }


    public function getCurrentStepUrl() {
        if($this->current_step === 1)
            return route('import-data', ['project' => $this->id]);

        if($this->current_step === 2)
            return route('qc-data-transformation', ['project' => $this->id]);


        return '/';
    }


    public function createStList() {

        $workingDir = '/users/' . (auth()->id() ?? '9999') . '/' . $this->id . '/';
        $workingDir = str_replace('\\', '/', $workingDir);

        $script = $workingDir . 'STList.R';



        Storage::put($script, $this->getStListScript());

        //dd($script);

        $this->spatialExecute('Rscript STList.R');

    }

    public function spatialExecute($command) {

        if(is_null($this->_container))
            $this->_container = new spatialContainer($this);

        //dd($this->_container);

        $this->_container->execute($command);

    }






    public function getStListScript() : string {

        $sampleDirs = $this->samples()->pluck('samples.id')->join("/','");
        $sampleDirs = "'" . $sampleDirs . "/'";

        $sampleNames = "'" . $this->samples()->pluck('samples.id')->join("','") . "'";

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Specify paths to directories containing data
count_files = c($sampleDirs)

# Specify sample names
samplenames = c($sampleNames)

# Create STlist
initial_stlist <- STlist(rnacounts=count_files, samples=samplenames)

#Save the STList to disk
save(initial_stlist, file='initial_stlist.RData')
";

        return $script;

    }


    public function applyFilter() {

        $workingDir = '/users/' . (auth()->id() ?? '9999') . '/' . $this->id . '/';
        $workingDir = str_replace('\\', '/', $workingDir);

        $script = $workingDir . 'Filter.R';



        Storage::put($script, $this->getFilterDataScript());

        //dd($script);

        $this->spatialExecute('Rscript Filter.R');

    }


    public function getFilterDataScript() : string {

        $sampleDirs = $this->samples()->pluck('samples.id')->join("/','");
        $sampleDirs = "'" . $sampleDirs . "/'";

        $sampleNames = "'" . $this->samples()->pluck('samples.id')->join("','") . "'";

        $script = "
setwd('/spatialGE')
# Load the package
library('spatialGE')

# Load STList from disk
load(file='initial_stlist.RData')

# Apply defined filter to the initial STList
filtered_stlist = filter_data(initial_stlist, spot_minreads=1000, spot_maxreads=20000)
save(filtered_stlist, file='filtered_stlist.RData')
";

        return $script;

    }



}

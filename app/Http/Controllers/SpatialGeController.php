<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

use Illuminate\Support\Facades\Process;
use Spatie\Docker\DockerContainer;
use Spatie\Docker\DockerContainerInstance;

class SpatialGeController extends Controller
{

    public function createScript(Project $project)
    {

        $spot_minreads = strlen(request('spot_minreads')) ? request('spot_minreads') : 4000;
        $spot_maxreads = strlen(request('spot_maxreads')) ? request('spot_maxreads') : 4500;

        $selectedSample = strlen(request('sample_id')) ? request('sample_id') : $project->samples()->pluck('samples.id')->toArray()[0];

        $workingDir = Storage::path('/users/' . (auth()->id() ?? '1') . '/');
        $workingDir = str_replace('\\', '/', $workingDir);


        //$sampleDirs = $project->samples()->pluck('samples.id')->join("/','" . $workingDir);
        $sampleDirs = $project->samples()->pluck('samples.id')->join("/','");
        $sampleDirs = "'" . $sampleDirs . "/'";

        $sampleNames = "'" . $project->samples()->pluck('samples.id')->join("','") . "'";

        //return $sampleDirs;




$script =
"
setwd('$workingDir')
# Load the package
library('spatialGE')


# Load STList from disk
load(file='initial_stlist.RData')

# Filter all samples within STlist by removing spots with less than 100 genes
#filtered_stlist = filter_data(initial_stlist, spot_minreads=$spot_minreads, spot_maxreads=$spot_maxreads)
load(file='filtered_stlist.RData')

#Save the filtered STList to disk
#save(filtered_stlist, file='filtered_stlist.RData')

# Generate plots of total genes per spot (only sample 1 'sample_120d')
## Unfiltered STlist
p1 = STplot(initial_stlist, samples='$selectedSample', plot_meta='total_counts', color_pal='YlOrRd')
## Filtered STlist
p2 = STplot(filtered_stlist, samples='$selectedSample', plot_meta='total_counts', color_pal='YlOrRd')

# Plot side by side (the fuction ggpubr::ggarrange is not part of spatialGE)
result <- ggpubr::ggarrange(p1[[1]], p2[[1]], nrow=1)
ggpubr::ggexport(filename = \"result.png\", result)
";



        $scriptFileName = '/users/' . (auth()->id() ?? '1') . '/script.R';

        Storage::put($scriptFileName, $script);



        try {

            $executable = '"C:/Program Files/R/R-4.1.2/bin/RScript.exe"';

            $result = Process::run("$executable $workingDir/script.R");

            $image = file_get_contents($workingDir . 'result.png');

            return ['image' => base64_encode($image), 'output' => $result->output()];
        }
        catch(\Exception $e)
        {
            return $e->getMessage();
        }




    }


    public function createSTList(Project $project) {

        $workingDir = Storage::path('/users/' . (auth()->id() ?? '1') . '/');
        $workingDir = str_replace('\\', '/', $workingDir);


        $sampleDirs = $project->samples()->pluck('samples.id')->join("/','");
        $sampleDirs = "'" . $sampleDirs . "/'";

        $sampleNames = "'" . $project->samples()->pluck('samples.id')->join("','") . "'";




        $script = "
setwd('$workingDir')
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

        $scriptFileName = '/users/' . (auth()->id() ?? '1') . '/script.R';

        Storage::put($scriptFileName, $script);

        try {

            $executable = '"C:/Program Files/R/R-4.1.2/bin/RScript.exe"';

            $result = Process::run("$executable $workingDir/script.R");


            return ['output' => $result->output()];
        }
        catch(\Exception $e)
        {
            return $e->getMessage();
        }


    }

    public function test() {
        //$container = DockerContainer::create('spatialge', 'spatial101')->setOptionalArgs('-d', '-it')->start();

        //$p = $container->execute('R --version');

        //return $p->getOutput();

        //return $container->getConfig()

        try {
            $container = new DockerContainer('', '');

            $instance = new DockerContainerInstance($container, 'e7d4a860a5b6', '');

            $p = $instance->execute("ls -la");

            echo $p->getOutput();

            //$instance->stop();



            //$instance->inspect();
        }
        catch(\Exception $e)
        {
            echo $e->getMessage();
        }


    }


}


<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

use Illuminate\Support\Facades\Process;

class QcController extends Controller
{

    public function createScript(Project $project)
    {
        $workingDir = Storage::path('/users/' . (auth()->id() ?? '1') . '/');
        $workingDir = str_replace('\\', '/', $workingDir);


        //$sampleDirs = $project->samples()->pluck('samples.id')->join("/','" . $workingDir);
        $sampleDirs = $project->samples()->pluck('samples.id')->join("/','");
        $sampleDirs = "'" . $sampleDirs . "/'";

        $sampleNames = "'" . $project->samples()->pluck('samples.id')->join("','") . "'";

        //return $sampleDirs;


        $firstSample = $project->samples()->pluck('samples.id')->toArray()[0];


        $script = "
setwd('$workingDir')
# Load the package
library('spatialGE')

# Specify paths to directories containing data
count_files = c($sampleDirs)

# Specify sample names
samplenames = c($sampleNames)

# Create STlist
example_stlist <- STlist(rnacounts=count_files, samples=samplenames)

# Filter all samples within STlist by removing spots with less than 100 genes
example_stlist_filtered = filter_data(example_stlist, spot_minreads=4000)

# Generate plots of total genes per spot (only sample 1 'sample_120d')
## Unfiltered STlist
p1 = STplot(example_stlist, samples='$firstSample', plot_meta='total_counts', color_pal='YlOrRd')
## Filtered STlist
p2 = STplot(example_stlist_filtered, samples='$firstSample', plot_meta='total_counts', color_pal='YlOrRd')

# Plot side by side (the fuction ggpubr::ggarrange is not part of spatialGE)
result <- ggpubr::ggarrange(p1[[1]], p2[[1]], nrow=1)
ggpubr::ggexport(filename = \"result.png\", result)
";

        $scriptFileName = '/users/' . (auth()->id() ?? '1') . '/script.R';

        Storage::put($scriptFileName, $script);

        //return $script;

        $result = Process::run("rscript $workingDir/script.R");

        return $result->output();
    }


}

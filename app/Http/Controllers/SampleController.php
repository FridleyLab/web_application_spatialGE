<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\View\View;

class SampleController extends Controller
{

    public function store() : Sample
    {
        $fileIds = request('file_ids');
        $projectId = request('project_id');

        $sample = new Sample();
        $sample->save();
        $sample->projects()->save(Project::findOrFail($projectId));
        $sample->save();

        foreach($fileIds as $fileId) {
            if($fileId !== 0) {
                $file = File::findOrFail($fileId);
                $sample->files()->save($file);
            }
        }

        return  $sample;
    }


}

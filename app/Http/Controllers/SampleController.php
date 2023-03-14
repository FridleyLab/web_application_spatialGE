<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SampleController extends Controller
{

    public function store()
    {

        if(request()->file('files')) {

            DB::transaction(function () {
                $projectId = request('project_id');
                $sample_name = request('sample_name');
                $sample = Sample::create(['name' => $sample_name]);
                $sample->save();
                $sample->projects()->save(Project::findOrFail($projectId));
                $fileType = null;

                foreach(request()->file('files') as $key => $file) {

                    $types = ['expressionFile', 'coordinatesFile', 'imageFile', 'scaleFile'];
                    foreach ($types as $type) {
                        if(request()->has($type) && request($type) === $file->getClientOriginalName())
                            $fileType = $type;
                    }

                    $file = File::create(['filename' => $file->getClientOriginalName(), 'type' => $fileType]);
                    $sample->files()->save($file);
                }
            });


        }

        return response('Sample stored successfully');


//        $fileIds = request('file_ids');
//        $projectId = request('project_id');
//
//        $sample = new Sample();
//        $sample->save();
//        $sample->projects()->save(Project::findOrFail($projectId));
//        $sample->save();
//
//        foreach($fileIds as $fileId) {
//            if($fileId !== 0) {
//                $file = File::findOrFail($fileId);
//                $sample->files()->save($file);
//            }
//        }
//
//        return  $sample;
    }


    public function destroy(Sample $sample) {

        ProjectSample::where('sample_id', $sample->id)->delete();
        $sample->delete();

        return 'OK';
    }


}

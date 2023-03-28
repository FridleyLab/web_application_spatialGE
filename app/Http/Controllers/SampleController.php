<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SampleController extends Controller
{

    public function store()
    {

        $userFolder = auth()->user()->getuserFolder();


        if(request()->file('files')) {

            DB::transaction(function () use($userFolder) {
                $projectId = request('project_id');
                $sample_name = request('sample_name');
                $sample = Sample::create(['name' => $sample_name]);
                if(!strlen(trim($sample->name))) { //if no name was provided for the sample
                    $count = Project::findOrFail($projectId)->samples->count() + 1;
                    $sample->name = 'Sample' . ($count > 9 ? $count : '0' . $count);
                }
                $sample->save();

                $sample->projects()->save(Project::findOrFail($projectId));
                $fileType = null;


                $projectFolder = $userFolder . $projectId . '/';
                Storage::createDirectory($projectFolder);
                $sampleFolder = $projectFolder . $sample->name . '/';
                $sampleFolderSpatial = $sampleFolder . 'spatial/';
                Storage::createDirectory($sampleFolder);
                Storage::createDirectory($sampleFolderSpatial);

                foreach(request()->file('files') as $key => $file) {

                    $types = ['expressionFile', 'coordinatesFile', 'imageFile', 'scaleFile'];
                    foreach ($types as $type) {
                        if(request()->has($type) && request($type) === $file->getClientOriginalName())
                            $fileType = $type;
                    }

                    $fileModel = File::create(['filename' => $file->getClientOriginalName(), 'type' => $fileType]);
                    $sample->files()->save($fileModel);

                    if($fileType === 'expressionFile')
                        $file->storeAs($sampleFolder, $file->getClientOriginalName());
                    if($fileType === 'coordinatesFile')
                        $file->storeAs($sampleFolderSpatial, $file->getClientOriginalName());
                    if($fileType === 'scaleFile')
                        $file->storeAs($sampleFolder, $file->getClientOriginalName());
                    if($fileType === 'imageFile')
                        $file->storeAs($sampleFolderSpatial, $file->getClientOriginalName());

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

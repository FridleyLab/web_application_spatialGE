<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Facades\File as _File;

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
                        $file->storeAs($sampleFolderSpatial, $file->getClientOriginalName());
                    if($fileType === 'imageFile')
                        $file->storeAs($sampleFolderSpatial, $file->getClientOriginalName());

                }
            });


        }

        return response('Sample stored successfully');

    }


    public function destroy(Sample $sample) {

        ProjectSample::where('sample_id', $sample->id)->delete();
        $sample->delete();

        return 'OK';
    }

    private function renameDirectory($base, $old, $new) {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if ($isWindows) {
            // Use 'ren' command for Windows
            $command = "ren \"$old\" \"$new\"";
        } else {
            // Use 'mv' command for Unix-based systems
            $command = "mv \"$old\" \"$new\"";
        }



        $command = 'cd "' . $base . '" && ' . $command;


        exec($command, $output, $returnCode);

        return $output;

        if ($returnCode === 0) {
            echo "Directory renamed successfully.";
        } else {
            echo "Error renaming the directory.";
        }
    }


    public function rename(Sample $sample) {

        try {

            $userFolder = auth()->user()->getuserFolder();
            $projectFolder = $userFolder . $sample->projects[0]->id . '/';
            $newFolderName = $projectFolder . request('new_name') . '/';

            $command = 'cp -r ' . $sample->name . ' ' . request('new_name');
            $sample->projects[0]->spatialExecute($command);

            if(Storage::directoryExists($newFolderName)) {
                $command = 'rm -fr ' . $sample->name;
                $sample->projects[0]->spatialExecute($command);
                $sample->name = request('new_name');
                $sample->save();
            }

            return $command;





            $newFolderName = Storage::path($newFolderName);
            $newFolderName = str_replace(':', '', $newFolderName);
            $newFolderName = str_replace('\\', '/', $newFolderName);
            $newFolderName = '/' . $newFolderName;

            $sampleFolder = Storage::path($sampleFolder);
            $sampleFolder = str_replace(':', '', $sampleFolder);
            $sampleFolder = str_replace('\\', '/', $sampleFolder);
            $sampleFolder = '/' . $sampleFolder;


            _File::copyDirectory($sampleFolder, $newFolderName);

            return $sampleFolder . '||||' . $newFolderName;

            //Storage::move($sampleFolder, $newFolderName);

            //$fs = new Filesystem();
            //$fs->moveDirectory(Storage::path($sampleFolder), Storage::path($newFolderName));

            //return response(is_dir(Storage::path($sampleFolder)));

            return $this->renameDirectory(Storage::path($projectFolder), $sample->name, request('new_name'));

            return 'OK';

            $sample->name = request('new_name');
            $sample->save();

            return response('Sample renamed |' . Storage::path($sampleFolder) . '|' . Storage::path($newFolderName));
        }
        catch(\Exception $e)
        {
            return response('Error renaming sample [' . $sample->name . ']: ' . $e->getMessage(), 500);
        }

    }

    public function get_image(Sample $sample) {

        $filename = $sample->image_file_path();

        if (!$sample->has_image || !Storage::fileExists($filename)) {
            abort(404);
        }

        $file = Storage::get($filename);
        $mimeType = Storage::mimeType($filename);

        return response($file, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $sample->name . '"',
        ]);
    }


}

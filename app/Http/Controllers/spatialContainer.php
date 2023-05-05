<?php

namespace App\Http\Controllers;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\throwException;

class spatialContainer {

    private string $exe;
    private Project $project;

    /**
     * @param $container_id
     * @param Project $project
     */
    public function __construct(Project $project)
    {

        $this->exe = '"' . env('DOCKER_EXECUTABLE' . ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? '_WINDOWS' : '')) . '"';

        $this->project = $project;

        //$this->checkIfRunningOrCreate();

    }

    public function checkIfRunningOrCreate() : void {
        if(!$this->checkIfRunning())
            $this->createContainer();
    }

    private function createContainer() : void {

        $image_name = env('DOCKER_IMAGE_NAME','spatialge');

        $container_id = 'spatial_' . $this->project->id;


        try {

            $workingDir = Storage::path('/users/' . $this->project->user_id . '/' . $this->project->id . '/');
            $workingDir = str_replace(':', '', $workingDir);
            $workingDir = str_replace('\\', '/', $workingDir);
            $workingDir = '/' . $workingDir;

            $exe = $this->exe;
            $command = "$exe container run -it -v $workingDir:/spatialGE --rm --name $container_id -d $image_name";

            Log::info($command);

            $process = Process::run($command);

            Log::info($process->output() . '/*/' . $process->errorOutput());

            $ok = true;
            $checks = ['Error', 'error', 'already', 'rename'];
            foreach ($checks as $check)
                if (str_contains($process->output(), $check))
                    $ok = false;

            if(!$process->successful() || !$ok)
                throwException(new \Exception("Error creating container for project with id:" . $this->project->id));

            $this->project->container_id = $container_id;
            $this->project->save();

        }
        catch(\Exception $e) {
            throwException(new \Exception("Error creating container for project with id:" . $this->project->id));
        }
    }

    private function checkIfRunning() : bool {

        if(is_null($this->project->container_id))
            return false;

        try {
            $output = $this->execute('R --version');

            //dd($output);

            $checks = ['R version', 'Statistical', 'Copyright', 'Foundation'];
            foreach ($checks as $check)
                if (str_contains($output, $check))
                    return true;
        } catch( \Exception $e)
        {
            return false;
        }

        return false;

    }

    private function _OLD_execute($command) {
        try {

            //set the max execution time for the system call
            $timeout = 1800;

            $exe = $this->exe;
            $_command = "$exe exec " . $this->project->container_id . ' ' . $command;

            Log::info("\nCOMMAND TO EXECUTE: " . $_command . "\n");

            $process = Process::timeout($timeout)->run($_command);


            $output = "\n+++++++++++++++++OUTPUT START+++++++++++++++++\n";
            $output .= trim($process->output() . "\n" . $process->errorOutput());
            $output .= "\n++++++++++++++++OUTPUT END++++++++++++++++++\n";

            Log::info($output);

            return $output;
        }
        catch (\Exception $e) {
            $errorMessage = 'spatialGE Error: Could not execute command: "' . $command . '" in container with id: ' . $this->project->container_id;
            Log::info("***$$$ EXCEPTION: \n" . $errorMessage);
            return throwException(new \Exception($errorMessage));
        }
    }



    public function execute($docker_command) {

        //set the max execution time for the system call
        $timeout = 1800;

        $image_name = env('DOCKER_IMAGE_NAME','spatialge');

        $container_id = 'spatial_' . $this->project->id;


        try {

            $workingDir = Storage::path('/users/' . $this->project->user_id . '/' . $this->project->id . '/');
            $workingDir = str_replace(':', '', $workingDir);
            $workingDir = str_replace('\\', '/', $workingDir);
            $workingDir = '/' . $workingDir;

            $exe = $this->exe;
            $command = "$exe container run -i -v $workingDir:/spatialGE --rm --name $container_id $image_name $docker_command";

            Log::info("\nCOMMAND TO EXECUTE: " . $command . "\n");

            $process = Process::timeout($timeout)->run($command);

            $output = "\n+++++++++++++++++OUTPUT START+++++++++++++++++\n";
            $output .= trim($process->output() . "\n" . $process->errorOutput());
            $output .= "\n++++++++++++++++OUTPUT END++++++++++++++++++\n";

            Log::info($output);

            return $output;

        }
        catch(\Exception $e) {
            $errorMessage = 'spatialGE Error: Could not execute command: "' . $command . '" in container with id: ' . $this->project->container_id;
            Log::info("***$$$ EXCEPTION: \n" . $errorMessage);
            return throwException(new \Exception($errorMessage));
        }
    }


}

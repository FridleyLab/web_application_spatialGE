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


    private function isWindows() : bool {
        return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    }

    /**
     * @param $container_id
     * @param Project $project
     */
    public function __construct(Project $project)
    {

        $this->exe = $this->getDockerExecutable(); //'"' . env('DOCKER_EXECUTABLE' . ($this->isWindows() ? '_WINDOWS' : ''), 'docker') . '"';

        $this->project = $project;

        //$this->checkIfRunningOrCreate();

    }

    public function checkIfRunningOrCreate() : void {
        if(!$this->checkIfRunning())
            $this->createContainer();
    }

    private function createContainer() : void {

        $image_name = env('DOCKER_IMAGE_NAME','spatialge');

        $container_id = 'spatial_' . $this->project->user->id . '_' . $this->project->id . '_' . substr(microtime(true) * 1000, 0, 13);


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

    /*private function _OLD_execute($command) {
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
    }*/


    public function getDockerExecutable() {
        return '"' . env('DOCKER_EXECUTABLE' . ($this->isWindows() ? '_WINDOWS' : ''), 'docker') . '"';
    }

    public function execute($docker_command, $task_id = '') {

        //set the max execution time for the system call
        $timeout = env('MAX_EXECUTION_TIME',259200);

        $image_name = env('DOCKER_IMAGE_NAME','spatialge');

        //load memory limit parameters
        $physical_memory = env('DOCKER_MAX_PHYSICAL_MEMORY', '4g');
        $total_memory = env('DOCKER_MAX_TOTAL_MEMORY', '12g');

        $container_id = $task_id;
        if(!strlen($container_id)) $container_id = 'spatialGE_' . $this->project->user->id . '_' . $this->project->id . '_' . substr(microtime(true) * 1000, 0, 13);

        $command = '';
        try {

            //$storage_path = '/users/' . $this->project->user_id . '/' . $this->project->id . '/';
            $storage_path = $this->project->workingDir();
            $workingDir = Storage::path($storage_path);

            $log_file = "$workingDir$container_id.log";

            //Transform the path to map inside the container (Windows paths)
            $workingDir = str_replace(':', '', $workingDir);
            $workingDir = str_replace('\\', '/', $workingDir);
            $workingDir = '/' . $workingDir;


            $script = 'cd /spatialGE' . "\n";
            $script .= $docker_command . "\n";
            $script .= 'R --quiet -e "print(\'$container_id - spatialGE_PROCESS_COMPLETED\')"';
            $script_file = "$task_id.sh";
            Storage::put($storage_path . $script_file, $script);


            $exe = $this->getDockerExecutable();
            //$command = "$exe container run -i -v $workingDir:/spatialGE --rm --memory $physical_memory --memory-swap $total_memory --name $container_id $image_name $docker_command > \"$log_file\" 2>&1 & R --quiet -e \"print('$container_id - spatialGE_PROCESS_COMPLETED')\" >> \"$log_file\" 2>&1";
            $command = "$exe container run -i -v $workingDir:/spatialGE --rm --memory $physical_memory --memory-swap $total_memory --name $container_id $image_name sh $script_file";

            //$log_contents = Storage::get("$storage_path$container_id.log");

            Log::info("\nCOMMAND TO EXECUTE: " . $command . "\n");

            $process = Process::timeout($timeout)->run($command);

            $output = "\n+++++++++++++++++OUTPUT START+++++++++++++++++\n";
            //$output .= $log_contents;
            $output .= trim($process->output() . "\n" . $process->errorOutput());
            $output .= "\n++++++++++++++++OUTPUT END++++++++++++++++++\n";

            Log::info($output);

            return $output;

        }
        catch(\Exception $e) {
            $errorMessage = 'spatialGE Error: Could not execute command: "' . $command . '" in container with id: ' . $container_id;
            Log::info("ERROR ***$$$ EXCEPTION: \n" . $errorMessage);
            Log::info("ERROR ***$$$ EXCEPTION MESSAGE: \n" . $e->getMessage());
            return $errorMessage . "\n ERROR ***$$$ EXCEPTION MESSAGE: \n" . $e->getMessage();
            //return throwException(new \Exception($errorMessage));
        }
    }

    public function killProcess($task_id): bool
    {
        $exe = $this->getDockerExecutable();
        $command = "$exe kill $task_id";
        try {
            $process = Process::timeout(60)->run($command);
            Log::warning("Process $task_id killed [$command], output: {$process->output()}");
            return true;
        } catch (\Exception $e)
        {
            Log::error("Error killing process $task_id: {$e->getMessage()}");
            return false;
        }
    }


}

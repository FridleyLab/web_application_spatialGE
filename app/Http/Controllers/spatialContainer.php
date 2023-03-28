<?php

namespace App\Http\Controllers;
use App\Models\Project;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\throwException;

class spatialContainer {

    private string $container_id;
    private Project $project;

    /**
     * @param $container_id
     * @param Project $project
     */
    public function __construct(Project $project)
    {


        set_include_path(env('DOCKER_EXECUTABLE'));
        putenv(env('DOCKER_EXECUTABLE'));

        //dd(env('DOCKER_EXECUTABLE'));

        $this->project = $project;

        $this->checkIfRunningOrCreate();

    }

    public function checkIfRunningOrCreate() : void {
        if(!$this->checkIfRunning())
            $this->createContainer();
    }

    private function createContainer() : void {

        $container_id = 'march-spatial_' . $this->project->id;

        try {

            $workingDir = Storage::path('/users/' . (auth()->id() ?? '9999') . '/' . $this->project->id . '/');
            $workingDir = str_replace(':', '', $workingDir);
            $workingDir = str_replace('\\', '/', $workingDir);
            $workingDir = '/' . $workingDir;

            $exe = '"C:/Program Files/Docker/Docker/resources/bin/docker.exe"';
            $command = "$exe container run -it -v $workingDir:/spatialGE --rm --name $container_id -d testing";
            $process = Process::run($command);

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

    public function execute($command) {
        try {

            $exe = '"C:/Program Files/Docker/Docker/resources/bin/docker.exe"';
            $process = Process::run("$exe exec " . $this->project->container_id . ' ' . $command);

            //dd($process->errorOutput());

            return $process->output();
        }
        catch (\Exception $e) {
            return throwException(new \Exception('spatialGE Error: Could not execute command: "' . $command . '" in container with id: ' . $this->project->container_id));
        }
    }


}

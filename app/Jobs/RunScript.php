<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Task;
use App\Notifications\ProcessCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class RunScript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $description,
        public Project $project,
        public string $command,
        public $parameters
    )
    {
        //
    }


    private function isWindows() : bool {
        return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //Execute the process
        try {
            $command = $this->command;
            Log::info('**### BEGIN** ' . $command);
            Log::info("\nPARAMS: " . json_encode($this->parameters));
            $result = $this->project->$command($this->parameters);
            Log::info('**END** ' . $command);
        } catch(\Exception $e) {
            Log::error('ERROR executing [' . $command . ']: ' . $e->getMessage());
        }

        $commandline = '';
        try {
            if(!$this->isWindows()) { //change folder permissions so the generated files and plots can be viewed and downloaded
                $public_dir = Storage::path('/public/users/' . $this->project->user_id . '/');
                $commandline = 'chmod -R 755 ' . $public_dir;
                $process = Process::run($commandline);
            }
        }
        catch(\Exception $e) {
            Log::error('Error running:"' . $commandline . '"' . "\n" . 'Error message: ' . $e->getMessage());
        }



        //check if the task completed correctly
        if(isset($this->parameters['__task']) && !isset($this->parameters['executeIn'])) {
            $task = Task::where('task', $this->parameters['__task'])->firstOrFail();
            if($task->completed) {
                //Notify the user if requested
                try {
                    //Load the project again because this class instance was loaded when the process started and the user could've changed their mind about being notified via email
                    $this->project->fresh();

                    //Check if the user requested to be notified via email
                    $key = "job.{$this->command}.email";
                    if( array_key_exists($key, $this->project->project_parameters) && intval($this->project->project_parameters[$key]))
                        $this->project->user->notify(new ProcessCompleted($this->project, $this->description, $result['output'], array_key_exists('script', $result) ? $result['script'] : ''));
                }
                catch (\Exception $e)
                {
                    Log::error('ERROR notifying user ' . $this->project->user->email . ' of process "' . $this->description . '", MESSAGE:' . $e->getMessage());
                }
            }
            else { //job failed, try again in max attempts not reached yet
                if($task->attempts < 3 && is_null($task->cancelled_at)) { //TODO: create env variable for MAX_JOB_ATTEMPTS
                    $payload = json_decode($task->payload);
                    $this->project->createJob($payload->description, $payload->command, get_object_vars($payload->parameters), $payload->queue);
                }
                else { //TODO: send notification about process failed to the user and copy to TWO app admins
                    null;
                }
            }
        }


    }
}

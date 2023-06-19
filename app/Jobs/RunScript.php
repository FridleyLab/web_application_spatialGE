<?php

namespace App\Jobs;

use App\Mail\notifyProcessCompleted;
use App\Models\Project;
use App\Models\ProjectParameter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        $command = $this->command;
        Log::info('**### BEGIN** ' . $command);
        Log::info("\nPARAMS: " . json_encode($this->parameters));
        $result = $this->project->$command($this->parameters);
        Log::info('**END** ' . $command);

        try {
            if(!$this->isWindows()) { //change folder permissions so the generated files and plots can be viewed and downloaded
                $public_dir = Storage::path('/public/users/' . $this->project->user_id . '/');
                //$commandline = 'chown -R apache:apache ' . $public_dir;
                $commandline = 'chmod -R 755 ' . $public_dir;
                $process = Process::run($commandline);

                //Log CHMOD output -- when debugging
                /*$chownout = "\n+++++++++++++++++CHOWN+++++++++++++++++\n";
                $chmodout .= "COMMAND: $commandline\n";
                $chmodout .= trim($process->output() . "\n" . $process->errorOutput());
                $chmodout .= "\n++++++++++++++++CHOWN END++++++++++++++++++\n";
                Log::info($chmodout);*/
            }
        }
        catch(\Exception $e) {}


        //Notify the user if requested
        try {
            //Load the project again because this class' instance was loaded when the process started and the user could've changed their mind
            $this->project->fresh();
            //$project = Project::findOrFail($this->project->id);

            //Check if the user requested to be notified via email
            $key = "job.{$this->command}.email";
            if( array_key_exists($key, $this->project->project_parameters) && intval($this->project->project_parameters[$key]))
                Mail::to($this->project->user->email)->send(new notifyProcessCompleted($this->project, $this->description, $this->project->user->is_admin ? $result['output'] : ''));
        }
        catch (\Exception $e)
        {
            Log::error('ERROR notifying user ' . $this->project->user->email . ' of process "' . $this->description . '", MESSAGE:' . $e->getMessage());
        }
    }
}

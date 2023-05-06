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

        //TODO: configure the docker image to run with the same user as apache or figure a better way to grant read permissions to files
        try {
            if(!$this->isWindows()) {
                $public_dir = Storage::path('/public/users/' . $this->project->user_id . '/' /* . $this->project->id . '/' */);
                //$commandline = 'chown -R apache:apache ' . $public_dir;
                $commandline = 'chmod -R 755 ' . $public_dir;
                $process = Process::run($commandline);
                $chownout = "\n+++++++++++++++++CHOWN+++++++++++++++++\n";
                $chownout .= "COMMAND: $commandline\n";
                $chownout .= trim($process->output() . "\n" . $process->errorOutput());
                $chownout .= "\n++++++++++++++++CHOWN END++++++++++++++++++\n";
                Log::info($chownout);
            }
        }
        catch(\Exception $e) {}


        //Notify the user
        try {
            Mail::to($this->project->user->email)->send(new notifyProcessCompleted($this->project, $this->description, $result['output']));
        }
        catch (\Exception $e)
        {
            Log::error('ERROR notifying user ' . $this->project->user->email . ' of process "' . $this->description . '", MESSAGE:' . $e->getMessage());
        }
    }
}

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

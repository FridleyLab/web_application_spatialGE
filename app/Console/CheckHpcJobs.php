<?php

namespace App\Console;

use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckHpcJobs
{
    public function __invoke()
    {
        $sharedFolderBase = env('HPC_FOLDER');


        for($x=1; $x<=5; $x++) {

            $pendingHPCTasks = Task::where('completed', 0)->where('payload', 'LIKE', '%"executeIn":"HPC"%')->get();

            foreach ($pendingHPCTasks as $task) {

                if (file_exists($sharedFolderBase . $task->task . '.spatialGE.completed')) {
                    $task->completed = 1;
                    $task->finished_at = DB::raw('CURRENT_TIMESTAMP');
                    $task->save();

                    $method = $task->process . 'Completed';


                    $task->project->$method(1);
                    Log::info('C O M P L E T E D   T A S K ||||||||||||||||||||=====>');
                }

                Log::info('||||||||||||||||||||=====> PENDING SCHEDULED TASK ' . now()->format('YmdHis_u') . $task->task);

            }

            sleep(10);

        }
    }

}

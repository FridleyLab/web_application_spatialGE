<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectParameter;
use App\Models\Sample;
use App\Models\Task;
use App\Models\TaskStat;
use App\Models\User;
use App\Notifications\ContactUs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomeController extends Controller
{

    public function dashboard(): View
    {

        return view('dashboard');

    }

    public function contactUs() {

        try {

            $spatialGE = User::findOrFail(0);

            $spatialGE->notify(new ContactUs(request('subject'), request('description'), request('email')));

            return 'message sent';
        }
        catch(\Exception $e) {
            return $e->getMessage();
        }

    }

    public function download_statistics() {

        if(!auth()->user()->is_admin)
            return response('Forbidden', '401');

        //$data = DB::table('tasks')->select('tasks.*')->get();

        try {

            $data = DB::table('tasks')
                ->join('task_stats', 'tasks.task', '=', 'task_stats.task')
                ->join('users', 'tasks.user_id', '=', 'users.id')
                ->select('tasks.id', 'users.email', 'tasks.project_id', 'tasks.samples', 'tasks.process',
                    'tasks.scheduled_at', 'tasks.started_at', 'tasks.finished_at', 'task_stats.cpu',
                    'task_stats.memory', 'task_stats.timestamp')
                ->orderBy('tasks.id', 'desc')
                ->orderBy('task_stats.timestamp')
                ->get();

            $filename = 'stats_' . substr(microtime(true) * 1000, 0, 13) . '.csv';
            $handle = fopen($filename, 'w');

            if (!empty($data)) {
                $columnNames = array_keys((array)$data[0]);
                fputcsv($handle, $columnNames);
            }

            foreach ($data as $row) {
                fputcsv($handle, json_decode(json_encode($row), true));
            }

            fclose($handle);

            return response()->download($filename);
        }
        catch(\Exception $e) {
            return response($e->getMessage());
        }

    }

    public function create_test_users($prefix, $n_users, $n_samples) {

        if(!auth()->user()->is_admin)
            return response('Forbidden', '403');

        if(!(is_numeric($n_users) && is_numeric($n_samples) && $n_users > 0 && $n_samples > 0 && $n_users <= 20 & $n_samples <= 14))
            return response('Wrong parameter values');

        $suffix = '_test_user_';

        //check if there are users already created with that prefix
        $check = User::where('email', 'LIKE', '%' . $prefix . $suffix . '%')->count();
        if($check)
            return response("Test users with the prefix *$prefix* already exist, please use a different prefix!");

        $samples = ['sample_093a', 'sample_093c', 'sample_093d', 'sample_094c', 'sample_117d', 'sample_118b', 'sample_118c', 'sample_119d', 'sample_119e', 'sample_120b', 'sample_120c', 'sample_395a', 'sample_396c', 'sample_396d'];
        $metadata = '[{"name":"therapy","values":{"sample_093a":"pembrolizumab","sample_093c":"pembrolizumab","sample_118b":"pembrolizumab","sample_118c":"pembrolizumab","sample_120c":"pembrolizumab","sample_395a":"pembrolizumab","sample_093d":"ddac","sample_119d":"ddac","sample_396c":"ddac","sample_094c":"taxotere","sample_119e":"taxotere","sample_396d":"taxotere","sample_120b":"ddac","sample_117d":"taxotere"}},{"name":"race","values":{"sample_093a":"african_american","sample_093d":"african_american","sample_119d":"african_american","sample_120c":"african_american","sample_396c":"african_american","sample_093c":"non_hispanic_white","sample_094c":"non_hispanic_white","sample_117d":"non_hispanic_white","sample_120b":"non_hispanic_white","sample_396d":"non_hispanic_white","sample_395a":"non_hispanic_white","sample_119e":"non_hispanic_white","sample_118b":"african_american","sample_118c":"african_american"}},{"name":"age","values":{"sample_093a":"45","sample_093c":"12","sample_093d":"55","sample_094c":"70","sample_117d":"87","sample_118b":"26","sample_118c":"35","sample_119d":"42","sample_119e":"40","sample_120b":"38","sample_120c":"9","sample_395a":"66","sample_396c":"77","sample_396d":"57"}}]';

        $users = [];
        for($x = 1; $x <= $n_users; $x++) {
            //Create the new user
            $user = User::create(['first_name' => "test first name $x", 'last_name' => "test lastname $x",
                'email' => "$prefix$suffix$x@moffitt.org", 'email_verification_code' => '',
                'password' => Hash::make('M0ff1ttT3stUs3r.*+'), 'industry' => 1, 'interest' => 1, 'job' => 1,
                'email_verified_at' => Carbon::now()]
            );
            $users[] = $user;
            $userFolder = $user->getuserFolder();

            //Create a new project for the user
            $project = Project::create(['name' => 'Test User ' . $user->id, 'description' => 'Test project for user ' . $user->email, 'project_platform_id' => 1, 'user_id' => $user->id]);
            //Create the folder to store project files
            $projectFolder = $userFolder . $project->id . '/';
            Storage::createDirectory($projectFolder);

            //Store project metadata in file and database
            Storage::copy('common/test_samples/clinical_data.csv', $projectFolder . '/clinical_data.csv');
            ProjectParameter::updateOrCreate(['parameter' => 'metadata', 'project_id' => $project->id], ['type' => 'json', 'value' => $metadata]);

            for($s = 0; $s < $n_samples; $s++) {
                //create the new sample
                $sample = Sample::create(['name' => $samples[$s]]);
                $sample->projects()->save(Project::findOrFail($project->id));

                //Copy sample files
                File::copyDirectory(Storage::path('common/test_samples/' . $samples[$s]), Storage::path($projectFolder . $samples[$s]));
            }

            //Import samples
            $project->createJob('Data import', 'createStList', []);
        }

        return $users;

    }


    public function show_statistics() {

        if(!auth()->user()->is_admin)
            return response('Forbidden', '401');

        try {

            $data = DB::table('tasks')
                ->join('users', 'tasks.user_id', '=', 'users.id')
                ->join('task_stats', 'tasks.task', '=', 'task_stats.task')
                ->select('users.email','tasks.id','tasks.task','tasks.user_id','tasks.project_id','tasks.samples','tasks.process','tasks.completed','tasks.output','tasks.scheduled_at','tasks.started_at','tasks.finished_at', DB::raw('max(task_stats.memory) as max_ram'))
                ->groupBy('users.email','tasks.id','tasks.task','tasks.user_id','tasks.project_id','tasks.samples','tasks.process','tasks.completed','tasks.output','tasks.scheduled_at','tasks.started_at','tasks.finished_at')
                ->orderBy('tasks.id', 'desc')
                ->get();

            foreach ($data as $key => $row) {
                $scheduled = Carbon::parse($row->scheduled_at);
                $started = Carbon::parse($row->started_at);
                $finished = Carbon::parse($row->finished_at);

                $row->process_time = round($started->diffInSeconds($finished)/60, 1);
                $row->wait_time = round($scheduled->diffInSeconds($started)/60, 1);
                $row->total_time = round($scheduled->diffInSeconds($finished)/60, 1);

                $row->user = explode('@', $row->email)[0];

                $row->stats = TaskStat::where('task', $row->task)->orderBy('timestamp')->get();

            }

            if (empty($data)) {
                return response('No data available');
            }

            $headers = array_keys((array)$data[0]);
            $columns_to_remove = ['output', 'task', 'stats', 'email'];
            $headers = array_diff($headers, $columns_to_remove);

            return view('stats.summary' , compact('headers', 'data'));
        }
        catch(\Exception $e) {
            return response($e->getMessage());
        }

    }


}

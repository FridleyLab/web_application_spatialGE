<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\ProjectStatusSeeder;
use Illuminate\View\View;

class ProjectController extends Controller
{

    public function create(): View
    {

        return view('wizard.new-project');

    }

    public function store() {

        $name = request('name');
        $description = request('description');

        if(strlen(trim($name)) < 4)
            return response('Name has to be at least 4 characters long', 400);

        try {
            $project = Project::create(['name' => $name, 'description' => $description, 'user_id' => auth()->id()]);

            return response(route('import-data',['project' => $project->id]));
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    public function import_data(Project $project): View
    {

        return view('wizard.import-data')->with(compact('project'));

    }




}

<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\ProjectStatusSeeder;
use Illuminate\View\View;

class ProjectController extends Controller
{

    public function index() : View
    {
        $projects = Project::where('user_id', auth()->id())->orderByDesc('updated_at')->get();

        return view('projects.index', compact(['projects']));
    }

    public function open(Project $project) {

        setActiveProject($project);

        return redirect($project->getCurrentStepUrl());

        //return redirect()->route('import-data', ['project' => $project->id]);
    }

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

            setActiveProject($project);

            return response(route('import-data',['project' => $project->id]));
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function edit(Project $project) {

        return view('wizard.edit-project', compact(['project']));

    }

    public function update(Project $project) {
        $name = request('name');
        $description = request('description');

        if(strlen(trim($name)) < 4)
            return response('Name has to be at least 4 characters long', 400);

        $project->name = $name;
        $project->description = $description;

        $project->save();

        return response(route('my-projects'));
    }


    public function import_data(Project $project): View
    {
        $samples = $project->samples;

        return view('wizard.import-data')->with(compact('project', 'samples'));

    }

    public function qc_data_transformation(Project $project) {
        return view('wizard.qc_data_transformation')->with(compact('project'));
    }

    public function destroy(Project $project) {

        $project->delete();

        return 'OK';
    }

    public function go_to_step(Project $project, $step) {
        $project->current_step = $step;
        $project->save();

        return route('qc-data-transformation', ['project' => $project->id]);

        if($step === 2)
            return redirect()->route('qc-data-transformation', ['project' => $project->id]);
    }




}

<?php

use App\Models\Project;
use App\Models\File;

function getProjectById($project_id) {
    return Project::findOrFail($project_id);
}

function setActiveProject($project) {
    session()->put('project_id', is_object($project) ? $project->id : $project);
}

function getActiveProjectId() {
    if(session()->has('project_id') && Project::where('id', session('project_id'))->count()) {
        return session('project_id');
    } else {
        session()->forget('project_id');
        return 0;
    }

}

function getActiveProject() {
    try {
        return Project::findOrFail(getActiveProjectId());
    }
    catch(Exception $e) {
        return null;
    }
}

function getShortProjectName($project)
{
    $name = is_object($project) ? $project->name : getProjectById(session('project_id'))->name;
    return strlen($name) > 14 ? substr($name, 0, 14) . '...' : $name;
}

function userCanAccessProject($project) : bool {

    $project = is_object($project) ? $project : getProjectById($project);

    if($project->user_id === auth()->id()) {
        $project->updateLastAccess();
    }

    return $project->user_id === auth()->id() || (auth()->user()->is_admin === 1);
}

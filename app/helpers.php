<?php

use App\Models\Project;

function getProjectById($project_id) {
    return Project::findOrFail($project_id);
}

function setActiveProject($project) {
    session()->put('project_id', is_object($project) ? $project->id : $project);
}

function getActiveProjectId() {
    return session('project_id');
}

function getActiveProject() {
    return Project::findOrFail(getActiveProjectId());
}

function getShortProjectName($project)
{
    $name = is_object($project) ? $project->name : getProjectById(session('project_id'))->name;
    return strlen($name) > 14 ? substr($name, 0, 14) . '...' : $name;
}

function userCanAccessProject($project) : bool {
    return is_object($project) ? $project->user_id === auth()->id() : getProjectById($project)->user_id === auth()->id();
}

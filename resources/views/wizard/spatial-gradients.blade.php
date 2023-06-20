@extends('layout.main')


@section('content')
    <spatial-gradients
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        stgradients-url="{{ route('spatial-gradients-stgradients', ['project' => $project->id]) }}"
    >
    </spatial-gradients>
@endsection

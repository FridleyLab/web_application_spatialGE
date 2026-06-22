@extends('layout.main')


@section('content')
    <cell-cell-interaction
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        moran-multi-url="{{ route('cell-cell-interaction-moran-multi', ['project' => $project->id]) }}"
    >
    </cell-cell-interaction>
@endsection

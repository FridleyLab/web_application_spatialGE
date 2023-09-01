@extends('layout.main')


@section('content')
    <spatial-gene-set-enrichment
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        stenrich-url="{{ route('spatial-gene-set-enrichment-stenrich', ['project' => $project->id]) }}"
    >
    </spatial-gene-set-enrichment>
@endsection

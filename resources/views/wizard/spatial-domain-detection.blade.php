@extends('layout.main')


@section('content')
    <spatial-domain-detection
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        sdd-stclust-url="{{ route('sdd-stclust', ['project' => $project->id]) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"
    >
    </spatial-domain-detection>
@endsection

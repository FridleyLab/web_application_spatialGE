@extends('layout.main')


@section('content')
    <stplot-visualization
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        stplot-quilt-url="{{ route('stplot-quilt', ['project' => $project->id]) }}"
        stplot-expression-surface-url="{{ route('stplot-expression-surface', ['project' => $project->id]) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"
    >
    </stplot-visualization>
@endsection

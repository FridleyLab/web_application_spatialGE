@extends('layout.main')


@section('content')
    <sthet-spatial-het
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        sthet-url="{{ route('sthet-spatial-het-calculate', ['project' => $project->id]) }}"
        sthet-plot-url="{{ route('sthet-spatial-het-plot', ['project' => $project->id]) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"

        spark-url="{{ route('spark', ['project' => $project->id]) }}"
        :is-local="{{ app()->isLocal() }}"
    >
    </sthet-spatial-het>
@endsection

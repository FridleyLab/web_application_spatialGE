@extends('layout.main')


@section('content')
    <sthet-spatial-het
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        sthet-plot-url="{{ route('sthet-spatial-het-plot', ['project' => $project->id]) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"
    >
    </sthet-spatial-het>
@endsection

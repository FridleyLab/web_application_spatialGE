@extends('layout.main')


@section('content')
    <phenotyping
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        st-deconvolve-url="{{ route('STdeconvolve', ['project' => $project->id]) }}"
        st-deconvolve2-url="{{ route('STdeconvolve2', ['project' => $project->id]) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"
    >
    </phenotyping>
@endsection

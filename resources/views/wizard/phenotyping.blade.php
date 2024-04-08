@extends('layout.main')


@section('content')
    <phenotyping
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        st-deconvolve-url="{{ route('STdeconvolve', ['project' => $project->id]) }}"
        st-deconvolve2-url="{{ route('STdeconvolve2', ['project' => $project->id]) }}"
        st-deconvolve3-url="{{ route('STdeconvolve3', ['project' => $project->id]) }}"
        in-situ-type-url="{{ route('InSituType', ['project' => $project->id]) }}"
        in-situ-type2-url="{{ route('InSituType2', ['project' => $project->id]) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"
    >
    </phenotyping>
@endsection

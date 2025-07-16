@extends('layout.main')


@section('content')
    <degas
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"
        :tcga-variables="{{ json_encode($tcga_variables) }}"
        degas2-url="{{ route('degas2', ['project' => $project->id]) }}"

    >
</degas>
@endsection

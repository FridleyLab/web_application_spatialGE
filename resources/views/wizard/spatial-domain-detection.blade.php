@extends('layout.main')


@section('content')
    <spatial-domain-detection
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        sdd-stclust-url="{{ route('sdd-stclust', ['project' => $project->id]) }}"
        sdd-stclust-rename-url="{{ route('sdd-stclust-rename', ['project' => $project->id]) }}"
        sdd-spagcn-url="{{ route('sdd-spagcn', ['project' => $project->id]) }}"
        sdd-spagcn-svg-url="{{ route('sdd-spagcn-svg', ['project' => $project->id]) }}"
        sdd-spagcn-rename-url="{{ route('sdd-spagcn-rename', ['project' => $project->id]) }}"
        sdd-milwrm-url="{{ route('sdd-milwrm', ['project' => $project->id]) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"
    >
    </spatial-domain-detection>
@endsection

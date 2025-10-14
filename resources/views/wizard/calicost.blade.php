@extends('layout.main')


@section('content')
    <calicost
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"
        calicost2-url="{{ route('calicost2', ['project' => $project->id]) }}"
    >
    </calicost>
@endsection

@extends('layout.main')


@section('content')
    <sparkx
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        spark-url="{{ route('spark', ['project' => $project->id]) }}"
    >
    </sparkx>
@endsection

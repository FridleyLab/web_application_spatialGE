@extends('layout.main')


@section('content')
    <sparkx
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        spark-url="{{ route('spark', ['project' => $project->id]) }}"
        gmt-file-url="{{ route('getGeneSetsFromGmtFile', ['project' => $project->id]) }}"
    >
    </sparkx>
@endsection

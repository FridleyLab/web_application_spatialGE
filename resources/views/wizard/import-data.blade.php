@extends('layout.main')


@section('content')
    <import-data :project="{{ json_encode($project) }}" :samples="{{ json_encode($samples) }}" nexturl="{{ route('create-stlist',['project' => $project->id]) }}"></import-data>
@endsection

@extends('layout.main')


@section('content')
    <import-data :project="{{ json_encode($project) }}" :samples="{{ json_encode($samples) }}" nexturl="{{ route('go-to-step',['project' => $project->id, 'step' => 2]) }}"></import-data>
@endsection

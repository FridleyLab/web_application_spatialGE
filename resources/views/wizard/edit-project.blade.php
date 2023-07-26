@extends('layout.main')


@section('content')
    <new-project target-url="{{ route('update-project', ['project' => $project->id]) }}" :project="{{ json_encode($project) }}" :platforms="{{ $platforms }}"></new-project>
@endsection

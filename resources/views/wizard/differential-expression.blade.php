@extends('layout.main')


@section('content')
    <differential-expression
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        non-spatial-url="{{ route('differential-expression-non-spatial', ['project' => $project->id]) }}"
    >
    </differential-expression>
@endsection

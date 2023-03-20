@extends('layout.main')


@section('content')
    <qc-data-transformation :project="{{ json_encode($project) }}" :samples="{{ json_encode($samples) }}" filter-url="{{ route('qc-dt-filter', ['project' => $project->id]) }}"></qc-data-transformation>
@endsection

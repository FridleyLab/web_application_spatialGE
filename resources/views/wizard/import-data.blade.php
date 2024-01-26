@extends('layout.main')

@section('content')
<div></div>
    <import-data :project="{{ json_encode($project) }}" :samples="{{ json_encode($samples) }}" nexturl="{{ route('create-stlist',['project' => $project->id]) }}" qc-dt-url="{{ route('qc-data-transformation',['project' => $project->id]) }}" excel-metadata-url="{{ route('read-metadata-from-excel-file',['project' => $project->id]) }}"></import-data>
@endsection

@extends('layout.main')


@section('content')
    <qc-data-transformation :project="{{ json_encode($project) }}"></qc-data-transformation>
@endsection

@extends('layout.main')


@section('content')
    <import-data :project="{{ json_encode($project) }}"></import-data>
@endsection

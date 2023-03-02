@extends('layout.main')


@section('content')
    <import-data :project="{{ json_encode($project) }}" :samples="{{ json_encode($samples) }}"></import-data>
@endsection

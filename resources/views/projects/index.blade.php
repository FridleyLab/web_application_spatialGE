@extends('layout.main')


@section('content')
    <my-projects :projects="{{ json_encode($projects) }}"></my-projects>
@endsection

@extends('layout.main')


@section('content')
    <my-projects :projects="{{ json_encode($projects) }}" new-project-url="{{ route('new-project') }}"></my-projects>
@endsection

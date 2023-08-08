@extends('layout.main')


@section('content')
    <show-stats
        :headers="{{ json_encode($headers) }}"
        :data="{{ json_encode($data) }}"
    >
    </show-stats>
@endsection

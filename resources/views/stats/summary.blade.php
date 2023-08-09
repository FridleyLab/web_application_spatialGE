@extends('layout.main')


@section('content')
    <show-stats
        :headers="{{ json_encode($headers) }}"
        :data="{{ json_encode($data) }}"
        :plot-data="{{ json_encode($plot_data) }}"
    >
    </show-stats>
@endsection

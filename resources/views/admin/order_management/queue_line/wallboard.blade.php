@extends('admin.layouts.wallboard')

@section('title', 'Queue Line Wall Board')

@section('content')
    <livewire:queue-line.board :wallboard="true" />
@endsection

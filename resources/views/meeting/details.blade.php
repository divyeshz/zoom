@extends('layouts.app')

@section('content')
    <h1>Meeting Details</h1>
    <p><strong>Meeting ID:</strong> {{ $meeting['id'] }}</p>
    <p><strong>Topic:</strong> {{ $meeting['topic'] }}</p>
    <p><strong>Start Time:</strong> {{ $meeting['start_time'] }}</p>
    <p><strong>Agenda:</strong> {{ $meeting['agenda'] }}</p>
@endsection

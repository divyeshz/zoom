@extends('layouts.app')

@section('content')
    <h1>Create Meeting</h1>
    <form action="{{ route('meeting.store') }}" method="POST">
        @csrf
        <label for="topic">Topic:</label>
        <input type="text" id="topic" value="" name="topic" required><br><br>

        <label for="start_time">Start Time:</label>
        <input type="datetime-local" id="start_time" value="" name="start_time" required><br><br>

        <label for="agenda">Agenda:</label><br>
        <textarea id="agenda" name="agenda" value="" required></textarea><br><br>

        <button type="submit">Create Meeting</button>
    </form>
@endsection

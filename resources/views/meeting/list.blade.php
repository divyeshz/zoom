@extends('layouts.app')

@section('content')
    <h1>All Meetings</h1>
    <table border="1">
        <tr>
            <th>Meeting ID</th>
            <th>Topic</th>
            <th>Start Time</th>
            <th>Actions</th>
        </tr>
        @foreach ($meetings as $meeting)
            <tr>
                <td>{{ $meeting['id'] }}</td>
                <td>{{ $meeting['topic'] }}</td>
                <td>{{ $meeting['start_time'] }}</td>
                <td>
                    <a href="{{ route('meeting.details', $meeting['id']) }}">View Details</a>
                </td>
            </tr>
        @endforeach
    </table>
@endsection

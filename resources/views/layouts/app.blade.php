<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoom Meetings</title>
</head>
<body>
    <header>
        <!-- Add your header content here -->
        <h1>Welcome to Zoom Meetings</h1>
        <nav>
            <ul>
                <li><a href="{{ route('meeting.create') }}">Create Meeting</a></li>
                <li><a href="{{ route('meeting.list') }}">List Meetings</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Content from individual pages will be injected here -->
        @yield('content')
    </main>

    <footer>
        <!-- Add your footer content here -->
        <p>&copy; 2024 Zoom Meetings. All rights reserved.</p>
    </footer>
</body>
</html>

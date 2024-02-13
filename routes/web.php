<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome')->with('respond', 'MEETING API RESPOND WILL COME IN THIS SECTION');
});

// ZoomController routes
Route::controller('ZoomController')->group(function () {
    Route::get('start', 'index');
    Route::any('zoom-meeting-create', 'index');

    // Route for initiating the OAuth flow
    Route::get('/zoom/auth', 'get_oauth_step_1')->name('zoom.auth');

    // Route for handling OAuth callback
    Route::get('zoom/auth/callback', 'index');

    // Route for creating a meeting
    Route::get('zoom/meeting/create', 'createMeetingPage')->name('meeting.create');
    Route::post('zoom/meeting/store', 'storeMeeting')->name('meeting.store');

    // Route for getting details of a specific meeting
    Route::get('/zoom/meeting/{meetingId}', 'getMeetingDetailsPage')->name('meeting.details');

    // Route for listing all meetings
    Route::get('/zoom/meetings', 'listAllMeetingsPage')->name('meeting.list');
});

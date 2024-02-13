<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZoomController;
use App\Http\Controllers\TestZoom;

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

Route::get('start', [ZoomController::class, 'index']);
Route::any('zoom-meeting-create', [ZoomController::class, 'index']);

// Route for initiating the OAuth flow
Route::get('/zoom/auth', [ZoomController::class, 'get_oauth_step_1'])->name('zoom.auth');

// Route for handling OAuth callback
Route::get('zoom/auth/callback', [ZoomController::class, 'index']);

// Route for creating a meeting
Route::get('zoom/meeting/create', [ZoomController::class, 'createMeetingPage'])->name('meeting.create');
Route::post('zoom/meeting/store', [ZoomController::class, 'storeMeeting'])->name('meeting.store');

// Route for getting details of a specific meeting
Route::get('/zoom/meeting/{meetingId}', [ZoomController::class, 'getMeetingDetailsPage'])->name('meeting.details');

// Route for listing all meetings
Route::get('/zoom/meetings', [ZoomController::class, 'listAllMeetingsPage'])->name('meeting.list');

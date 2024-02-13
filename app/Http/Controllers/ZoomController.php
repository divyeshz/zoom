<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZoomController extends Controller
{
    /**
     * Initiate OAuth step 1 by redirecting the user to the authorization URL.
     *
     * @return \Illuminate\Http\RedirectResponse The redirection to the authorization URL.
     */
    public function get_oauth_step_1()
    {
        // Retrieve OAuth related configuration from environment variables
        $redirectURL    = env('ZOOM_REDIRECT_URL');
        $authorizeURL   = env('ZOOM_AUTHORIZE_URL');
        $clientID       = env('ZOOM_CLIENT_ID');

        // Construct the authorization URL with necessary parameters
        $authURL = $authorizeURL . '?client_id=' . $clientID . '&redirect_uri=' . $redirectURL . '&response_type=code&scope=meeting:write meeting:read&state=xyz';

        // Redirect the user to the authorization URL
        return redirect()->away($authURL);
    }


    /**
     * Perform OAuth step 2 to obtain an access token.
     *
     * @param string $code The authorization code received from OAuth step 1.
     * @return array The response containing the access token.
     */
    private function get_oauth_step_2($code)
    {
        // Retrieve OAuth related configuration from environment variables
        $clientID       = env('ZOOM_CLIENT_ID');
        $clientSecret   = env('ZOOM_CLIENT_SECRET');
        $redirectURL    = env('ZOOM_REDIRECT_URL');
        $tokenURL       = env('ZOOM_TOKEN_URL');

        // Send a POST request to the token URL to exchange the authorization code for an access token
        $response = Http::asForm()->post($tokenURL, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectURL,
            'client_id' => $clientID,
            'client_secret' => $clientSecret,
        ]);

        // Decode the JSON response and return it
        return json_decode($response->body(), true);
    }


    /**
     * Handle the index page request.
     *
     * @param \Illuminate\Http\Request $request The HTTP request.
     * @return mixed The response for the index page request.
     */
    public function index(Request $request)
    {
        // Check if 'code' parameter is present in the request
        if (!$request->code) {
            // If 'code' parameter is not present, initiate OAuth step 1
            $this->get_oauth_step_1();
        } else {
            // If 'code' parameter is present, proceed with OAuth step 2
            $getToken = $this->get_oauth_step_2($request->code);

            // Store the access token in the session
            session(['token' => $getToken['access_token']]);

            // Redirect to the create meeting page after successful OAuth
            return redirect()->route('meeting.create');
        }
    }


    /**
     * Display the page for creating a new meeting.
     *
     * @param \Illuminate\Http\Request $request The HTTP request.
     * @return \Illuminate\View\View The view for creating a new meeting.
     */
    public function createMeetingPage(Request $request)
    {
        // Return the view for creating a new meeting
        return view('meeting.create');
    }


    /**
     * Store a new meeting with the provided details for the authenticated user.
     * This function requires authentication with Zoom API via JWT token.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing meeting details.
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View Redirect back with an error message if authentication fails or return a view with a list of meetings on success.
     */
    public function storeMeeting(Request $request)
    {
        // Retrieve JWT token from session
        $jwtToken = session()->get('token');

        // Check if JWT token exists
        if (!$jwtToken) {
            return redirect()->route('zoom.auth')->with('error', 'Please authenticate first');
        }

        // Send POST request to create a new meeting on Zoom API
        $response =  Http::withToken($jwtToken)
            ->post('https://api.zoom.us/v2/users/me/meetings', [
                'topic'         => $request->topic ?? 'New Meeting General Talk',
                'type'          => 2,
                'start_time'    => $request->start_time ?? date('Y-m-dTh:i:00') . 'Z',
                'duration'      => 30,
                'password'      => $request->password ?? "123456",
                'agenda'        => $request->agenda ?? 'Interview Meeting',
                'timezone'      => 'Asia/Kolkata',
            ]);

        // If the response is successful, fetch the list of meetings
        if ($response) {
            $response = Http::withToken($jwtToken)
                ->get('https://api.zoom.us/v2/users/me/meetings');

            // Extract list of meetings from response JSON
            $meetings = $response->json()['meetings'];
        }

        // Return a view with a list of meetings
        return view('meeting.list', compact('meetings'));
    }

    /**
     * Display a page listing all meetings associated with the authenticated user.
     * This function requires authentication with Zoom API via JWT token.
     *
     * @param \Illuminate\Http\Request $request The HTTP request.
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View Redirect back with an error message if authentication fails or return a view with a list of meetings on success.
     */
    public function listAllMeetingsPage(Request $request)
    {
        // Retrieve JWT token from session
        $jwtToken = session()->get('token');

        // Check if JWT token exists
        if (!$jwtToken) {
            return redirect()->route('zoom.auth')->with('error', 'Please authenticate first');
        }

        // Send GET request to Zoom API to fetch all meetings for the authenticated user
        $response = Http::withToken($jwtToken)
            ->get('https://api.zoom.us/v2/users/me/meetings');

        // Extract list of meetings from response JSON
        $meetings = $response->json()['meetings'];

        // Return a view with a list of meetings
        return view('meeting.list', compact('meetings'));
    }

    /**
     * Retrieve details of a Zoom meeting with the provided meeting ID.
     * This function requires authentication with Zoom API via JWT token.
     *
     * @param \Illuminate\Http\Request $request The HTTP request.
     * @param int $meetingId The ID of the meeting to fetch details for.
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View Redirect back with an error message if authentication fails or return a view with meeting details on success.
     */
    public function getMeetingDetailsPage(Request $request, $meetingId)
    {
        // Retrieve JWT token from session
        $jwtToken = session()->get('token');

        // Check if JWT token exists
        if (!$jwtToken) {
            return redirect()->route('zoom.auth')->with('error', 'Please authenticate first');
        }

        // Send GET request to Zoom API to fetch meeting details
        $response = Http::withToken($jwtToken)
            ->get("https://api.zoom.us/v2/meetings/{$meetingId}");

        // Extract meeting details from response JSON
        $meeting = $response->json();

        // Return a view with meeting details
        return view('meeting.details', compact('meeting'));
    }

    /**
     * Update a Zoom meeting with the provided meeting ID.
     * This function requires authentication with Zoom API via JWT token.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing updated meeting details.
     * @param int $meetingId The ID of the meeting to be updated.
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Client\Response Redirect back with an error message if authentication fails or return JSON response from Zoom API on success.
     */
    public function updateMeeting(Request $request, $meetingId)
    {
        // Retrieve JWT token from session
        $jwtToken = session()->get('token');

        // Check if JWT token exists
        if (!$jwtToken) {
            return redirect()->route('zoom.auth')->with('error', 'Please authenticate first');
        }

        // Send PATCH request to Zoom API to update the meeting
        $response = Http::withToken($jwtToken)
            ->patch("https://api.zoom.us/v2/meetings/{$meetingId}", [
                'topic'         => $request->topic,
                'start_time'    => $request->start_time,
                'agenda'        => $request->agenda,
            ]);

        // Check if the request was successful
        if ($response->successful()) {
            return $response->json();
        } else {
            return back()->with('error', 'Failed to update the meeting');
        }
    }


    // TODO: Not in Use

    // private function createMeeting($jwtToken)
    // {
    //     $meetingDetails = [
    //         'topic'      => 'New Meeting General Talk',
    //         'type'       => 2,
    //         'start_time' => date('Y-m-dTh:i:00') . 'Z',
    //         'duration'   => 30,
    //         'password'   => mt_rand(),
    //         'timezone'   => 'Asia/Kolkata',
    //         'agenda'     => 'Interview Meeting',
    //     ];
    //     return $this->makeZoomAPICall('POST', 'https://api.zoom.us/v2/users/me/meetings', $meetingDetails, $jwtToken);
    // }

    // private function create_a_zoom_meeting($meetingConfig = [])
    // {
    //     $requestBody = [
    //         'topic'      => $meetingConfig['topic'] ?? 'New Meeting General Talk',
    //         'type'       => $meetingConfig['type'] ?? 2,
    //         'start_time' => $meetingConfig['start_time'] ?? date('Y-m-dTh:i:00') . 'Z',
    //         'duration'   => $meetingConfig['duration'] ?? 30,
    //         'password'   => $meetingConfig['password'] ?? mt_rand(),
    //         'timezone'   => 'Asia/Kolkata',
    //         'agenda'     => $meetingConfig['agenda'] ?? 'Interview Meeting',
    //         'settings'   => [
    //             'host_video'        => false,
    //             'participant_video' => true,
    //             'cn_meeting'        => false,
    //             'in_meeting'        => false,
    //             'join_before_host'  => true,
    //             'mute_upon_entry'   => true,
    //             'watermark'         => false,
    //             'use_pmi'           => false,
    //             'approval_type'     => 0,
    //             'registration_type' => 0,
    //             'audio'             => 'voip',
    //             'auto_recording'    => 'none',
    //             'waiting_room'      => false,
    //         ],
    //     ];

    //     $curl = curl_init();
    //     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL            => "https://api.zoom.us/v2/users/me/meetings",
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING       => "",
    //         CURLOPT_MAXREDIRS      => 10,
    //         CURLOPT_SSL_VERIFYHOST => 0,
    //         CURLOPT_SSL_VERIFYPEER => 0,
    //         CURLOPT_TIMEOUT        => 30,
    //         CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST  => "POST",
    //         CURLOPT_POSTFIELDS     => json_encode($requestBody),
    //         CURLOPT_HTTPHEADER     => array(
    //             "Authorization: Bearer " . $meetingConfig['jwtToken'],
    //             "Content-Type: application/json",
    //             "cache-control: no-cache",
    //         ),
    //     ));
    //     $response = curl_exec($curl);
    //     $err      = curl_error($curl);
    //     curl_close($curl);

    //     if ($err) {
    //         return [
    //             'success'  => false,
    //             'msg'      => 'cURL Error #:' . $err,
    //             'response' => null,
    //         ];
    //     } else {
    //         return [
    //             'success'  => true,
    //             'msg'      => 'success',
    //             'response' => json_decode($response, true),
    //         ];
    //     }
    // }

    // private function get_zoom_meeting_details($meetingId, $jwtToken)
    // {
    //     $curl = curl_init();
    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => "https://api.zoom.us/v2/meetings/{$meetingId}",
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => "",
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_SSL_VERIFYHOST => 0,
    //         CURLOPT_SSL_VERIFYPEER => 0,
    //         CURLOPT_TIMEOUT => 30,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => "GET",
    //         CURLOPT_HTTPHEADER => array(
    //             "Authorization: Bearer " . $jwtToken,
    //             "Content-Type: application/json",
    //             "cache-control: no-cache",
    //         ),
    //     ));
    //     $response = curl_exec($curl);
    //     $err = curl_error($curl);
    //     curl_close($curl);

    //     if ($err) {
    //         return [
    //             'success' => false,
    //             'msg' => 'cURL Error #: ' . $err,
    //             'response' => null,
    //         ];
    //     } else {
    //         return [
    //             'success' => true,
    //             'msg' => 'success',
    //             'response' => json_decode($response, true),
    //         ];
    //     }
    // }

    // private function getMeetingDetails($meetingId, $jwtToken)
    // {
    //     return $this->makeZoomAPICall('GET', "https://api.zoom.us/v2/meetings/{$meetingId}", [], $jwtToken);
    // }

    // private function listAllMeetings($jwtToken)
    // {
    //     $jwtToken = session()->get('token');
    //     $response =  $this->makeZoomAPICall('GET', 'https://api.zoom.us/v2/users/me/meetings', [], $jwtToken);
    //     dd($response);
    // }

    // private function makeZoomAPICall($method, $url, $data = [], $jwtToken = null)
    // {
    //     $curl = curl_init();
    //     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL            => $url,
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING       => "",
    //         CURLOPT_MAXREDIRS      => 10,
    //         CURLOPT_SSL_VERIFYHOST => 0,
    //         CURLOPT_SSL_VERIFYPEER => 0,
    //         CURLOPT_TIMEOUT        => 30,
    //         CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST  => $method,
    //         CURLOPT_POSTFIELDS     => ($method === 'POST' ? json_encode($data) : null),
    //         CURLOPT_HTTPHEADER     => array(
    //             "Authorization: Bearer " . $jwtToken,
    //             "Content-Type: application/json",
    //             "cache-control: no-cache",
    //         ),
    //     ));

    //     $response = curl_exec($curl);
    //     $err      = curl_error($curl);
    //     curl_close($curl);

    //     if ($err) {
    //         return [
    //             'success' => false,
    //             'msg' => 'cURL Error #: ' . $err,
    //             'response' => null,
    //         ];
    //     } else {
    //         return [
    //             'success' => true,
    //             'msg' => 'success',
    //             'response' => json_decode($response, true),
    //         ];
    //     }
    // }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZoomController extends Controller
{
    public function __construct()
    {
    }

    public function get_oauth_step_1()
    {
        $redirectURL  = 'http://localhost:8001/zoom/auth/callback';
        $authorizeURL = 'https://zoom.us/oauth/authorize';

        $clientID     = env("ZOOM_CLIENT_ID");

        $authURL = $authorizeURL . '?client_id=' . $clientID . '&redirect_uri=' . $redirectURL . '&response_type=code&scope=meeting:write meeting:read&state=xyz';

        return redirect()->away($authURL);
    }

    private function get_oauth_step_2($code)
    {
        $tokenURL    = 'https://zoom.us/oauth/token';
        $redirectURL = 'http://localhost:8001/zoom/auth/callback';

        $clientID     = env("ZOOM_CLIENT_ID");
        $clientSecret = env("ZOOM_CLIENT_SECRET");

        $response = Http::asForm()->post($tokenURL, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectURL,
            'client_id' => $clientID,
            'client_secret' => $clientSecret,
        ]);

        $response = json_decode($response, true);
        return $response;
    }

    public function index(Request $request)
    {
        if (!$request->code) {
            $this->get_oauth_step_1();
        } else {
            $getToken = $this->get_oauth_step_2($request->code);

            session(['token' => $getToken['access_token']]);

            // Example: Redirect to create a meeting page after successful OAuth
            return redirect()->route('meeting.create');
        }
    }

    public function createMeetingPage(Request $request)
    {
        return view('meeting.create');
    }

    public function storeMeeting(Request $request)
    {
        $jwtToken = session()->get('token');

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
        if ($response) {
            $response = Http::withToken($jwtToken)
                ->get('https://api.zoom.us/v2/users/me/meetings');

            $meetings = $response->json()['meetings'];
        }

        return view('meeting.list', compact('meetings'));
    }

    public function listAllMeetingsPage(Request $request)
    {
        $jwtToken = session()->get('token');

        $response = Http::withToken($jwtToken)
            ->get('https://api.zoom.us/v2/users/me/meetings');

        $meetings = $response->json()['meetings'];
        return view('meeting.list', compact('meetings'));
    }

    public function getMeetingDetailsPage(Request $request, $meetingId)
    {
        $jwtToken = session()->get('token');

        $response = Http::withToken($jwtToken)
            ->get("https://api.zoom.us/v2/meetings/{$meetingId}");

        $meeting = $response->json();

        return view('meeting.details', compact('meeting'));
    }

    public function updateMeeting(Request $request, $meetingId)
    {

        $jwtToken = session()->get('token');

        if (!$jwtToken) {
            return redirect()->route('zoom.auth')->with('error', 'Please authenticate first');
        }

        $response = Http::withToken($jwtToken)
            ->patch("https://api.zoom.us/v2/meetings/{$meetingId}", [
                'topic'         => $request->topic,
                'start_time'    => $request->start_time,
                'agenda'        => $request->agenda,
            ]);

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

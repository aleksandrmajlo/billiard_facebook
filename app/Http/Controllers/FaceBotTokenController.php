<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class FaceBotTokenController extends Controller
{
    //
    public function face_send_token(Request $request)
    {
        $token = $request->token;
        $billiards_id = Cookie::get('billiards_id', 'billiards_1');


        $client = new Client();
        $baseUrl = 'https://graph.facebook.com';
        $endpoint = '/me';
        $params = [
            'access_token' => $token,
        ];

        $response = $client->request('GET', $baseUrl . $endpoint, [
            'query' => $params,
        ]);

        $body = $response->getBody()->getContents();

        $data = json_decode($body, true);
        if ($data) {
            $face_id = $data['id'];

            \DB::table('face_tokens')
                ->updateOrInsert(
                    ['face_id' => $data['id']],
                    [
                        'token' => $token,
                        'billiard_id' =>$billiards_id,
                        'name' => $data['name'],
                        'created_at'=>Carbon::now(),
                        'updated_at'=>Carbon::now()
                    ]
                );

        }

        return response()->json(['suc' => 1]);
    }
}

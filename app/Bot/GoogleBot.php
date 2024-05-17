<?php


namespace App\Bot;


use App\Bot\BookingFind;
use App\Bot\MessangeSend;
use App\Models\FaceCustomer;
use App\Models\FaceMessage;
use App\Services\Bot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7;
use Illuminate\Support\Facades\Http;

class GoogleBot
{
    public static function send_mess($jsonData,$specialist='')
    {
        $client = new \Google\Client();
        $client->setApplicationName("My Business Communications App");
        $client->setAuthConfig(storage_path('app/gbc-5af5r9v-33fed125c3b6.json'));
        $client->setAccessType('offline');
        $client->addScope('https://www.googleapis.com/auth/businessmessages');
        $tokenOauth = $client->fetchAccessTokenWithAssertion();
        $message = [
            'messageId' => Str::uuid(),
            "representative" => [
                "representativeType" => 'HUMAN',
                "displayName"=> $specialist,
            ],
            'name' => $jsonData['recipient']['id'],
            'text' => $jsonData['message'],
        ];

        $endpoint = 'https://businessmessages.googleapis.com/v1/conversations';
        $headers = [
            'Authorization' => 'Bearer ' . $tokenOauth['access_token'],
            'Content-Type' => 'application/json'
        ];

        $conversation_id = mb_strtolower($jsonData['conversation_id']);
        $message_endpoint = $endpoint . '/' . $conversation_id . '/messages';
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $message_endpoint, [
                'headers' => $headers,
                'json' => $message
            ]);
//            Log::channel('google')->info('suc - ' . Psr7\Message::toString($response));
            return $response;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
//            Log::channel('google')->info('error - ' . Psr7\Message::toString($response));
            return $response;
        }
    }
}

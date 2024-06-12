<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class FacebookLoginController extends Controller
{
    public function redirectToFacebook()
    {

        return Socialite::driver('facebook')
            ->scopes([
                'pages_show_list',
                'pages_read_engagement',
                'pages_messaging',
                'instagram_basic',
                'instagram_manage_messages'
            ])
            ->redirect();

    }

    public function handleFacebookCallback()
    {
        try {
            $club_user_id = Auth::user()->id;
            $billiards_id = Cookie::get('billiards_id', 'billiards_1');
            $user = Socialite::driver('facebook')->user();
            $accessToken = $user->token;

            $face_id = $user->getId();

            \DB::table('face_tokens')->updateOrInsert(
                [
                    'user_id' => $club_user_id,
                    'face_id' => $face_id,
                ],
                [
                    'token' => $accessToken,
                    'name' => $user->getName(),
                    'billiard_id' => $billiards_id,
                    'created_at' => now(),
                ]
            );
            $response = Http::get('https://graph.facebook.com/v12.0/me/accounts', [
                'access_token' => $accessToken,
            ]);
            $pages = $response->json()['data'];

            foreach ($pages as $page) {

                \DB::table('face_pages')
                    ->where('page_id', $page['id'])->delete();
                \DB::table('face_pages')->insert([
                    'token' => $page['access_token'],
                    'user_id' => $club_user_id,
                    'face_id' => $face_id,
                    'name' => $page['name'],
                    'page_id' => $page['id'],
                    'billiard_id' => $billiards_id,
                    'created_at' => now(),
                ]);

//                dump(isset($page['instagram_business_account']));
//                dump($page);


                if (isset($page['instagram_business_account'])) {
                    $instagramId = $page['instagram_business_account']['id'];
                    // Используем правильный endpoint для получения токена Instagram
                    $instagramTokenResponse = Http::get("https://graph.facebook.com/v12.0/{$instagramId}", [
                        'fields' => 'access_token',
                        'access_token' => $accessToken,
                    ]);
//                    dump($instagramTokenResponse);

                    if ($instagramTokenResponse->successful()) {

                        $instagramToken = $instagramTokenResponse->json()['access_token'];
//                        dump($instagramTokenResponse->json()['access_token']);

                        \DB::table('instagram_pages')->updateOrInsert(
                            [
                                'account_id' => $instagramId,
                            ],
                            [
                                'user_id' => $club_user_id,
                                'access_token' => $instagramToken,
                                'name' => $user->name,
                                'billiard_id' => $billiards_id,
                                'created_at' => now(),
                            ]
                        );
                    }

                }

            }

            /*
             $igResponse = Http::get('https://graph.facebook.com/v12.0/me/accounts', [
                 'access_token' => $accessToken,
                 'fields' => 'instagram_business_account'
             ]);
             if ($igResponse->successful()) {
                 $accounts = $igResponse->json()['data'];
                 foreach ($accounts as $account) {
                     if (isset($account['instagram_business_account'])) {
                         $instagramAccount = $account;
                         \DB::table('instagram_pages')->updateOrInsert(
                             [
                                 'account_id' => $instagramAccount['id'],
                             ],
                             [
                                 'user_id' => $club_user_id,
                                 'access_token' => $instagramAccount['access_token'],
                                 'name' => $instagramAccount['name'],
                                 'billiard_id' => $billiards_id,
                                 'created_at' => now(),
                             ]
                         );

                     }
                 }
             }
             */

            return redirect()->route('settings');

        } catch (Exception $e) {
            return redirect()->route('settings');
        }
    }


}

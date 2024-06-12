<?php


namespace App\Bot;


class FaceBot
{

    public static function send_mess($jsonData)
    {
        $tokens=\DB::table('face_pages')->where('page_id',$jsonData['page_id'])->first();
        if($tokens){
            $token=$tokens->token;
            $url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' .$token;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            $result = curl_exec($ch);
            curl_close($ch);
        }

    }

}

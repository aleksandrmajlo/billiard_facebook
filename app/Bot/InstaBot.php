<?php


namespace App\Bot;


class InstaBot
{
   public static function send_mess($response){

       /*
        * after setting up pages, take a token from this page
        *  instagram_pages
        * $access_token=\DB::table('instagram_pages')->where('page_id',$jsonData['page_id'])->first();
        *   $access_token = "YOUR_PAGE_ACCESS_TOKEN";
        */
       $access_token = "EAAKL83ZB8zJUBO46hBQ2ZAgDobEUlMnjNPEbOZAxtPTMQIG5ZBZAg5zDWjAJdUrHZBBj8aY0OYXMTQBwhAsulRTxwz9DNGryDefdZAbB0I7S0o5qMHDjYlZAyIzANxAROaJAiUtYLQXFdsx87WjgDzhxpebKiUtPtXLUlk7YuhVWC5QDGG8iF3zqUtpYtB9E9hUUfMGCgierlCrLMS0r";
       $ch = curl_init('https://graph.facebook.com/v11.0/me/messages?access_token=' . $access_token);
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
       curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
       if (!empty($response)) {
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           $result = curl_exec($ch);
           curl_close($ch);
//           dump($result);
           file_put_contents('php://stderr', print_r($result, true));
       }
   }
}

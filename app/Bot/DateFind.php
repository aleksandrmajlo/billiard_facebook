<?php


namespace App\Bot;
use Carbon\Carbon;



class DateFind
{
   public static function validatingTime($message){
       $message_string = preg_replace('/\s+/', '', $message);
       $date=false;
       preg_match('/(0[1-9]|1[0-9]|2[0-9]|3[01])[\/-](0[1-9]|1[0-2])[\/-](19[5-9][0-9]|20[0-9][0-9])/', $message, $matches);
       if($matches){
           $date = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
       }else{
           $booking_json = \Storage::disk('bot')->get('date.json');
           $booking_json = @json_decode($booking_json, 1);
           foreach ($booking_json as $key => $value) {
               if (str_contains($message_string, $key)) {
                   if ($value == 'next') {
                       $currentDateTime = Carbon::now();
                       $newDateTime = Carbon::now()->addDay();
                       $date = $newDateTime->format('Y-m-d');
                   } elseif ('th') {
                       $newDateTime = Carbon::now();
                       $date = $newDateTime->format('Y-m-d');
                   } else {
                       $currentDateTime = Carbon::now((int)$value);
                       $newDateTime = Carbon::now()->addDays();
                       $date = $newDateTime->format('Y-m-d');
                   }
                   break;
               }
           }
       }

       return $date;

   }
}

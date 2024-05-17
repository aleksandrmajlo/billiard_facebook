<?php


namespace App\Bot;


class TimeFind
{
     public static function validatingTime($message){
         $time=false;
         preg_match('/([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?/', $message, $time_matches);
         if($time_matches){
             $time=$time_matches[0];
         }
         return $time;
     }
}

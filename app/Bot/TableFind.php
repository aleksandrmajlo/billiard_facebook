<?php


namespace App\Bot;


class TableFind
{
   public static function validatingTable($message){
       $table_name=false;
       $table_json = \Storage::disk('bot')->get('table.json');
       $table_json = @json_decode($table_json, 1);
       $message_string = preg_replace('/\s+/', '', $message);
       foreach ($table_json as $key => $value) {
           if (str_contains($message_string, $key)) {
               $table_name = $value;
               break;
           }
       }
       return $table_name;
   }
}

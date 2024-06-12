<?php


namespace App\Bot;


use App\Models\Setting;
use App\Models\FaceMessage;
use Carbon\Carbon;

class MessangeSend
{

    public static function send($key,$facecustomer,$lang,$response,$type='google'){
        $facemessage =  FaceMessage::where('face_customer_id',$facecustomer->id)
            ->where('type',$key)->orderBy('id','desc')->first();
        if($facemessage){
            $currentTime =Carbon::now();
            $anchorTime=Carbon::createFromFormat("Y-m-d H:i:s", $facemessage->created_at);
            $minuteDiff = $anchorTime->diffInMinutes($currentTime);
            if($minuteDiff<30)return false;
        }
        $text=$lang[$key];
        if($key=='all'){
            $phone = Setting::where('type', 'phone')->first();
            if($phone){
                $phone_number=$phone->setting;
                $text=str_replace('phone_number',$phone_number,$text);
            }
        }

        if($type=='google'){
            $response['message']=$text;
            GoogleBot::send_mess($response);
        }elseif ($type=='face'){
            $response["message"]["text"]=$text;
            FaceBot::send_mess($response);;
        }elseif ($type=='insta'){
            $response["message"]["text"]=$text;
            InstaBot::send_mess($response);;
        }

        $facemessage = new FaceMessage;
        $facemessage->face_customer_id = $facecustomer->id;
        $facemessage->message = $text;
        $facemessage->user_id = -1;
        $facemessage->type = $key;
        $facemessage->save();

    }

   // для next acion
    public static function sendNext($key,$facecustomer,$response,$type='google'){

        if($type=='google'){
            $text=$response['message'];
            GoogleBot::send_mess($response);
        }elseif ($type=='face'){
            $text=$response["message"]["text"];
            FaceBot::send_mess($response);;
        }elseif ($type=='insta'){
            $text=$response["message"]["text"];
            InstaBot::send_mess($response);;
        }

        $facemessage = new FaceMessage;
        $facemessage->face_customer_id = $facecustomer->id;
        $facemessage->message = $text;
        $facemessage->user_id = -1;
        $facemessage->type = $key;
        $facemessage->save();

    }


}

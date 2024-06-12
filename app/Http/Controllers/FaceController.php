<?php

namespace App\Http\Controllers;

use App\Bot\BookingFind;
use App\Bot\MessangeSend;
use App\Bot\NextAction;
use App\Models\Booking;
use App\Models\FaceCustomer;
use App\Models\FaceMessage;
use App\Models\Table;
use App\Services\Bot;
use Illuminate\Http\Request;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FaceController extends Controller
{



    public function webhook_face()
    {
        $log_file = public_path() . "/log_my/face.txt";
        $data = file_get_contents('php://input');
        file_put_contents($log_file, $data . "\n", FILE_APPEND);

        //hook validation
        if (isset($_REQUEST['hub_challenge'])) {
            $challenge = $_REQUEST['hub_challenge'];
            $verify_token = $_REQUEST['hub_verify_token'];
            if ($verify_token === config('face.face_secret')) {
                echo $challenge;
                exit;
            }
        }

        $input = @json_decode($data, true);
        if($input&&isset($input['object'])&&$input['object']=='page'){
            $page_id=$input['entry'][0]['id'];

            $isPage=false;
            $bases = config('database.connections');
            foreach ($bases as $k => $basis) {
                if ($k == 'bill_pay') continue;
                $face_page = DB::connection($k)->table('face_pages')->where('page_id', $page_id)->first();
                if($face_page){
                    config(['database.connections.mysql.database' => $basis['database']]);
                    DB::purge('mysql');
                    DB::reconnect('mysql');
                    $isPage=true;
                    break;
                }
            }
            if($isPage){

                $lang_json = \Storage::disk('bot')->get('lang.json');
                $lang = @json_decode($lang_json, 1);

                $senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
                $facecustomer = FaceCustomer::where('face_id', $senderId)->where('type', 'face')->first();
                if (is_null($facecustomer)) {
                    // create a user if there is none
                    $facecustomer = new FaceCustomer;
                    $facecustomer->face_id = $senderId;
                    $facecustomer->face_page_id=$page_id;
                    $facecustomer->save();
                }
                else{
                    $facecustomer->face_id = $senderId;
                    $facecustomer->face_page_id=$page_id;
                    $facecustomer->save();
                }

                $message = $input['entry'][0]['messaging'][0]['message']['text'];

                $facemessage = new FaceMessage;
                $facemessage->face_customer_id = $facecustomer->id;
                $facemessage->message = $message;
                $facemessage->save();

                $start_result = false;
                $next_action = $facecustomer->next_action;
                $response =
                    [
                        'recipient' => ['id' => $senderId],
                        'message' => ['text' => ''],
                        'page_id'=>$page_id
                    ];
                $arr_next_action = [
                    "table",
                    "phone",
                    "date",
                    "time"
                ];
                $booking_result = Bot::validatingBookingTimeTablePhone($message, $facecustomer, $lang);

                if (is_null($next_action)) {

                    $true = [];
                    $false = [];
                    /*
                     * если есть 0 и 1
                     * то нужно отсылать сообщение poles_send
                     */
                    foreach ($arr_next_action as $item) {
                        if ($booking_result[$item]) {
                            $true[] = $item;
                        } else {
                            $false[] = $item;
                        }
                    }
                    if ($true && $false) {
                        $response["message"]["text"]=NextAction::set($false, $true, $booking_result, $facecustomer, $lang);
                        MessangeSend::sendNext('poles_send', $facecustomer, $response,'face');
                        return \Response::make('OK', 200);
                    }
                }
                else {

                    $valid_next = true;
                    $arr_next = explode(',', $next_action);
                    foreach ($arr_next as $item) {
                        if (!$booking_result[$item]) {
                            $valid_next = false;
                        }
                    }
                    // all fields are completed - make a reservation
                    if ($valid_next) {
                        $action_datas = $facecustomer->action_datas;
                        $booking_result['suc']=true;
                        foreach ($action_datas as $key => $action_data) {
                            $booking_result[$key] = $action_data;
                        }
                        $booking_id = BookingFind::addBooking($booking_result, $facecustomer);
                        MessangeSend::send('thank_you', $facecustomer, $lang, $response,'face');
                        return \Response::make('OK', 200);
                    }else{
                        // чего то не хватает обнуляем
                        $facecustomer->action_datas=null;
                        $facecustomer->next_action=null;
                        $facecustomer->save();
                        MessangeSend::send('all', $facecustomer, $lang, $response,'face');
                        return \Response::make('OK', 200);
                    }
                }

                if (!$booking_result['table'] && !$booking_result['phone'] && !$booking_result['date'] && !$booking_result['time'] && !$booking_result['isBooking']) {
                    // send phone technical support
                    MessangeSend::send('all', $facecustomer, $lang, $response,'face');
                    return \Response::make('OK', 200);
                }

                if (!$booking_result['table'] && !$booking_result['phone'] && !$booking_result['date'] && !$booking_result['time'] && $booking_result['isBooking']) {
                    //есть упоминание с start_booking.json остального нту
                    MessangeSend::send('booking_send', $facecustomer, $lang, $response,'face');
                    return \Response::make('OK', 200);
                }

                if ($booking_result['table'] && $booking_result['phone'] && $booking_result['date'] && $booking_result['time']) {
                    //create booking
                    $booking_id = BookingFind::addBooking($booking_result, $facecustomer);
                    MessangeSend::send('thank_you', $facecustomer, $lang, $response,'face');
                    return \Response::make('OK', 200);
                }

                if ($booking_result['table'] || $booking_result['phone'] || $booking_result['date'] || $booking_result['time'] || $booking_result['isBooking']) {
                    //есть упоминание с start_booking.json остального нту
                    MessangeSend::send('booking_send', $facecustomer, $lang, $response,'face');
                    return \Response::make('OK', 200);
                }
                MessangeSend::send('all', $facecustomer, $lang, $response,'face');
                return \Response::make('OK', 200);

            }

            return \Response::make('OK', 200);
        }
        return \Response::make('OK', 200);

    }

    public function webhook_inst(){

        $inst_token=config('face.face_secret');

        $log_file = public_path() . "/log_my/ins.txt";
        $data = file_get_contents('php://input');
        file_put_contents($log_file, $data . "\n", FILE_APPEND);

        //hook validation
        if (isset($_REQUEST['hub_challenge'])) {
            $challenge = $_REQUEST['hub_challenge'];
            $verify_token = $_REQUEST['hub_verify_token'];
            if ($verify_token === $inst_token) {
                echo $challenge;
                exit;
            }
        }

        $input = @json_decode($data, true);

        if($input&&isset($input['object'])&&$input['object']=='instagram'){
            if (isset($input['entry'])) {
                $isPage="17841409933645214";
                $sender_id=false;
                $message_text='';
                foreach ($input['entry'] as $entry) {
                    $page_id=$entry['id'];
                    foreach ($entry['messaging'] as $messaging_event) {
                        $sender_id = $messaging_event['sender']['id'];
                        $message_text = $messaging_event['message']['text'];
                    }

                }

                if($sender_id){
                    $lang_json = \Storage::disk('bot')->get('lang.json');
                    $lang = @json_decode($lang_json, 1);

                    $facecustomer = FaceCustomer::where('face_id', $sender_id)->where('type', 'insta')->first();
                    if (is_null($facecustomer)) {
                        $facecustomer = new FaceCustomer;
                        $facecustomer->face_id = $sender_id;
                        $facecustomer->face_page_id=$page_id;
                        $facecustomer->type='insta';
                        $facecustomer->save();
                    }else{
                        $facecustomer->face_id = $sender_id;
                        $facecustomer->face_page_id=$page_id;
                        $facecustomer->save();
                    }

                    $facemessage = new FaceMessage;
                    $facemessage->face_customer_id = $facecustomer->id;
                    $facemessage->message = $message_text;
                    $facemessage->save();

                    $start_result = false;
                    $next_action = $facecustomer->next_action;
                    $response =
                        [
                            'recipient' => ['id' => $sender_id],
                            'message' => ['text' => ''],
                            'page_id'=>$page_id
                        ];
                    $arr_next_action = [
                        "table",
                        "phone",
                        "date",
                        "time"
                    ];

                    $booking_result = Bot::validatingBookingTimeTablePhone($message_text, $facecustomer, $lang);

                    if (is_null($next_action)) {

                        $true = [];
                        $false = [];
                        /*
                         * если есть 0 и 1
                         * то нужно отсылать сообщение poles_send
                         */
                        foreach ($arr_next_action as $item) {
                            if ($booking_result[$item]) {
                                $true[] = $item;
                            } else {
                                $false[] = $item;
                            }
                        }
                        if ($true && $false) {
                            $response["message"]["text"]=NextAction::set($false, $true, $booking_result, $facecustomer, $lang);
                            MessangeSend::sendNext('poles_send', $facecustomer, $response,'insta');
                            return \Response::make('OK', 200);
                        }
                    }
                    else {
                        $valid_next = true;
                        $arr_next = explode(',', $next_action);
                        foreach ($arr_next as $item) {
                            if (!$booking_result[$item]) {
                                $valid_next = false;
                            }
                        }
                        // all fields are completed - make a reservation
                        if ($valid_next) {
                            $action_datas = $facecustomer->action_datas;
                            $booking_result['suc']=true;
                            foreach ($action_datas as $key => $action_data) {
                                $booking_result[$key] = $action_data;
                            }
                            $booking_id = BookingFind::addBooking($booking_result, $facecustomer);
                            MessangeSend::send('thank_you', $facecustomer, $lang, $response,'insta');
                            return \Response::make('OK', 200);
                        }else{
                            // чего то не хватает обнуляем
                            $facecustomer->action_datas=null;
                            $facecustomer->next_action=null;
                            $facecustomer->save();
                            MessangeSend::send('all', $facecustomer, $lang, $response,'insta');
                            return \Response::make('OK', 200);
                        }
                    }

                    if (!$booking_result['table'] && !$booking_result['phone'] && !$booking_result['date'] && !$booking_result['time'] && !$booking_result['isBooking']) {
                        // send phone technical support
                        MessangeSend::send('all', $facecustomer, $lang, $response,'insta');
                        return \Response::make('OK', 200);
                    }

                    if (!$booking_result['table'] && !$booking_result['phone'] && !$booking_result['date'] && !$booking_result['time'] && $booking_result['isBooking']) {
                        //есть упоминание с start_booking.json остального нту
                        MessangeSend::send('booking_send', $facecustomer, $lang, $response,'insta');
                        return \Response::make('OK', 200);
                    }

                    if ($booking_result['table'] && $booking_result['phone'] && $booking_result['date'] && $booking_result['time']) {
                        //create booking
                        $booking_id = BookingFind::addBooking($booking_result, $facecustomer);
                        MessangeSend::send('thank_you', $facecustomer, $lang, $response,'insta');
                        return \Response::make('OK', 200);
                    }

                    if ($booking_result['table'] || $booking_result['phone'] || $booking_result['date'] || $booking_result['time'] || $booking_result['isBooking']) {
                        //есть упоминание с start_booking.json остального нту
                        MessangeSend::send('booking_send', $facecustomer, $lang, $response,'insta');
                        return \Response::make('OK', 200);
                    }
                    MessangeSend::send('all', $facecustomer, $lang, $response,'insta');

                }

            }

        }


        return \Response::make('OK', 200);


    }



}

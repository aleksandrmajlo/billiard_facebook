<?php

namespace App\Http\Controllers;

use App\Models\FaceCustomer;
use App\Models\FaceMessage;
use App\Services\Bot;
use Illuminate\Http\Request;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class FaceController extends Controller
{


    // вебхук
    public function webhook_face()
    {
        $log_file = public_path() . "/log_my/face.txt";
        $data = file_get_contents('php://input');
        file_put_contents($log_file, $data . "\n", FILE_APPEND);

        // валидация хука
        if (isset($_REQUEST['hub_challenge'])) {
            $challenge = $_REQUEST['hub_challenge'];
            $verify_token = $_REQUEST['hub_verify_token'];
            if ($verify_token === config('face.face_secret')) {
                echo $challenge;
                exit;
            }
        }

        $lang = \Storage::disk('bot')->get('lang.json');
        $lang = @json_decode($lang, 1);

        $input = @json_decode($data, true);
        $senderId = $input['entry'][0]['messaging'][0]['sender']['id'];

        $facecustomer = FaceCustomer::where('face_id', $senderId)->where('type','face')->first();

        if (is_null($facecustomer)) {
            // создаем пользователя если нету
            $facecustomer = new FaceCustomer;
            $facecustomer->face_id = $senderId;
            $facecustomer->save();
        }

        if (isset($input['entry'][0]['messaging'][0]['message']['text'])) {

            /*
             * пришло текстовое сообщение + созаем новое
             * в любом случае
             */
            $message = $input['entry'][0]['messaging'][0]['message']['text'];

            $facemessage = new FaceMessage;
            $facemessage->face_customer_id = $facecustomer->id;
            $facemessage->message = $message;
            $facemessage->save();

            $start_result = false;
            $next_action = $facecustomer->next_action;
            /*
             *  проверяем сколько времени ушло от времени последней записи в  if ($facecustomer->action_datas)
             */
            if ($facecustomer->action_datas) {
                $action_datas = @json_decode($facecustomer->action_datas);
                $th_date = Carbon::now();
                $created = new Carbon($action_datas->created);
                /*
                 *  сначала проверим дату
                 * если прошло больше  1 часов - инфа не действительна
                 */
                $diff_in_hours = $th_date->diffInHours($created);
                if ($diff_in_hours > 1) {
                    $facecustomer->action_datas = null;
                    $facecustomer->next_action = null;
                    $facecustomer->save();
                    // перезагрузить пользователя
                    $facecustomer = FaceCustomer::where('face_id', $senderId)->first();
                }
            }
            //проверяем фразу на время стол
            $booking_result = Bot::validatingBooking($message, $facecustomer, $lang);

            /*
             *  если существует пользователь для пользователя фейсбука
             * то проверяем может  это сразу заказ
             * телефон не надо запрашивать
             */
            if ($facecustomer->customer) {

                if ($booking_result['suc']) {
                    // все норма - создаем бронь
                    $booking = Bot::addBooking($booking_result, $facecustomer);
                    $facecustomer->next_action = null;
                    $facecustomer->action_datas = null;
                    $facecustomer->save();
                    $response =
                        [
                            'recipient' => ['id' => $senderId],
                            'message' => ['text' => $lang["thank_you"]]
                        ];
                    Bot::bot_messange_chat($facecustomer->id,$lang["thank_you"]);
                    self::send_mess($response);
                    exit();
                }
                else {
                    // прегрузить пользователя
                    $facecustomer = FaceCustomer::where('face_id', $senderId)->first();
                    // тут допилить для пользователя
                    $answer = Bot::Action_answer($facecustomer, $lang);
                    if (!$answer['suc']) {

                    } elseif ($answer['suc'] == 'not') {
                        //  че то сохранили в базе
                        $response =
                            [
                                'recipient' => ['id' => $senderId],
                                'message' => ['text' => $answer['mes']]
                            ];
                        Bot::bot_messange_chat($facecustomer->id,$answer['mes']);
                        self::send_mess($response);
                        exit();
                    } elseif ($answer['suc'] == 'yes') {
                        // все норма - создаем бронь
                        // перегрузить пользователя
                        $booking = self::addBooking($booking_result, $facecustomer);
                        $facecustomer->next_action = null;
                        $facecustomer->action_datas = null;
                        $facecustomer->save();
                        $response =
                            [
                                'recipient' => ['id' => $senderId],
                                'message' => ['text' => $lang["thank_you"]]
                            ];
                        Bot::bot_messange_chat($facecustomer->id,$lang["thank_you"]);
                        self::send_mess($response);
                        exit();
                    }
                    // проверяем или есть фраза забронировать стол
                    $start_result = Bot::start_booking($message);
                    if ($start_result) {
                        /*
                        * поступил запрос на бронирование
                        * отправляем собщение что б поделился номером телефоеа
                        */
                        // пишем что следующее действие - отсылка телефона
                        $facecustomer->next_action = 'booking';
                        $facecustomer->save();
                        $response =
                            [
                                'recipient' => ['id' => $senderId],
                                'message' => ['text' => $lang["booking_send"]]
                            ];
                        Bot::bot_messange_chat($facecustomer->id,$lang["booking_send"]);
                        self::send_mess($response);
                        exit();
                    } else {

                    }
                }
            }

            else {
                // то есть нету пользователя !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                $phone_result = Bot::validatingPhone($message, $facecustomer);
                $answer = Bot::Action_answer($facecustomer, $lang);
                if ($phone_result) {
                    // перегрузить пользователя
                    $facecustomer = FaceCustomer::where('face_id', $senderId)->first();
                    //
                    // если нет сохраненых данных action
                    ///
                    if (!$answer['suc']) {
                        $facecustomer->next_action = 'booking';
                        $facecustomer->save();
                        $response =
                            [
                                'recipient' => ['id' => $senderId],
                                'message' => ['text' => $lang['booking_send']]
                            ];
                        Bot::bot_messange_chat($facecustomer->id,$lang['booking_send']);
                        self::send_mess($response);
                        exit();
                    }
                    elseif ($answer['suc'] == "not") {
                        //  че то сохранили в базе
                        $response =
                            [
                                'recipient' => ['id' => $senderId],
                                'message' => ['text' => $answer['mes']]
                            ];
                        Bot::bot_messange_chat($facecustomer->id,$answer['mes']);
                        self::send_mess($response);
                        exit();
                    }
                    elseif ($answer['suc'] == "yes") {
                        // все норма - создаем бронь
                        // перегрузить пользователя
                        $booking = Bot::addBooking($booking_result, $facecustomer);
                        $facecustomer->next_action = null;
                        $facecustomer->action_datas = null;
                        $facecustomer->save();
                        $response =
                            [
                                'recipient' => ['id' => $senderId],
                                'message' => ['text' => $lang["thank_you"]]
                            ];
                        Bot::bot_messange_chat($facecustomer->id,$lang["thank_you"]);
                        self::send_mess($response);
                        exit();
                    }
                }
                else {
                    // проверяем или есть фраза забронировать стол
                    $start_result = Bot::start_booking($message);
                }
                /*
                 *  если ничего не найдено
                 * телефона или старта бронирования
                 * но перед  этим писал что надо
                 */
                if (!$phone_result && !$start_result && ($next_action && $next_action == 'phone')) {
                    $start_result = Bot::start_booking($message);
                    $response =
                        [
                            'recipient' => ['id' => $senderId],
                            'message' => ['text' => $lang["phone_send"]]
                        ];
                    Bot::bot_messange_chat($facecustomer->id,$lang["phone_send"]);
                    self::send_mess($response);
                    exit();
                }
            }


            // проверяем сначала ответ на  бронирование
            // если норма - то создаем заказ
            if ($booking_result['suc']) {
                $booking = Bot::addBooking($booking_result, $facecustomer);
                $facecustomer->next_action = null;
                $facecustomer->action_datas = null;
                $facecustomer->save();
                $response =
                    [
                        'recipient' => ['id' => $senderId],
                        'message' => ['text' => $lang["thank_you"]]
                    ];
                Bot::bot_messange_chat($facecustomer->id,$lang["thank_you"]);
                self::send_mess($response);
                exit();
            }
            if ($start_result) {
                /*
                 * поступил запрос на бронирование
                 * отправляем собщение что б поделился номером телефоеа
                 */
                // пишем что следующее действие - отсылка телефона
                $facecustomer->next_action = 'phone';
                $facecustomer->save();
                $response =
                    [
                        'recipient' => ['id' => $senderId],
                        'message' => ['text' => $lang["phone_send"]]
                    ];
                Bot::bot_messange_chat($facecustomer->id,$lang["phone_send"]);
                self::send_mess($response);
                exit();
            }
        }



    }

    // Отправка сообщения
    public static function send_mess($jsonData)
    {
        $url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' . config('face.face_page');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);

        curl_close($ch);
    }



}

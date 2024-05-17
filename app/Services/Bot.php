<?php


namespace App\Services;

use App\Bot\BookingFind;
use App\Bot\DateFind;
use App\Bot\PhoneValid;
use App\Bot\TableFind;
use App\Bot\TimeFind;
use App\Models\FaceMessage;
use Illuminate\Support\Carbon;

class Bot
{
    /*
     * проверка  или есть ключевые слова  для начала  бронирования
     */
    static public function start_booking($message)
    {
        $start_booking = \Storage::disk('bot')->get('start_booking.json');
        $input = @json_decode($start_booking, 1);
        $values = array_map(function ($v) {
            $v = mb_strtolower($v);
            $v = preg_replace('/\s+/', '', $v);
            return $v;
        }, $input);
        $message = mb_strtolower($message);
        $message = preg_replace('/\s+/', '', $message);
        $result = false;
        foreach ($values as $value) {
            if (str_contains($message, $value)) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /*
     *  проверка телефона
     */
    static public function validatingPhone($message, $facecustomer = null)
    {
        $valid_number = false;
        $message_arr = explode(" ", $message);
        foreach ($message_arr as $value) {
            $phone = preg_replace('/[^0-9]/', '', $value);
            $len = strlen($phone);
            if ($len == 10) {
                $valid_number = true;
                $phone = '+38' . $phone;
                break;
            } elseif ($len == 12) {
                $valid_number = true;
                $phone = '+' . $phone;
                break;
            }
        }
        if ($valid_number && $facecustomer) {
            $customer = Customer::where('phone', $phone)->first();
            if (is_null($customer)) {
                $customer = new Customer;
                $customer->phone = $phone;
                if ($facecustomer->type == 'google') {
                    $customer->name = $facecustomer->face_id;
                }
                $customer->save();
            }
            $facecustomer->customer_id = $customer->id;
            $facecustomer->save();
        }
        return $valid_number;
    }

    /*
     * проверка фразы на бронирование
     *
     * $lang =lang.json
     */
    public static function validatingBooking($message, $facecustomer, $lang)
    {

        $th_date = Carbon::now();
        $this_action_datas = [
            'created' => $th_date->format('Y-m-d H:i:s')
        ]; //это новая инфа которая будет записана в бд
        $action_datas = false;// то что для пользователя сохранили в базе
        if ($facecustomer->action_datas) {
            $action_datas = @json_decode($facecustomer->action_datas, false);
        }


        $table_name = false;// название  стола
        $date = false;// дата бронирования
        $time = false;// время бронирования
        $message = mb_strtolower($message);
        $message_string = preg_replace('/\s+/', '', $message);
        $booking_result = [
            'suc' => false,
            'mes' => '',
            'table' => false,
            'booking_from' => false
        ];
        $booking_json = \Storage::disk('bot')->get('booking.json');
        $booking_json = @json_decode($booking_json, 1);

        // сначала ищем упоминание стола
        foreach ($booking_json['table'] as $key => $value) {
            if (str_contains($message_string, $key)) {
                $table_name = $value;
                break;
            }
        }
        // если стол не найден выходим
        if (!$table_name) {
            // проверим сначала или нет ранее записи в таблицу $action_datas->table
            if ($action_datas && isset($action_datas->table)) {
                $table_name = $action_datas->table;
            } else {
                $booking_result['mes'] = $lang["booking_send"];
                return $booking_result;
            }
        }
        $booking_result['table'] = $table_name;

        //валидация даты
        preg_match('/(0[1-9]|1[0-9]|2[0-9]|3[01])[\/-](0[1-9]|1[0-2])[\/-](19[5-9][0-9]|20[0-9][0-9])/', $message, $matches);
        /*
         *если пустая дата но стол указан
         *  то проверяем фразы
         */

        if (empty($matches)) {
            foreach ($booking_json['date_string'] as $key => $value) {
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
        } else {
            // дата есть
            $date = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        /*
         * если даты нету
         * нужно проверить или нет сохраненной
         */
        if (!$date) {
            if ($action_datas && isset($action_datas->date)) {
                $bd_date = new Carbon($action_datas->date);
                $diff_gt = $bd_date->gt($th_date);
                // если дата актуальна
                if ($diff_gt) {
                    $date = $action_datas->date;
                }
            }
            // даты нету точно!!!!!
            if (!$date) {
                $this_action_datas['table'] = $table_name;
                $facecustomer->action_datas = json_encode($this_action_datas);
                $facecustomer->save();
                $booking_result['mes'] = $lang["yes_table_not_date"];
                return $booking_result;
            }
        }

        // валидация времени
        preg_match('/([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?/', $message, $time_matches);
        $this_action_datas['table'] = $table_name;
        $this_action_datas['date'] = $date;
        if ($time_matches) {
            $time = $time_matches[0];
            $booking_result['booking_from'] = $date . ' ' . $time;
            $date_start = new Carbon($booking_result['booking_from']);
            // добавть один час
            $booking_before = $date_start->addHours();
            $booking_result['booking_before'] = $booking_before;
            $booking_result['suc'] = true;
            $this_action_datas['time'] = $time;
        } else {
            // нету времени
            // проверяемсохраненую инфу
            if ($action_datas && isset($action_datas->time)) {
                $time = $action_datas->time;
                $booking_result['booking_from'] = $date . ' ' . $time;
                $date_start = new Carbon($booking_result['booking_from']);
                // добавть один час
                $booking_before = $date_start->addHours();
                $booking_result['booking_before'] = $booking_before;
                $booking_result['suc'] = true;
                $this_action_datas['time'] = $time;
            } else {
                $booking_result['mes'] = $lang["yes_table_not_date"];
            }
        }
        $facecustomer->action_datas = json_encode($this_action_datas);
        $facecustomer->save();
        return $booking_result;
    }

    /*
     *  выбрать ответ нужный для пользователя
     */
    public static function Action_answer($facecustomer, $lang)
    {
        $res = [
            'suc' => false,
            'mes' => '',
        ];
        $datas = [
            'table' => 'стіл (пул,снукер,піраміда)',
            'date' => 'дату бронювання (дата-місяць-рік )',
            'time' => 'час бронювання (години:хвилини)'
        ];

        if ($facecustomer->action_datas) {

            $action_datas = @json_decode($facecustomer->action_datas);
            $text = [];
            foreach ($datas as $key => $title) {
                if (!isset($action_datas->{$key})) {
                    $text[] = $title;
                }
            }
            /*
             *  если массив пустой
             * значит все есть - можно создавать бронь
             */
            if (empty($text)) {
                $res = [
                    'suc' => 'yes',
                    'mes' => '',
                ];
            } else {
                $res = [
                    'suc' => 'not',
                    'mes' => $lang['poles_send'] . implode(', ', $text),
                ];
            }
        }
        return $res;
    }

    /*
     *  общие методы !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     */


    /*
    *  проверяем сколько времени ушло от времени последней записи в  if ($facecustomer->action_datas)
   */



  static function addBooking($booking_result, $facecustomer)
    {

        return 1;
    }
    /*
     * *********************************************************************************************
     */
    /*
     * проврка на все : стол время телефон
     */
    public static function validatingBookingTimeTablePhone($message)
    {
        $booking_result = [
            'suc' => false,
            'mes' => '',

            'booking_from' => false,
            'table' => false,
            'phone' => false,
            'date' => false,
            'time' => false,
            'isBooking' => false,
        ];

        $message = mb_strtolower($message);
        $booking_result['phone'] = PhoneValid::validatingPhone($message);
        $booking_result['table'] = TableFind::validatingTable($message);
        $booking_result['time'] = TimeFind::validatingTime($message);
        $booking_result['date'] = DateFind::validatingTime($message);
        $booking_result['isBooking'] = BookingFind::validatingBooking($message);

        return $booking_result;

    }


    // запись ответа  бота в чат
    // Удалить !!!!!
    public static function bot_messange_chat($facecustomer_id, $message)
    {
        $facemessage = new FaceMessage;
        $facemessage->face_customer_id = $facecustomer_id;
        $facemessage->message = $message;
        $facemessage->user_id = -1;
        $facemessage->save();
    }


}



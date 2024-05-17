<?php


namespace App\Bot;


class PhoneValid
{
    static public function validatingPhone($message, $facecustomer = null)
    {
        $valid_number = false;
        $message_arr = explode(" ", $message);

        foreach ($message_arr as $value) {
            $phone = preg_replace('/[^0-9]/', '', $value);
            $len = strlen($phone);
            if ($len == 10) {
                $valid_number = '+38' . $phone;
                break;
            } elseif ($len == 12) {
                $valid_number = '+' . $phone;
                break;
            }
        }
        return $valid_number;
    }
}

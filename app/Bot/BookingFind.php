<?php


namespace App\Bot;




class BookingFind
{
   public static function validatingBooking($message){
       $start_booking = \Storage::disk('bot')->get('start_booking.json');
       $input = @json_decode($start_booking, 1);
       $values = array_map(function ($v) {
           $v = mb_strtolower($v);
           $v = preg_replace('/\s+/', '', $v);
           return $v;
       }, $input);
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

    public static function addBooking($booking_result, $facecustomer)
    {
        $table_name = $booking_result['table'];
        $table = Table::where('type', 'like', '%' . $table_name . '%')->first();
        if($table){

        }else{
            return false;
        }
        // customer
        $phone=$booking_result["phone"];
        $customer = Customer::where('phone', $phone)->first();
        if (is_null($customer)) {
            $customer = new Customer;
            $customer->phone = $phone;
            if ($facecustomer->type == 'google') {
                $customer->name = $facecustomer->face_id;
            }
            $customer->save();
        }
        $booking = new Booking();
        $booking->table_id = $table->id;
        $booking->customer_id = $customer->id;
        //2023-12-16 12:03:00
        $booking->booking_from = $booking_result['date'].' '.$booking_result['time'];
        $booking->source = 'Соц. мережі';
        $booking->status = 1;
        $booking->phone = $phone;
        $booking->save();

        $facecustomer->action_datas=null;
        $facecustomer->next_action=null;
        $facecustomer->save();

        return $booking->id;
    }
}

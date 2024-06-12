<?php

namespace App\Http\Controllers;

use App\Bot\FaceBot;
use App\Bot\GoogleBot;
use App\Bot\InstaBot;
use App\Models\FaceCustomer;
use App\Models\FaceMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BotController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax() || $request->has('type')) {
            $facecustomers = collect();
            if ($request->has('type')) {
                $endDate = Carbon::now();
                $startDate = Carbon::now()->subDays(7);

                $face_customers_ = FaceCustomer::all();
                foreach ($face_customers_ as $face_customer) {
                    $last = $face_customer->face_messages->last();
                    if (is_null($last->user_id)) {
                        $created_at = $last->created_at;
                        if ($startDate->isBefore($created_at)) {
                            $facecustomers->add($face_customer);
                        }
                    }
                }
            } else {
                $bot_messages = FaceMessage::orderBy('created_at', 'desc')->whereNull('user_id')->get(['face_customer_id', 'created_at']);
                $facecustomer_ids = $bot_messages->pluck('face_customer_id')->unique()->values()->all();
                foreach ($facecustomer_ids as $facecustomer_id) {
                    $facecustomers->add(FaceCustomer::find($facecustomer_id));
                }
            }
            return response()->json($facecustomers);
        }
        return view('chat.index');
    }

    public function chat_new_messange()
    {
        $count = 0;
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(7);
        $face_customers = FaceCustomer::all();

        foreach ($face_customers as $face_customer) {
            $last = $face_customer->face_messages->last();
            if (is_null($last->user_id)) {
                $created_at = $last->created_at;
                if ($startDate->isBefore($created_at)) {
                    $count++;
                }
            }
        }
        return response()->json($count);
    }

    // получаем сообщения по конкретному пользователю
    public function bot_messages(Request $request)
    {
        $facecustomer_id = $request->facecustomer_id;
        $facecustomer = FaceCustomer::find($facecustomer_id);
        return response()->json($facecustomer->face_messages()->orderBy('id', 'asc')->get());
    }


    public function bot_send_messages(Request $request)
    {
        $user = Auth::user();
        $facecustomer_id = $request->facecustomer_id;
        $message = $request->message;

        $facecustomer = FaceCustomer::find($facecustomer_id);

        $facemessage = new FaceMessage;
        $facemessage->face_customer_id = $facecustomer_id;
        $facemessage->user_id = $user->id;
        $facemessage->message = $message;
        $facemessage->save();

        $response = [
            'recipient' => ['id' => $facecustomer->face_id],
            'message' => ['text' => $message],
            'conversation_id' => $facecustomer->conversation_id,
            'page_id' => $facecustomer->face_page_id
        ];
        if ($facecustomer->type == 'face') {
            FaceBot::send_mess($response);

        } elseif ($facecustomer->type == 'google') {
            GoogleBot::send_mess($response, $user->name);
        }
        elseif ($facecustomer->type == 'insta'){
            InstaBot::send_mess($response);
        }
        return response()->json(['suc' => 1]);
    }

    public function bot_search_messages(Request $request)
    {
        $facecustomers = collect();
        if ($request->has('q')) {
            $q = trim($request->q);
            $facecustomers = FaceCustomer::where('face_id', 'like', '%' . $q . '%')
                ->orwhereHas('customer', function ($qq) use ($q) {
                    $qq->where('phone', 'like', '%' . $q . '%');
                })->get();

        }
        if ($request->has('date_start')) {
            $date_start = Carbon::parse($request->date_start)->format('Y-m-d');
            $date_end = Carbon::parse($request->date_end)->format('Y-m-d');

            $bot_messages = FaceMessage::whereBetween('created_at', [$date_start . ' 00:00:00', $date_end . ' 23:59:59'])
                ->orderBy('created_at', 'desc')
                ->whereNull('user_id')
                ->get(['face_customer_id', 'created_at']);

            $facecustomer_ids = $bot_messages->pluck('face_customer_id')->unique()->values()->all();
            foreach ($facecustomer_ids as $facecustomer_id) {
                $facecustomers->add(FaceCustomer::find($facecustomer_id));
            }
        }
        return response()->json($facecustomers);
    }

    // delete
    public function face_messages()
    {
        return false;
    }

}

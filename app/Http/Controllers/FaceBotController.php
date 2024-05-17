<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FaceBotController extends Controller
{
    public function face_bot(Request $request){
//        $view='business';
        $view='index';
            return view('face.'.$view,[ ]);

    }
}

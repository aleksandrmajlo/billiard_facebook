<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FaceBotController extends Controller
{
    public function face_bot(Request $request){
//        $view='business';
        $view='index';
//        if($request->has('view')){
//            $view=$request->view;
            return view('face.'.$view,[ ]);
//        }else{
//            return view('face.index',[ ]);
//        }

    }
}

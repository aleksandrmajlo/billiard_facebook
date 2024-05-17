<?php


namespace App\Bot;


class NextAction
{
    public static function set($false,$true,$booking_result,$facecustomer,$lang){
        $action_datas=[];
        $next_action=implode(',', $false);
        foreach ($true as $item){
            $action_datas[$item] =$booking_result[$item];
        }
        $facecustomer->next_action=$next_action;
        $facecustomer->action_datas=$action_datas;
        $facecustomer->save();
        $mes_ar=[];
        foreach ($false as $item){
            $mes_ar[]=$lang[$item];
        }
        $mes=implode(', ',$mes_ar) ;
        return $lang['poles_send'].$mes;

    }
}

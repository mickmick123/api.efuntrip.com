<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper{
    public static function nextWeekDays(){
        $dt = Carbon::now();
        if($dt->isoFormat('dddd') === 'Friday'){
            return $dt->addDays(3)->format('Y-m-d');
        }else if($dt->isoFormat('dddd') === 'Saturday'){
            return $dt->addDays(2)->format('Y-m-d');
        }else if($dt->isoFormat('dddd') === 'Sunday'){
            return $dt->addDay()->format('Y-m-d');
        }
    }
}

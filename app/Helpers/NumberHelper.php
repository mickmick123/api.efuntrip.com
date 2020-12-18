<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class NumberHelper{
    //nnnf = no negative number format
    public static function nnnf($number){
        return number_format(abs($number));
    }
}

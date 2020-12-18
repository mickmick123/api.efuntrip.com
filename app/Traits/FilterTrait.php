<?php

namespace App\Traits;

trait FilterTrait
{
    public function absNumber($number) {
        if(empty($number)){
            return $number=0;
        }
        return number_format(abs($number));
    }


}

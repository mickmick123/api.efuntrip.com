<?php

namespace App\Traits;

trait FilterTrait
{
    /**
     * @Description Global function
    */
    public function absNumber($number) {
        if(empty($number)){
            return $number=0;
        }
        return number_format(abs($number));
    }


}

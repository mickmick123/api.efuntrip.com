<?php

namespace App\Helpers;

class ArrayHelper{
    // $array should be like this [[],[]] or more [[],[],[]....]
    public static function ArrayMerge($array){
        $data = [];
        foreach($array as $i){
            foreach($i as $j){
                $data[] = $j;
            }
        }
        return $data;
    }
}

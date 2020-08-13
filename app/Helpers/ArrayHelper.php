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

    public static function ArrayQueryPush($array,$column,$value){
        foreach ($array as $k=>$v){
            for($i=0;$i<count($column);$i++){
                $v[$column[$i]] = $value[$i];
            }
        }
        return $array;
    }
}

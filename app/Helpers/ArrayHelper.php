<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class ArrayHelper{
    // $array = [[1],[2]] or more [[1],[2],[3]....]
    // return [1,2,3,4,5]
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

    // $collection = [1,2,3,4]; $optional = '5'
    // return "1/2/3/4" or "1/2/3/4/5"
    public static function ArrayParentImplode($collection,$optional=null){
        $tree = new Collection($collection);
        $optional !== null && $tree->push($optional);
        return $tree->implode('/');
    }
}

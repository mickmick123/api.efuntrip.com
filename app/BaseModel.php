<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public function saveToDb($data){
        if(empty($data)){
            return false;
        }
        $this->fillable(array_keys($data));
        $this->fill($data);
        if($this->save()==false){
            return false;
        }
        return $this;
    }

    public function updateById($id,$upData)
    {
        $data = $this->where($id)->update($upData);
        if(!$data)
        {
            return false;
        }
        return true;
    }

}

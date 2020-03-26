<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    protected $table = 'orders';

    protected $fillable = ['name', 'address','contact','delivered_by','is_delivered'];

    public function details() {
        return $this->hasMany('App\OrderDetails', 'order_id', 'order_id');
    }

}

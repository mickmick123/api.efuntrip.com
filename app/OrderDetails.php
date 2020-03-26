<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{

    protected $table = 'order_details';

    protected $fillable = ['product_id', 'order_id','qty','order_status','remarks'];

    public function order() {
    	return $this->belongsTo('App\Order', 'order_id', 'order_id');
    }

}

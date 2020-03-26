<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{

    protected $table = 'order_details';

    protected $fillable = ['product_id', 'order_id','qty','order_status','remarks','unit_price','total_price'];

    public function order() {
    	return $this->belongsTo('App\Order', 'order_id', 'order_id');
    }

}

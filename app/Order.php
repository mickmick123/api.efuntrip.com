<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    protected $table = 'orders';
    public $primaryKey  = 'order_id';

    protected $fillable = ['user_id','name','last_name', 'address','contact','wechat_id','telegram','is_delivered','is_received','remarks','money_received','rmb_received','date_of_delivery','delivered_by'];

    public function details() {
        return $this->hasMany('App\OrderDetails', 'order_id', 'order_id');
    }

}

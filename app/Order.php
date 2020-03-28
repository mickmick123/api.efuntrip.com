<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    protected $table = 'orders';
    public $primaryKey  = 'order_id';

    protected $fillable = ['name', 'address','contact','wechat_id','is_delivered','remarks','money_received','date_of_delivery','delivered_by'];

    public function details() {
        return $this->hasMany('App\OrderDetails', 'order_id', 'order_id');
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{

    protected $table = 'order_logs';

    protected $fillable = ['order_id','log','log_date'];


}

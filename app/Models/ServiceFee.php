<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceFee extends Model
{
    //
    protected $fillable=[
        'user_id','action','currencies','amount','balance','rate','profit','total_ServiceFee','source','nickname','operator','number'
    ];
}

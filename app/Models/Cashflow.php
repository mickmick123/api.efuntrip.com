<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cashflow extends Model
{
    //
    protected $fillable=[
        'user_id','action','transfer_role','currencies','amount','balance','rate','profit','total_balance','source','nickname','operator','number'
    ];
}

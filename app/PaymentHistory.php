<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\GroupController;

use App\User;
use App\Group;

class PaymentHistory extends Model
{
    
    protected $table = 'payment_history';

    protected $fillable = ['service_id', 'log', 'amount', 'type', 'status'];

}

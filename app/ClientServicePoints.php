<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Http\Controllers\GroupController;

use App\Http\Controllers\ClientController;

use App\User;
use App\Group;

class ClientServicePoints extends Model
{
    
    protected $table = 'client_service_points';

    protected $fillable = ['id', 'client_service_id', 'points', 'created_at', 'updated_at'];

}

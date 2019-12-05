<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    
    protected $table = 'devices';

    protected $fillable = ['user_id', 'device_type', 'device_token'];

    public function user() {
    	return $this->belongsTo('App\User', 'user_id', 'id');
    }

}

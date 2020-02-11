<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    
    protected $table = 'attendance';

    protected $fillable = ['user_id', 'day', 'month', 'year', 'time_in', 'time_out', 'timein_status', 'timeout_status'];

    public function user() {
    	return $this->belongsTo('App\User', 'user_id', 'id');
    }

}

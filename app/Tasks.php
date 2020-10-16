<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tasks extends Model
{
    protected $table = 'tasks';
    protected $fillable = ['id', 'client_service_id', 'who_is_in_charge', 'date','reason'];
    public $timestamps = false;

    public function client_service() {
    	return $this->hasOne('App\ClientService', 'id', 'client_service_id');
    }

}

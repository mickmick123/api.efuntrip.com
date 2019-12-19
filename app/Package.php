<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    
    protected $table = 'packages';

    protected $fillable = ['client_id', 'group_id', 'tracking', 'status'];

    public function client() {
    	return $this->belongsTo('App\User', 'client_id', 'id');
    }

    public function clientServices() {
    	return $this->hasMany('App\ClientService', 'tracking', 'tracking');
    }
    
    public function group() {
    	return $this->belongsTo('App\Group', 'group_id', 'id');
    }

}

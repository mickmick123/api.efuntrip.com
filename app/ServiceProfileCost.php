<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceProfileCost extends Model
{
    
    protected $table = 'service_profile_cost';

    public $timestamps = false;

    protected $fillable = ['service_id', 'profile_id', 'cost', 'charge', 'tip', 'com_agent', 'com_client', 'branch_id', 'active'];

    public function service() {
    	return $this->belongsTo('App\Service', 'service_id', 'id');
    }

    public function profile() {
    	return $this->belongsTo('App\ServiceProfile', 'profile_id', 'id');
    }

    public function branch() {
    	return $this->belongsTo('App\Branch', 'branch_id', 'id');
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    
    protected $table = 'services';

    public $timestamps = false;

    protected $fillable = ['parent_id', 'detail', 'detail_cn', 'description', 'description_cn', 'cost', 'charge', 'tip', 'com_agent', 'com_client', 'is_active', 'months_required'];

    public function clientServices() {
        return $this->hasMany('App\ClientService', 'service_id', 'id');
    }
    
    public function serviceBranchCosts() {
    	return $this->hasMany('App\ServiceBranchCost', 'service_id', 'id');
    }

    public function serviceProfileCosts() {
    	return $this->hasMany('App\ServiceProfileCost', 'service_id', 'id');
    }

    public function serviceProcedures() {
        return $this->hasMany('App\ServiceProcedure', 'service_id', 'id');
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    
    protected $table = 'services';

    public $timestamps = false;

    protected $fillable = ['parent_id', 'detail', 'detail_cn', 'description', 'description_cn', 'cost', 'charge', 'tip', 'com_agent', 'com_client', 'is_active', 'months_required','max_months', 'min_months', 'form_id'];

    public function breakdowns() {
        return $this->hasMany('App\Breakdown', 'service_id', 'id');
    }

    public function clientServices() {
        return $this->hasMany('App\ClientService', 'service_id', 'id');
    }

    public function form() {
        return $this->belongsTo('App\Form', 'form_id', 'id');
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

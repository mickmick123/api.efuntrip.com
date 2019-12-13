<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceBranchCost extends Model
{
    
    protected $table = 'service_branch_cost';

    public $timestamps = false;

    protected $fillable = ['service_id', 'branch_id', 'cost', 'charge', 'tip', 'com_agent', 'com_client'];

    public function service() {
    	return $this->belongsTo('App\Service', 'service_id', 'id');
    }

    public function branch() {
    	return $this->belongsTo('App\Branch', 'branch_id', 'id');
    }

}

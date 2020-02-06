<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Breakdown extends Model
{

    protected $table = 'breakdowns';

    protected $fillable = ['type', 'description', 'amount', 'service_id', 'branch_id', 'service_profile_id'];

    public function service() {
    	return $this->belongsTo('App\Service', 'service_id', 'id');
    }

    public function branch() {
    	return $this->belongsTo('App\Branch', 'branch_id', 'id');
    }

    public function serviceProfile() {
    	return $this->belongsTo('App\ServiceProfile', 'service_profile_id', 'id');
    }

}

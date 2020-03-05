<?php

namespace App;

use App\Http\Controllers\ServiceProfileCostController;

use Illuminate\Database\Eloquent\Model;

class ServiceProfile extends Model
{
    
    protected $table = 'service_profiles';

    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'with_agent_commision', 'with_client_commision', 'is_active'];

    public static function boot() {
        parent::boot();

        self::created(function($model) {
            ServiceProfileCostController::createData();
        });
    }

    public function breakdowns() {
        return $this->hasMany('App\Breakdown', 'service_profile_id', 'id');
    }

    public function groups() {
    	return $this->hasMany('App\Group', 'service_profile_id', 'id');
    }

    public function serviceProfileCosts() {
    	return $this->hasMany('App\ServiceProfileCost', 'profile_id', 'id');
    }

}

<?php

namespace App;

use App\Http\Controllers\ServiceProfileCostController;

use Illuminate\Database\Eloquent\Model;

class ServiceProfile extends Model
{
    
    protected $table = 'service_profiles';

    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'is_active'];

    public static function boot() {
        parent::boot();

        self::created(function($model) {
            ServiceProfileCostController::createData();
        });
    }

    public function groups() {
    	return $this->hasMany('App\Group', 'service_profile_id', 'id');
    }

    public function serviceProfileCosts() {
    	return $this->hasMany('App\ServiceProfileCost', 'profile_id', 'id');
    }

}

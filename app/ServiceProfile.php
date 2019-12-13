<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceProfile extends Model
{
    
    protected $table = 'service_profiles';

    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'is_active'];

    public function groups() {
    	return $this->hasMany('App\Group', 'service_profile_id', 'id');
    }

    public function serviceProfileCosts() {
    	return $this->hasMany('App\ServiceProfileCost', 'profile_id', 'id');
    }

}

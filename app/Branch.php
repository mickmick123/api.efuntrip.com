<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    
    use SoftDeletes;

    protected $table = 'branches';

    protected $fillable = ['name'];

    public function breakdowns() {
        return $this->hasMany('App\Breakdown', 'branch_id', 'id');
    }

    public function groups() {
    	return $this->belongsToMany('App\Group', 'branch_group', 'branch_id', 'group_id');
    }

    public function serviceBranchCosts() {
    	return $this->hasMany('App\ServiceBranchCost', 'branch_id', 'id');
    }

    public function serviceProfileCosts() {
    	return $this->hasMany('App\ServiceProfileCost', 'branch_id', 'id');
    }

    public function users() {
    	return $this->belongsToMany('App\User', 'branch_user', 'branch_id', 'user_id');
    }

}

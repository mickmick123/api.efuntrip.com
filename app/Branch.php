<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    
    use SoftDeletes;

    protected $table = 'branches';

    protected $fillable = ['name'];

    public function groups() {
    	return $this->belongsToMany('App\Group', 'branch_group', 'branch_id', 'group_id');
    }

    public function users() {
    	return $this->belongsToMany('App\User', 'branch_user', 'branch_id', 'user_id');
    }

}

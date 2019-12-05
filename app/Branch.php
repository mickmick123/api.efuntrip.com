<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    
    protected $table = 'branches';

    protected $fillable = ['name'];

    public function users() {
    	return $this->belongsToMany('App\User', 'branch_user', 'branch_id', 'user_id');
    }

}

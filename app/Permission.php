<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    
    // use SoftDeletes;

    protected $table = 'permissions';

    protected $fillable = ['name', 'label', 'type'];

    public function users() {
        return $this->belongsToMany('App\User', 'permission_user', 'permission_id', 'user_id');
    }

}

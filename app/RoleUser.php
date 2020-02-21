<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{

    protected $table = 'role_user';

    protected $fillable = ['id', 'role_id', 'user_id'];

    public function user() {
    	return $this->belongTo('App\User', 'id', 'user_id');
    }


}

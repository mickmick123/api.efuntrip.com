<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DepartmentUser extends Model
{
    
    protected $table = 'department_user';

    protected $fillable = ['department_id', 'user_id'];

    public function departments() {
    	return $this->belongsTo('App\Department', 'department_id', 'id');
    }

    public function user() {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function attendance() {
    	return $this->hasMany('App\Attendance', 'user_id', 'user_id')->orderBy('created_at', 'desc');
    }

}

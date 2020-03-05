<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    
    protected $table = 'actions';

    protected $fillable = ['name', 'order_of_precedence'];

    public function categories() {
    	return $this->belongsToMany('App\Category', 'action_category', 'action_id', 'category_id');
    }

    public function serviceProcedures() {
        return $this->hasMany('App\ServiceProcedure', 'action_id', 'id');
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    
    protected $table = 'actions';

    protected $fillable = ['name'];

    public function categories() {
    	return $this->belongsToMany('App\Category', 'action_category', 'action_id', 'category_id');
    }

}

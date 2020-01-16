<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    
    protected $table = 'categories';

    protected $fillable = ['name', 'name_cn'];

    public function actions() {
    	return $this->belongsToMany('App\Action', 'action_category', 'category_id', 'action_id');
    }

}

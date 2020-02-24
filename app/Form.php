<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    
	protected $table = 'forms';

	public $timestamps = false;

    protected $fillable = ['name'];

    public function services() {
    	return $this->hasMany('App\Services', 'form_id', 'id');
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    
    protected $table = 'countries';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function users() {
    	return $this->hasMany('App\User', 'birth_country_id', 'id');
    } 

}

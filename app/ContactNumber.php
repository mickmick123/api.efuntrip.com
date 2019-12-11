<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactNumber extends Model
{
    
    protected $table = 'contact_numbers';

    protected $fillable = ['user_id', 'group_id', 'number', 'is_primary', 'is_mobile'];

    public function user() {
    	return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function group() {
    	return $this->belongsTo('App\Group', 'group_id', 'id');
    }

}

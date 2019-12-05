<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactNumber extends Model
{
    
    protected $table = 'contact_numbers';

    protected $fillable = ['user_id', 'number', 'is_primary', 'is_mobile'];

    public function user() {
    	return $this->belongsTo('App\User', 'user_id', 'id');
    }

}

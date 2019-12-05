<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
    
    protected $table = 'nationalities';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function users() {
        return $this->belongsToMany('App\User', 'nationality_user', 'nationality_id', 'user_id');
    }

}

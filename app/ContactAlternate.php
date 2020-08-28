<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactAlternate extends Model
{
    
    protected $table = 'contact_alternate';

    protected $fillable = ['user_id', 'group_id', 'detail', 'type'];

}

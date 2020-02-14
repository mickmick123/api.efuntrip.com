<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Updates extends Model
{

    protected $table = 'updates';

    protected $fillable = ['client_id', 'type'];

}

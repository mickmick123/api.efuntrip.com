<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'ref_location';
    public $timestamps = false;

    protected $fillable = ['location','type'];
}

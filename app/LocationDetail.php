<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LocationDetail extends Model
{
    protected $table = 'ref_location_detail';
    public $timestamps = false;

    protected $fillable = ['loc_id','location_detail'];
}

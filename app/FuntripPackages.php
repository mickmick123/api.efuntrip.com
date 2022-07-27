<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FuntripPackages extends Model
{
    protected $table = 'funtrip_packages';

    protected $fillable = ['package_name', 'package_price', 'package_url', 'package_description'];
}

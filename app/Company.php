<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'company';

    protected $fillable = ['company_id','name','chinese_name','company_img','created_at','updated_at'];

    public $timestamps = false;
}

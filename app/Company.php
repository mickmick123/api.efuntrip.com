<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'company';
    public $primaryKey  = 'company_id';
    public $timestamps = false;

    protected $fillable = ['name','chinese_name','created_at','updated_at'];
}

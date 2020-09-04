<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Remark extends Model
{
    protected $table = 'remarks';
    public $timestamps = false;

    protected $fillable = ['client_id','group_id','remark','created_at'];
}

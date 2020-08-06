<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryLogs extends Model
{
    protected $table = 'inventory_logs';
    public $timestamps = false;

    protected $fillable = ['id','inventory_id','type','reason','created_by','created_at'];
}

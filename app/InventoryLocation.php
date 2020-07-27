<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryLocation extends Model
{
    protected $table = 'inventory_location';
    public $primaryKey  = 'id';
    public $timestamps = false;

    protected $fillable = ['inventory_id','qty','location','created_at','updated_at'];
}

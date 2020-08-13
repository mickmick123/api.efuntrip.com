<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryUnit extends Model
{
    protected $table = 'inventory_unit';
    public $primaryKey  = 'unit_id';
    public $timestamps = false;

    protected $fillable = ['name','created_at','updated_at'];
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventorySellingUnit extends Model
{
    protected $table = 'inventory_selling_unit';
    public $timestamps = false;

    protected $fillable = ['inv_id','unit_id','qty'];
}

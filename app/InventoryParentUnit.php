<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryParentUnit extends Model
{
    protected $table = 'inventory_parent_unit';
    public $timestamps = false;

    protected $fillable = ['inventory_id','unit_id','parent_id','content','min_purchased'];
}

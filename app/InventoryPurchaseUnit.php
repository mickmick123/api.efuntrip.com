<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryPurchaseUnit extends Model
{
    protected $table = 'inventory_purchase_unit';
    public $timestamps = false;

    protected $fillable = ['inv_id','unit_id','parent_id','content','min_purchased'];
}

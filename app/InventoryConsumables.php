<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryConsumables extends Model
{
    protected $table = 'inventory_consumables';
    public $timestamps = false;

    protected $fillable = ['inventory_id', 'assigned_to','order_id','set','qty','price','location_id','supp_name','supp_location','type','created_at','created_at','updated_at'];
}

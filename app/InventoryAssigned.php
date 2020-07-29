<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryAssigned extends Model
{
    protected $table = 'inventory_assigned';
    public $primaryKey  = 'id';
    public $timestamps = false;

    protected $fillable = ['inventory_id', 'assigned_qty','assigned_to','serial','date_purchased','location_site','location_detail','status','created_at','updated_at'];
}

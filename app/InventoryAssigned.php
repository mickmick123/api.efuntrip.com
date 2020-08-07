<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryAssigned extends Model
{
    protected $table = 'inventory_assigned';
    public $timestamps = false;

    protected $fillable = ['inventory_id', 'assigned_to','model','serial','type','name','purchase_price','date_purchased','hasOR','location_site','location_detail','status','remarks','created_by','updated_by','created_at','updated_at'];
}

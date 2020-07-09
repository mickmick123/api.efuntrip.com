<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';
    public $primaryKey  = 'inventory_id';
    public $timestamps = false;

    protected $fillable = ['category_id', 'company_id', 'inventory_name','inventory_name_chinese','inventory_price','orig_price','unit','multiplier', 'inventory_img', 'inventory_description', 'status'];
}

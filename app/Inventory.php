<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';
    public $primaryKey  = 'inventory_id';
    public $timestamps = false;

    protected $fillable = ['company_id','category_id','name','name_chinese','inventory_img','description','specification','type','purchase_price','or','qty','unit','created_at','updated_at'];
}

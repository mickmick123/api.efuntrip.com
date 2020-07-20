<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';
    public $primaryKey  = 'inventory_id';
    public $timestamps = false;

    protected $fillable = ['company_id','category_id','name','name_chinese','serial_no','model','date_purchased','inventory_img','notes','specification','status','is_assigned','assigned_to','type','location_site','location_detail','purchase_price','or','qty','unit','created_at','updated_at'];
}

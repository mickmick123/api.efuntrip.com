<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryConsumablesSetting extends Model
{
    protected $table = 'inventory_consumables_setting';
    public $primaryKey  = 'setting_id';
    public $timestamps = false;

    protected $fillable = ['inv_id','length','width','height','imported_rmb_price','shipping_fee_per_cm','rmb_rate','market_price_min','market_price_max','advised_sale_price','actual_sale_price','id','expiration_date','remarks'];
}

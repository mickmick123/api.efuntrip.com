<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use JustBetter\PaginationWithHavings\PaginationWithHavings;

class Inventory extends Model
{
    use PaginationWithHavings;
    protected $table = 'inventory';
    public $primaryKey  = 'inventory_id';
    public $timestamps = false;

    protected $fillable = ['company_id','category_id','name','name_chinese','inventory_img','description','specification','type','unit_id','sell','purchase_price','or','qty','unit','created_at','updated_at'];

    public function units(){
        return $this->hasMany('App\InventoryPurchaseUnit', 'inv_id','inventory_id')
            ->leftJoin('inventory_unit', 'inventory_unit.unit_id', '=', 'inventory_purchase_unit.unit_id')
            ->orderBy("inventory_purchase_unit.id", "ASC");
    }
}

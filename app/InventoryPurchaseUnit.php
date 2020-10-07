<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryPurchaseUnit extends Model
{
    protected $table = 'inventory_purchase_unit';
    public $timestamps = false;

    protected $fillable = ['inv_id','unit_id','qty','last_unit_id','parent_id'];

    public static $inventory_id = 0;

    public function subCategories()
    {
        return $this->hasMany(self::class, 'parent_id', 'unit_id')->with('subCategories')
            ->leftJoin('inventory_unit as iunit', 'iunit.unit_id', '=', 'inventory_purchase_unit.unit_id')
            ->where('inventory_purchase_unit.inv_id',self::$inventory_id)->orderBy('iunit.name', 'asc');
    }
}

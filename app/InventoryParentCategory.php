<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryParentCategory extends Model
{
    protected $table = 'inventory_parent_category';

    protected $fillable = ['id','company_id','category_id','parent_id'];

    public $timestamps = false;

    public function subCategories()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->with('inventories', 'subCategories.inventories')
            ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->orderBy('inventory_category.name', 'asc');
    }

    public function subCategoriesWithInventories()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->with('inventories', 'subCategoriesWithInventories')
            ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id');
    }

    public function inventories()
    {
        return $this->hasMany('App\Inventory', 'category_id', 'id');
    }
}

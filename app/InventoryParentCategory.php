<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryParentCategory extends Model
{
    protected $table = 'inventory_parent_category';

    protected $fillable = ['id','company_id','category_id','parent_id'];

    public $timestamps = false;

    protected $appends = [
        'parents'
    ];

    protected $hidden = [
        'parent'
    ];

    public function parent()
    {
        return $this->belongsTo('App\InventoryParentCategory', 'parent_id','category_id')
                    ->where('company_id', $this->company_id)
                    ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id');
    }

    public function subCategories()
    {
        return $this->hasMany(self::class, 'parent_id', 'category_id')
                // ->where('company_id', $this->company_id)
                ->with('subCategories')
                // ->with('inventories', 'subCategories.inventories')
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id');
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


    // ** ATTRIBUTES ** //

    public function getParentsAttribute()
    {
        $parents = collect([]);

        $parent = $this->parent;

        while(!is_null($parent)) {
            $parents->push($parent);
            $parent =  $parent->parent;
        }

        return $parents;
    }
}

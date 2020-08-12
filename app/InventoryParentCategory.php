<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryParentCategory extends Model
{
    protected $table = 'inventory_parent_category';

    protected $fillable = ['company_id','category_id','parent_id'];

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

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id','category_id')
                    ->where('company_id', $this->company_id)
                    ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id');
    }

    public function subCategories()
    {
        return $this->hasMany(self::class, 'parent_id', 'category_id')->with('inventories', 'subCategories')
            ->leftJoin('inventory_category as icat', 'icat.category_id', '=', 'inventory_parent_category.category_id')->orderBy('icat.name', 'asc');
    }

    public function inventories()
    {
        return $this->hasMany('App\Inventory', 'category_id', 'id');
    }

    public function getAllChildren()
    {
        $sections = collect([]);

        foreach ($this->children as $section) {
            $sections->push($section);
            $sections = $sections->merge($section->getAllChildren());
        }

        return $sections;
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

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductParentCategory extends Model
{

    protected $table = 'product_parent_category';

    protected $fillable = ['id','category_id','parent_id'];

    public $timestamps = false;

    public function subCategories()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->with('products', 'subCategories.products')
        		->leftJoin('product_category', 'product_category.category_id', '=', 'product_parent_category.category_id')->orderBy('product_category.name', 'asc');
    }

    public function subCategoriesWithProducts()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->with('products', 'subCategoriesWithProducts')
        		->leftJoin('product_category', 'product_category.category_id', '=', 'product_parent_category.category_id');
    }

    public function products()
    {
        return $this->hasMany('App\Product', 'category_id', 'id');
    }

}

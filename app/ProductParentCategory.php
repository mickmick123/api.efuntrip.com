<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductParentCategory extends Model
{

    protected $table = 'product_parent_category';

    protected $fillable = ['id','category_id'];


    public function mainCategories() {
        return $this->hasMany('App\ProductMainCategory', 'parent_category_id', 'id')
                ->leftJoin('product_category', 'product_category.category_id', '=', 'product_main_category.category_id');
    }

    public function subCategories() {
        return $this->hasMany('App\ProductSubCategory', 'main_category_id', 'id')
                ->leftJoin('product_category', 'product_category.category_id', '=', 'product_subcategory.category_id');
    }

    public function category() {
        return $this->hasOne('App\ProductCategory', 'category_id', 'category_id');
    }

}

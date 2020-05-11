<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductSubCategory extends Model
{

    protected $table = 'product_subcategory';

    protected $fillable = ['id','main_category_id','category_id'];


    public function subCategories() {
        return $this->hasMany('App\ProductMainCategory', 'id', 'parent_category_id')->hasOne('App\ProductCategory', 'category_id', 'category_id');
    }

}

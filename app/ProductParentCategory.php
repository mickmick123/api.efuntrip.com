<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductParentCategory extends Model
{

    protected $table = 'product_parent_category';

    protected $fillable = ['id','category_id','parent_id'];

    public $timestamps = false;

    public function subCategories() {
        return $this->hasMany('App\ProductParentCategory', 'parent_id', 'id')
                ->leftJoin('product_category', 'product_category.category_id', '=', 'product_parent_category.category_id');
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductParentCategory extends Model
{

    protected $table = 'product_category_id';

    protected $fillable = ['id','parent_category_id','main_category_id','subcategory_id','product_id'];


}

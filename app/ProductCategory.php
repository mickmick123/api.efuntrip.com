<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{

    protected $table = 'product_category';

    protected $fillable = ['category_id','name','name_chinese','description'];

}

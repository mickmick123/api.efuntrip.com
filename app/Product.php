<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    protected $table = 'product';

    protected $fillable = ['category_id', 'product_name','product_name_chinese','product_price'];

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    protected $table = 'product';
    public $primaryKey  = 'product_id';
    public $timestamps = false;

    protected $fillable = ['category_id', 'product_name','product_name_chinese','product_price','orig_price','unit','multiplier', 'product_img', 'product_description', 'status'];

}

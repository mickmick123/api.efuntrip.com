<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    protected $table = 'inventory_category';

    protected $fillable = ['category_id','name','name_chinese','description','category_img', 'status'];

    public $timestamps = false;
}

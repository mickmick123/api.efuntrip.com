<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    protected $table = 'inventory_category';
    public $primaryKey  = 'category_id';
    public $timestamps = false;

    protected $fillable = ['category_id','name','name_chinese','description','category_img', 'status'];
}

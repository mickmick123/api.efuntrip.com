<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use JustBetter\PaginationWithHavings\PaginationWithHavings;

class Inventory extends Model
{
    use PaginationWithHavings;
    protected $table = 'inventory';
    public $primaryKey  = 'inventory_id';
    public $timestamps = false;

    protected $fillable = ['company_id','category_id','name','name_chinese','inventory_img','description','specification','type','unit_id','sell','purchase_price','or','qty','unit','created_at','updated_at'];
}

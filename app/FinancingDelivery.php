<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class FinancingDelivery extends Model
{
    use SoftDeletes;
    
    protected $table = 'financing_delivery';

	  protected $fillable = [
	   'trans_desc', 'cat_type', 'purchasing_budget','purchasing_budget_return','delivery_budget','delivery_budget_return','other_cost','other_received','chmoney_paid','chmoney_used','cash_balance','ch_balance','created_at','record_id'
	  ];


}

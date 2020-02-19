<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Financing extends Model
{
    use SoftDeletes;
    
    protected $table = 'financing';

	  protected $fillable = [
	   'trans_desc', 'cat_type', 'cat_storage','cash_client_depo_payment','cash_client_refund','cash_client_process_budget_return','cash_process_cost','borrowed_process_cost','cash_admin_budget_return','borrowed_admin_cost','cash_admin_cost','	bank_client_depo_payment','bank_cost','cash_balance','bank_balance','postdated_checks','cost_other','deposit_other','cost_type','additional_budget','user_sn','branch_id','created_at','storage_type','metrobank','securitybank','aub','eastwest','type','record_id'
	  ];


}

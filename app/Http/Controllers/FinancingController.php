<?php

namespace App\Http\Controllers;

use App\Financing;

use Response;

use Illuminate\Http\Request;

use Carbon\Carbon;

use DB;

class FinancingController extends Controller
{
    
    public function show($date, $branch_id) {
    	$date = str_replace("-", "/", $date);
    	$dateSelector = Carbon::parse($date.'/01')->toDateTimeString();
    	$now = Carbon::now();

    	$checkInitial = DB::table('financing')->where('cat_type','initial')
    						->where('branch_id',$branch_id)
    						->whereRaw('MONTH(created_at) = MONTH("'.$dateSelector.'")')
    						->where('deleted_at',null)
    						->orderBy('created_at', 'DESC')->count();

        $checkData = DB::table('financing')->where('branch_id',$branch_id)->count();
          
        $month = Carbon::parse($dateSelector);

		if(($checkInitial==0 && $checkData>0) && $now->month==$month->month){
			$this->fixInitial($branch_id,0);
		}

    	$query = DB::table('financing as f')
    				->select(array('f.*'))
    				->whereRaw('MONTH(created_at) = MONTH("'.$dateSelector.'")')
    				->where('branch_id',$branch_id)
    				->where('deleted_at',null)->orderBy('created_at', 'DESC')
    				->paginate(5000);
    	$ctr = 1;			
    	foreach($query as $q){
    		$q->index = $ctr;
    		$ctr++;
    	}


		$response['status'] = 'Success';
		$response['data'] = $query;
		$response['code'] = 200;

		return Response::json($response);
	}


	public function fixInitial($branch_id,$dd){
     
      $now = Carbon::now();
      if($dd==0){
        $initial = DB::table('financing')->where('branch_id',$branch_id)->where('cat_type','initial')->orderBy('created_at', 'desc')->first();
      }else{
        $initial = DB::table('financing')->where('branch_id',$branch_id)->where('cat_type','initial')->orderBy('created_at', 'desc')->skip(1)->first();
      }
   
      if($initial){
        
        $month = Carbon::parse($initial->created_at);
        
        $cash = $initial->cash_balance;
        $metrobank = $initial->metrobank;
        $securitybank = $initial->securitybank;
        $aub = $initial->aub;
        $eastwest = $initial->eastwest;

        for($i=$month->month;$i<$now->month;$i++){

          $query = DB::table('financing')->where('branch_id',$branch_id)->whereRaw('MONTH(created_at) = "'.$i.'"')->where('cat_type','!=','initial')->orderBy('created_at', 'desc')->where('deleted_at',null)->get();
            if(count($query)>0){
              foreach($query as $q){
                $cash = (
                			$cash+
                            + $q->cash_balance
                            + $q->cash_client_depo_payment
                            + ($q->cat_storage=='cash' ? $q->cash_client_process_budget_return:0)
                            + ($q->cat_storage=='cash' ? $q->cash_admin_budget_return:0)
                            + ($q->cat_storage=='cash' ? $q->deposit_other:0)
                        )
                        - 
                        (
                        	($q->cat_storage=='cash' ? $q->cash_client_refund:0)
                            + ($q->cat_storage=='cash' ? $q->cash_process_cost:0)
                            + ($q->cat_storage=='cash' ? $q->cash_admin_cost:0)
                            + ($q->cat_storage=='cash' ? $q->additional_budget:0)
                            + $q->borrowed_admin_cost
                            + $q->borrowed_process_cost
                            + (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other==null || $q->deposit_other=='') && $q->cat_storage=='cash' ? $q->cost_other:0)
                            + (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other!=null && $q->deposit_other!='') && $q->cat_storage=='bank' ? $q->cost_other:0)
                        );

				$metrobank = (
								$metrobank
								+ $q->metrobank
								+ ($q->cat_storage=='bank' && $q->storage_type=='metrobank' ? $q->bank_client_depo_payment:0)
								+ ($q->cat_storage=='bank' && $q->storage_type=='metrobank' ? $q->cash_client_process_budget_return:0)
								+ ($q->cat_storage=='bank' && $q->storage_type=='metrobank' ? $q->cash_admin_budget_return:0)
								+ ($q->cat_storage=='bank' && $q->storage_type=='metrobank' ? $q->deposit_other:0)
							)
							-
							(
								($q->cat_storage=='bank' && $q->storage_type=='metrobank' ? $q->cash_admin_cost:0)
								+ ($q->cat_storage=='bank' && $q->storage_type=='metrobank' ? $q->cash_client_refund:0)
								+ ($q->cat_storage=='bank' && $q->storage_type=='metrobank' ? $q->cash_process_cost:0)
								+ ($q->cat_storage=='bank' && $q->storage_type=='metrobank' ? $q->additional_budget:0)
								+ (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other==null || $q->deposit_other=='') && $q->cat_storage=='bank' && $q->storage_type=='metrobank'? $q->cost_other:0)
								+ (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other!=null && $q->deposit_other!='') && $q->cat_storage=='cash' && $q->storage_type=='metrobank' ? $q->cost_other:0)
							);

                $securitybank = (
                				$securitybank
                            	+ $q->securitybank
	                            + ($q->cat_storage=='bank' && $q->storage_type=='securitybank' ? $q->bank_client_depo_payment:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='securitybank' ? $q->cash_client_process_budget_return:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='securitybank' ? $q->cash_admin_budget_return:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='securitybank' ? $q->deposit_other:0)
	                        )
	                        -
	                        (
	                            ($q->cat_storage=='bank' && $q->storage_type=='securitybank' ? $q->cash_admin_cost:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='securitybank' ? $q->cash_client_refund:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='securitybank' ? $q->cash_process_cost:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='securitybank' ? $q->additional_budget:0)
	                            + (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other==null || $q->deposit_other=='') && $q->cat_storage=='bank' && $q->storage_type=='securitybank'? $q->cost_other:0)
	                            + (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other!=null && $q->deposit_other!='') && $q->cat_storage=='cash' && $q->storage_type=='securitybank' ? $q->cost_other:0)
	                        );

                $aub =  (
                			$aub
                            + $q->aub
                            + (($q->cat_storage=='bank' || $q->cat_storage=='alipay') && $q->storage_type=='aub' ? $q->bank_client_depo_payment:0)
                            + ($q->cat_storage=='bank' && $q->storage_type=='aub' ? $q->cash_client_process_budget_return:0)
                            + ($q->cat_storage=='bank' && $q->storage_type=='aub' ? $q->cash_admin_budget_return:0)
                            + ($q->cat_storage=='bank' && $q->storage_type=='aub' ? $q->deposit_other:0)
                        )
                        -
                        (
                            ($q->cat_storage=='bank' && $q->storage_type=='aub' ? $q->cash_admin_cost:0)
                            + ($q->cat_storage=='bank' && $q->storage_type=='aub' ? $q->cash_client_refund:0)
                            + ($q->cat_storage=='bank' && $q->storage_type=='aub' ? $q->cash_process_cost:0)
                            + ($q->cat_storage=='bank' && $q->storage_type=='aub' ? $q->additional_budget:0)
                            + (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other==null || $q->deposit_other=='') && $q->cat_storage=='bank' && $q->storage_type=='aub'? $q->cost_other:0)
                            + (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other!=null && $q->deposit_other!='') && $q->cat_storage=='cash' && $q->storage_type=='aub' ? $q->cost_other:0)
                        );
                           
                $eastwest = (
                				$eastwest
	                            + $q->eastwest
	                            + (($q->cat_storage=='bank' || $q->cat_storage=='alipay') && $q->storage_type=='eastwest' ? $q->bank_client_depo_payment:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='eastwest' ? $q->cash_client_process_budget_return:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='eastwest' ? $q->cash_admin_budget_return:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='eastwest' ? $q->deposit_other:0)
	                        )
	                        -
	                        (
	                            ($q->cat_storage=='bank' && $q->storage_type=='eastwest' ? $q->cash_admin_cost:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='eastwest' ? $q->cash_client_refund:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='eastwest' ? $q->cash_process_cost:0)
	                            + ($q->cat_storage=='bank' && $q->storage_type=='eastwest' ? $q->additional_budget:0)
	                            + (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other==null || $q->deposit_other=='') && $q->cat_storage=='bank' && $q->storage_type=='eastwest'? $q->cost_other:0)
	                            + (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other!=null && $q->deposit_other!='') && $q->cat_storage=='cash' && $q->storage_type=='eastwest' ? $q->cost_other:0)
	                        );  

              }
            }
            
            $ndate1 = explode(' ',$now);
            $ndate2 = explode('-',$ndate1[0]);
            $ndate3 = Carbon::parse($ndate2[0].'/'.($i+1).'/01')->toDateTimeString();
            //dd($cash);
            
            if($dd==0){
              Financing::create([
                'cat_type'=>'initial',
                'cash_balance'=>$cash,
                'metrobank'=>$metrobank,
                'securitybank'=>$securitybank,
                'aub'=>$aub,
                'eastwest'=>$eastwest,
                'branch_id'=>$branch_id,
                'created_at'=>$ndate3
              ]);
            }else{
              $initial2 = DB::table('financing')->where('branch_id',$branch_id)->where('cat_type','initial')->orderBy('created_at', 'desc')->first();
              Financing::where('id',$initial2->id)->update(['cash_balance'=>$cash,'metrobank'=>$metrobank,'securitybank'=>$securitybank,'aub'=>$aub,'eastwest'=>$eastwest]);
            }

            $initial = DB::table('financing')->where('branch_id',$branch_id)->where('cat_type','initial')->orderBy('created_at', 'desc')->first();
            $cash = $initial->cash_balance;
            $metrobank = $initial->metrobank;
            $securitybank = $initial->securitybank;
            $aub = $initial->aub;
            $eastwest = $initial->eastwest;
             
        }
      }
    }

}

<?php

namespace App\Http\Controllers;

use App\Financing;

use App\RoleUser;

use App\User;

use Response;

use Illuminate\Http\Request;

use Carbon\Carbon;

use DB, Auth;

class FinancingController extends Controller
{
    
    public function show($date, $branch_id) {
    	$date = str_replace("-", "/", $date);
    	$dateSelector = Carbon::parse($date.'/01')->toDateTimeString();
      \Log::info($dateSelector);
    	$now = Carbon::now();

    	$checkInitial = DB::table('financing')->where('cat_type','initial')
    						->where('branch_id',$branch_id)
    						->whereRaw('MONTH(created_at) = MONTH("'.$dateSelector.'")')
                ->whereRaw('YEAR(created_at) = YEAR("'.$dateSelector.'")')
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
            ->whereRaw('YEAR(created_at) = YEAR("'.$dateSelector.'")')
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
        $y = $month->year;
        
        $cash = $initial->cash_balance;
        $metrobank = $initial->metrobank;
        $securitybank = $initial->securitybank;
        $aub = $initial->aub;
        $eastwest = $initial->eastwest;
        $chinabank = $initial->chinabank;
        $pnb = $initial->pnb;

        for($i=$month->month;$i<$now->month;$i++){
          \Log::info($i);
          \Log::info($y);
          $query = DB::table('financing')->where('branch_id',$branch_id)
                    ->whereRaw('MONTH(created_at) = "'.$i.'"')
                    ->whereRaw('YEAR(created_at) = "'.$y.'"')
                    ->where('cat_type','!=','initial')
                    ->orderBy('created_at', 'desc')->where('deleted_at',null)->get();
                \Log::info($query);
            if(count($query)>0){
              foreach($query as $q){
                $cash = (
                			$cash +
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

                $metrobank = ( floatval($metrobank) + floatval($q->metrobank) + $this->bankComputation('metrobank', $q));

                $securitybank = ( floatval($securitybank) + floatval($q->aub) + $this->bankComputation('aub', $q));

                $aub = ( floatval($aub) + floatval($q->aub) + $this->bankComputation('aub', $q));

                $eastwest = ( floatval($eastwest) + floatval($q->eastwest) + $this->bankComputation('eastwest', $q));

	            $chinabank = ( floatval($chinabank) + floatval($q->chinabank) + $this->bankComputation('chinabank', $q));

	            $pnb = ( floatval($pnb) + floatval($q->pnb) + $this->bankComputation('pnb', $q));

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
                'chinabank'=>$chinabank,
                'pnb'=>$pnb,
                'branch_id'=>$branch_id,
                'created_at'=>$ndate3
              ]);
            }else{
              $initial2 = DB::table('financing')->where('branch_id',$branch_id)->where('cat_type','initial')->orderBy('created_at', 'desc')->first();
              Financing::where('id',$initial2->id)->update(['cash_balance'=>$cash,'metrobank'=>$metrobank,'securitybank'=>$securitybank, 'aub'=>$aub, 'eastwest'=>$eastwest, 'chinabank'=>$chinabank, 'pnb'=>$pnb]);
            }

            $initial = DB::table('financing')->where('branch_id',$branch_id)->where('cat_type','initial')->orderBy('created_at', 'desc')->first();
            $cash = $initial->cash_balance;
            $metrobank = $initial->metrobank;
            $securitybank = $initial->securitybank;
            $aub = $initial->aub;
            $eastwest = $initial->eastwest;
            $chinabank = $initial->chinabank;
            $pnb = $initial->pnb;
             
        }
      }
    }

    public function bankComputation($bank, $q){
           return ((($q->cat_storage=='bank' || $q->cat_storage=='alipay') && $q->storage_type==$bank ? $q->bank_client_depo_payment:0)
                    + ($q->cat_storage=='bank' && $q->storage_type==$bank ? $q->cash_client_process_budget_return:0)
                    + ($q->cat_storage=='bank' && $q->storage_type==$bank ? $q->cash_admin_budget_return:0)
                    + ($q->cat_storage=='bank' && $q->storage_type==$bank ? $q->deposit_other:0)
                )
                -
                (
                    ($q->cat_storage=='bank' && $q->storage_type==$bank ? $q->cash_admin_cost:0)
                    + ($q->cat_storage=='bank' && $q->storage_type==$bank ? $q->cash_client_refund:0)
                    + ($q->cat_storage=='bank' && $q->storage_type==$bank ? $q->cash_process_cost:0)
                    + ($q->cat_storage=='bank' && $q->storage_type==$bank ? $q->additional_budget:0)
                    + (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other==null || $q->deposit_other=='') && $q->cat_storage=='bank' && $q->storage_type==$bank? $q->cost_other:0)
                    + (($q->cost_other!=null && $q->cost_other!='') && ($q->deposit_other!=null && $q->deposit_other!='') && $q->cat_storage=='cash' && $q->storage_type==$bank ? $q->cost_other:0)
                );
    }

    public function store(Request $request){
           Financing::create($request->all());
           $response['status'] = 'Success';
           $response['code'] = 200;
           $response['data'] = $request->all();
           return response()->json($response);
    }

    public function update(Request $request, $id) {
      $trans_type = $request->trans_type;

      $update_field = ($trans_type == 'process_cost' ? 
      						'cash_process_cost' : 
      						($trans_type =='admin_budget_return' ? 
  							   'cash_admin_budget_return' : 
  							   ($trans_type == 'process_budget_return' ? 
  							   		'cash_client_process_budget_return' : 
  							   		($trans_type == 'admin_add_budget' || $trans_type == 'process_add_budget' ? 
  							   			'additional_budget' : 
  							   			'cash_admin_cost'
  							   		)
  							   	)
      						)
      					);

      $finance = Financing::where('id', $request->borrowed_id)->first();
      $timestamp = strtotime($finance->created_at);
      $oldMonth = date('m', $timestamp);
      $curMonth = date('m');
      
      if($update_field == 'cash_admin_budget_return'){
        	$nAmount = Financing::select('cash_admin_cost')
        					->where('id', $request->borrowed_id)
        					->first()->cash_admin_cost;
      }else{
        	$nAmount = Financing::select('cash_process_cost')
        					->where('id', $request->borrowed_id)
        					->first()->cash_process_cost;
      }

      if($trans_type != 'admin_add_budget' && $trans_type != 'process_add_budget'){
        	$nAmount = $nAmount + 
        			($finance->additional_budget != null && $finance->additional_budget != '' ? 
        				$financing->additional_budget : 
        				0 
        			);
      }

      if($nAmount<$request->amount && ($trans_type != 'admin_add_budget' && $trans_type != 'process_add_budget')){
        return json_encode([
            'success' => 'Failed',
            'code' => 404,
            'message' => 'Amount entered is higher than the amount borrowed'
        ]);
      }else{
         Financing::where('id', intval($request->borrowed_id))->update([$update_field=>$request->amount]);


          if($oldMonth!=$curMonth){
        
            $this->fixInitial($finance->branch_id,1);
          }
  
         return json_encode([
             'success' => 'Success',
             'code'	   => 200,
             'message' => 'Data has been saved!'
         ]);
      }
	}

    public function getBorrowed($trans, $branch_id){
      $trans_type = (($trans == 'process_budget_return' || $trans=='process_add_budget') ? 'process' : 'admin');

      if($trans_type == 'admin'){

            return Financing::where('cat_type',$trans_type)
            		->whereRaw('(cash_admin_cost != "")')
            		->where('cost_type',1)
            		->where('cash_admin_budget_return',NULL)
            		->where('branch_id',$branch_id)
            		->when($trans=='admin_add_budget', function ($query)  {
		                return $query->where('additional_budget',NULL);
		            })
            		->orderBy('id', 'desc')
            		->get();

      }
      else{

          return Financing::where('cat_type',$trans_type)
          			->whereRaw('(cash_process_cost != "")')
          			->where('cost_type',1)
          			->where('cash_client_process_budget_return',NULL)
          			->where('branch_id',$branch_id)
          			->when($trans == 'process_add_budget', function ($query)  {
		                return $query->where('additional_budget',NULL);
		            })
          			->orderBy('id', 'desc')
          			->get();

      }
    }

    public function getRequestingUsers(){
    	$ids = RoleUser::where('role_id',4)->pluck('user_id');

    	$users = User::whereIn('id',$ids)->get();

    	$response['status'] = 'Success';
		$response['data'] = $users;
		$response['code'] = 200;

		return Response::json($response);
    }

}

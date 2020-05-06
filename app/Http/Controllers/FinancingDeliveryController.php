<?php

namespace App\Http\Controllers;

use App\FinancingDelivery;

use App\RoleUser;

use App\User;

use Response;

use Illuminate\Http\Request;

use Carbon\Carbon;

use DB, Auth, Validator;

class FinancingDeliveryController extends Controller
{
    
    public function show($date) {
    	$date = str_replace("-", "/", $date);
    	$dateSelector = Carbon::parse($date.'/01')->toDateTimeString();
    	$now = Carbon::now();

    	$checkInitial = DB::table('financing_delivery')->where('cat_type','initial')
    						->whereRaw('MONTH(created_at) = MONTH("'.$dateSelector.'")')
    						->where('deleted_at',null)
    						->orderBy('created_at', 'DESC')->count();

        $checkData = DB::table('financing_delivery')->count();
          
        $month = Carbon::parse($dateSelector);

		if(($checkInitial==0 && $checkData>0) && $now->month==$month->month){
			$this->fixInitial(0);
		}

    	$query = DB::table('financing_delivery as f')
    				->select(array('f.*'))
    				->whereRaw('MONTH(created_at) = MONTH("'.$dateSelector.'")')
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


	public function fixInitial($dd){
     
      $now = Carbon::now();
      if($dd==0){
        $initial = DB::table('financing_delivery')->where('cat_type','initial')->orderBy('created_at', 'desc')->first();
      }else{
        $initial = DB::table('financing_delivery')->where('cat_type','initial')->orderBy('created_at', 'desc')->skip(1)->first();
      }
   
      if($initial){
        
        $month = Carbon::parse($initial->created_at);
        
        $cash = $initial->cash_balance;
        $ch = $initial->ch_balance;

        for($i=$month->month;$i<$now->month;$i++){

          $query = DB::table('financing_delivery')->whereRaw('MONTH(created_at) = "'.$i.'"')->where('cat_type','!=','initial')->orderBy('created_at', 'desc')->where('deleted_at',null)->get();
            if(count($query)>0){
              foreach($query as $q){
                $cash = (
                			$cash+
                            $q->cash_balance
                            + $q->purchasing_budget_return
                            + $q->delivery_budget_return
                            + $q->other_received
                          )
                        - 
                        (
                        	
                            + $q->other_cost
                            + $q->delivery_budget
                            + $q->purchasing_budget

                        );

                $ch = ($ch + $q->chmoney_paid) - ($q->chmoney_used);

              }
            }

            $ndate1 = explode(' ',$now);
            $ndate2 = explode('-',$ndate1[0]);
            $ndate3 = Carbon::parse($ndate2[0].'/'.($i+1).'/01')->toDateTimeString();
            //dd($cash);
            
            if($dd==0){
              FinancingDelivery::create([
                'cat_type'=>'initial',
                'cash_balance'=>$cash,
                'ch_balance'=>$ch,
                'created_at'=>$ndate3
              ]);
            }else{
              $initial2 = DB::table('financing_delivery')->where('cat_type','initial')->orderBy('created_at', 'desc')->first();
              FinancingDelivery::where('id',$initial2->id)->update(['cash_balance'=>$cash,'ch_balance'=>$ch]);
            }

            $initial = DB::table('financing_delivery')->where('cat_type','initial')->orderBy('created_at', 'desc')->first();
            $cash = $initial->cash_balance;
            $ch = $initial->ch_balance;
             
        }
      }
    }
    public function store(Request $request){
           FinancingDelivery::create($request->all());
           $response['status'] = 'Success';
           $response['code'] = 200;
           $response['data'] = $request->all();
           return response()->json($response);
    }

    public function addPurchasingBudget(Request $request){
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required',
            'purchasing_budget' => 'required',
            'rider' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
          $trans_desc = $request->rider.' budget for purchase. Order #'.$request->order_ids;
          $log_data['user_sn'] = Auth::user()->id;
          $log_data['trans_desc'] = $trans_desc;
          $log_data['cat_type'] = 'purchasing';
          $log_data['purchasing_budget'] = $request->purchasing_budget;
          FinancingDelivery::insert($log_data);

           $response['status'] = 'Success';
           $response['code'] = 200;
         }
           return response()->json($response);
    }

    public function update(Request $request, $id) {
      $trans_type = $request->trans_type;
      $update_field = $trans_type;

      $finance = FinancingDelivery::where('id', $request->finance_id)->first();
      $timestamp = strtotime($finance->created_at);
      $oldMonth = date('m', $timestamp);
      $curMonth = date('m');
      
      if($update_field == 'purchasing_budget_return'){
        	$nAmount = FinancingDelivery::select('purchasing_budget')
        					->where('id', $request->finance_id)
        					->first()->purchasing_budget;
      }else{
        	$nAmount = FinancingDelivery::select('delivery_budget')
        					->where('id', $request->finance_id)
        					->first()->delivery_budget;
      }


      if($nAmount<$request->amount && $update_field == 'purchasing_budget_return'){
        return json_encode([
            'success' => 'Failed',
            'code' => 404,
            'message' => 'Amount entered is higher than the purchasing amount'
        ]);
      }else if($nAmount>$request->amount && $update_field == 'delivery_budget_return'){
        return json_encode([
            'success' => 'Failed',
            'code' => 404,
            'message' => 'Amount entered is lower than the delivery amount'
        ]);
      }else{
        if($update_field == 'add_purchasing_budget'){
          $f = FinancingDelivery::where('id', intval($request->finance_id))->first();
          $remarks = ($request->remarks != '' ? '"'.$request->remarks.'"' : '');
          $f->trans_desc = $f->trans_desc.'<br>&bull; '.$request->req_user.' additional purchasing budget : '.$request->amount.'.'.$remarks;
          $f->purchasing_budget = $f->purchasing_budget + $request->amount;
          $f->save();
        }
        else{
          FinancingDelivery::where('id', intval($request->finance_id))->update([$update_field=>$request->amount]);
        }


          if($oldMonth!=$curMonth){
        
            $this->fixInitial(1);
          }
  
         return json_encode([
             'success' => 'Success',
             'code'	   => 200,
             'message' => 'Data has been saved!'
         ]);
      }
	}

    public function deleteRow($id){
      $f = FinancingDelivery::where('id', intval($id))->first();
      if($f){
        $timestamp = strtotime($f->created_at);
        $oldMonth = date('m', $timestamp);
        $curMonth = date('m');
        $f->delete();

        if($oldMonth!=$curMonth){
            $this->fixInitial(1);
        }
      } 
      return json_encode([
         'success' => 'Success',
         'code'    => 200,
         'message' => 'Data has been deleted!'
     ]); 
    }

    public function updateRow(Request $request ,$id){
      $f = FinancingDelivery::where('id', intval($request->id))->first();
          $f->trans_desc = $request->trans_desc;
          $f->purchasing_budget = $request->purchasing_budget;
          $f->purchasing_budget_return = $request->purchasing_budget_return;
          $f->delivery_budget = $request->delivery_budget;
          $f->delivery_budget_return = $request->delivery_budget_return;
          $f->other_cost = $request->other_cost;
          $f->other_received = $request->other_received;
          $f->chmoney_used = $request->chmoney_used;
          $f->chmoney_paid = $request->chmoney_paid;
          $f->save();
          return json_encode([
             'success' => 'Success',
             'code'    => 200,
             'message' => 'Row has been updated!'
         ]);
    }


    public function getReturnList($trans){

      if($trans == 'purchasing_budget_return' || $trans == 'add_purchasing_budget'){

            return FinancingDelivery::where('cat_type','purchasing')
            		->whereRaw('(purchasing_budget != "")')
            		->where('purchasing_budget_return',NULL)
            		->orderBy('id', 'desc')
            		->get();

      }
      else{

          return FinancingDelivery::where('cat_type','delivery')
                ->whereRaw('(delivery_budget != "")')
                ->where('delivery_budget_return',NULL)
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

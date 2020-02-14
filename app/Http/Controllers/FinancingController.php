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

    	$query = DB::table('financing as f')
    				->select(array('f.*'))
    				->whereRaw('MONTH(created_at) = MONTH("'.$dateSelector.'")')
    				// ->where('branch_id',$request->input('branch'))
    				->where('deleted_at',null)->orderBy('created_at', 'DESC')
    				->paginate(2000);
    	$ctr = 0;			
    	foreach($query as $q){
    		$q->index = $ctr;
    		$ctr++;
    	}


		$response['status'] = 'Success';
		$response['data'] = $query;
		$response['code'] = 200;

		return Response::json($response);
	}

}

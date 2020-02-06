<?php

namespace App\Http\Controllers;

use App\Breakdown;

use App\Service;

use App\ServiceBranchCost;

use App\ServiceProfileCost;

use Response, Validator;

use Illuminate\Http\Request;

class BreakdownController extends Controller
{
    
	public function store(Request $request) {
		$validator = Validator::make($request->all(), [ 
            'type' => 'required',
            'service_id' => 'required',
            'branch_id' => 'required',
            'service_profile_id' => 'required',
            'details' => 'array'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	Breakdown::where('type', $request->type)
        		->where('service_id', $request->service_id)
        		->where('branch_id', $request->branch_id)
        		->where('service_profile_id', $request->service_profile_id)
        		->delete();

        	$value = 0;

        	foreach($request->details as $detail) {
        		$value += $detail['amount'];

        		Breakdown::create([
        			'type' => $request->type,
        			'description' => $detail['description'],
        			'amount' => $detail['amount'],
        			'service_id' => $request->service_id,
        			'branch_id' => $request->branch_id,
        			'service_profile_id' => $request->service_profile_id
        		]);
        	}

        	if( $request->service_profile_id == 0 ) { // Regular Rate
        		if( $request->branch_id == 1 ) { // Manila
        			Service::find($request->service_id)->update([$request->type => $value]);
        		} else {
        			ServiceBranchCost::where('service_id', $request->service_id)
        				->where('branch_id', $request->branch_id)
        				->update([$request->type => $value]);
        		}
        	} else {
        		ServiceProfileCost::where('service_id', $request->service_id)
        			->where('profile_id', $request->service_profile_id)
        			->where('branch_id', $request->branch_id)
        			->update([$request->type => $value]);
        	}

        	$response['status'] = 'Success';
			$response['code'] = 200;
        }

        return Response::json($response);
	}

}

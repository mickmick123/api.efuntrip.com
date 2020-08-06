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
            $sum = Breakdown::where('type', $request->type)
                ->where('service_id', $request->service_id)
                ->where('branch_id', $request->branch_id)
                ->where('service_profile_id', $request->service_profile_id)
                ->sum('amount');

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

                    if($request->type != 'charge'){
                        $this->updatedRelated($request, $value, $sum);           
                    }

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

    private function updatedRelated($request, $value, $sum) {
        $breakTypeInc = $request->type.' increase';
        $breakTypeDec = $request->type.' decrease';
        $breakIfExist = Breakdown::where('service_id',$request->service_id)
                            ->where('branch_id', $request->branch_id)
                            ->where('service_profile_id',$request->service_profile_id)
                            ->where('type','charge')
                            ->where(function ($q) use($breakTypeInc, $breakTypeDec) {
                                $q->orwhere('description', $breakTypeInc)
                                  ->orwhere('description', $breakTypeDec);
                            })->first();
        $amount = $value - $sum;
        if($breakIfExist){
            $amt = $breakIfExist->amount + ($amount*-1);
            $breakIfExist->amount = $amt;
            $breakIfExist->description = $breakTypeInc;
            if($amt > 0){
                $breakIfExist->description = $breakTypeDec;
            }
            $breakIfExist->save();
        }   
        else{
            Breakdown::create([
                'type' => 'charge',
                'description' => $breakTypeInc,
                'amount' => $amount*-1,
                'service_id' => $request->service_id,
                'branch_id' => $request->branch_id,
                'service_profile_id' => $request->service_profile_id
            ]);
        }    
        $service = Service::findorfail($request->service_id);
        $chrg = $service->charge + ($amount * -1);
        $service->charge += ($amount * -1);
        if($chrg < 0){
            $service->charge = 0;  
        }
        $service->save(); 


        //update other service profiles
        $profiles = ServiceProfileCost::where('service_id', $request->service_id)
                    ->where('branch_id', 1)
                    ->get(); 
        // \Log::info($profiles);

        foreach($profiles as $p){
            // if($p->cost > 0 || $p->charge > 0 || $p->tip > 0){
                $sp = ServiceProfileCost::findorfail($p->id);
                // $spcost = $sp->cost + $amount;
                if($request->type == 'cost'){
                    // if($spcost > 0){
                        $sp->cost = $value;
                    // }
                    // else{
                    //     $sp->cost = 0;
                    // }
                } 

                // $sptip = $sp->tip + $amount;
                if($request->type == 'tip'){
                    // if($sptip > 0){
                         $sp->tip = $value;
                    // }
                    // else{
                    //     $sp->tip = 0;
                    // }
                } 

                $spcharge = $sp->charge + ($amount * -1);
                if($spcharge > 0 && $sp->charge > 0){
                    $sp->charge = $spcharge;
                }
                else{
                    $sp->charge = 0;
                }

                $sp->save();
            // }
        }
    }


    public function updatePrice(Request $request) {
        $validator = Validator::make($request->all(), [ 
            'amount' => 'required',
            'type' => 'required',
            'service_id' => 'required',
            'branch_id' => 'required',
            'service_profile_id' => 'required',
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
            $value = $request->amount;

            // Breakdown::where('type', $request->type)
            //     ->where('service_id', $request->service_id)
            //     ->where('branch_id', $request->branch_id)
            //     ->where('service_profile_id', $request->service_profile_id)
            //     ->delete();

            // foreach($request->details as $detail) {
            //     $value += $detail['amount'];

            //     Breakdown::create([
            //         'type' => $request->type,
            //         'description' => $detail['description'],
            //         'amount' => $detail['amount'],
            //         'service_id' => $request->service_id,
            //         'branch_id' => $request->branch_id,
            //         'service_profile_id' => $request->service_profile_id
            //     ]);
            // }

            if( $request->service_profile_id == 0 ) { // Regular Rate
                if( $request->branch_id == 1 ) { // Manila
                    $sum = $this->getOldValue($request->service_id, $request->type);
                    Service::find($request->service_id)->update([$request->type => $value]);

                    if($request->type != 'charge' && $request->type != 'com_client' && $request->type != 'com_agent'){
                        $this->updatedRelated($request, $value, $sum);           
                    }

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

    private function getOldValue($id, $type){
        $serv = Service::where('id', $id)->first();
        $sum = 0;
        if($type == 'cost'){
            $sum = $serv->cost;
        }
        else if($type == 'charge'){
            $sum = $serv->charge;
        }
        else if($type == 'tip'){
            $sum = $serv->tip;
        }
        else if($type == 'com_client'){
            $sum = $serv->com_client;
        }
        else if($type == 'com_agent'){
            $sum = $serv->com_agent;
        }
        return $sum;
    }

}

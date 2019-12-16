<?php

namespace App\Http\Controllers;

use App\Service;

use App\ServiceBranchCost;

use DB, Response, Validator;

use Illuminate\Http\Request;

class ServiceController extends Controller
{
    
	public function manageServices() {
		$parents = Service::where('parent_id', 0)->where('is_active', 1)->orderBy('detail')
			->select(array('id', 'parent_id', 'detail', DB::raw('SUM(cost + charge + tip + com_agent + com_client) as total_service_charge')))
			->groupBy('id')
			->get();

		$services = [];
		foreach($parents as $parent) {
            $services[] = $parent;

            $children = Service::where('parent_id', $parent->id)->where('is_active', 1)->orderBy('detail')
				->select(array('id', 'parent_id', 'detail', DB::raw('SUM(cost + charge + tip + com_agent + com_client) as total_service_charge')))
				->groupBy('id')
				->get();

			foreach($children as $child) {
				$services[] = $child;
			}
		}

		$response['status'] = 'Success';
		$response['data'] = [
		    'services' => $services
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function store(Request $request) {
		$validator = Validator::make($request->all(), [ 
            'type' => 'required',
            'service_name' => 'required|unique:services,detail',
            'parent_id' => 'required_if:type,child',
            'pricing' => 'required_if:type,child|array'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	if( $request->type == 'parent' ) {
        		Service::create([
        			'parent_id' => 0,
        			'detail' => $request->service_name,
        			'detail_cn' => ($request->service_name_chinese) ? $request->service_name_chinese : null,
        			'description' => ($request->description) ? $request->description : null,
        			'description_cn' => ($request->description_chinese) ? $request->description_chinese : null
        		]);
        	} elseif( $request->type == 'child' ) {
        		$service = Service::create([
        			'parent_id' => $request->parent_id,
        			'detail' => $request->service_name,
        			'detail_cn' => ($request->service_name_chinese) ? $request->service_name_chinese : null,
        			'description' => ($request->description) ? $request->description : null,
        			'description_cn' => ($request->description_chinese) ? $request->description_chinese : null
        		]);

        		foreach($request->pricing as $pricing) {
        			if( $pricing['branch_id'] == 1 ) {
        				$service->update([
        					'cost' => $pricing['cost'],
        					'charge' => $pricing['charge'],
        					'tip' => $pricing['tip'],
        					'com_agent' => $pricing['com_agent'],
        					'com_client' => $pricing['com_client']
        				]);
        			} else {
        				ServiceBranchCost::create([
        					'service_id' => $service->id,
        					'branch_id' => $pricing['branch_id'],
        					'cost' => $pricing['cost'],
        					'charge' => $pricing['charge'],
        					'tip' => $pricing['tip'],
        					'com_agent' => $pricing['com_agent'],
        					'com_client' => $pricing['com_client']
        				]);
        			}
        		}
        	}

        	$response['status'] = 'Success';
			$response['code'] = 200;
        }

        return Response::json($response);
	}

	public function show($id) {
		$service = Service::with('serviceBranchCosts')->find($id);

		if( $service ) {
			$response['status'] = 'Success';
			$response['data'] = [
			    'service' => $service
			];
			$response['code'] = 200;
		} else {
			$response['status'] = 'Failed';
        	$response['errors'] = 'No query results.';
			$response['code'] = 404;
		}

		return Response::json($response);
	} 

	public function update(Request $request, $id) {
		$validator = Validator::make($request->all(), [ 
			'type' => 'required',
            'service_name' => 'required|unique:services,detail,'.$id,
            'parent_id' => 'required_if:type,child',
            'pricing' => 'required_if:type,child|array'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	$service = Service::find($id);

        	if( $service ) {
        		if( $request->type == 'parent' ) {
        			$service->update([
	        			'detail' => $request->service_name,
	        			'detail_cn' => ($request->service_name_chinese) ? $request->service_name_chinese : null,
	        			'description' => ($request->description) ? $request->description : null,
	        			'description_cn' => ($request->description_chinese) ? $request->description_chinese : null
	        		]);
        		} elseif( $request->type == 'child' ) {
        			$service->update([
	        			'parent_id' => $request->parent_id,
	        			'detail' => $request->service_name,
	        			'detail_cn' => ($request->service_name_chinese) ? $request->service_name_chinese : null,
	        			'description' => ($request->description) ? $request->description : null,
	        			'description_cn' => ($request->description_chinese) ? $request->description_chinese : null
	        		]);

	        		foreach($request->pricing as $pricing) {
	        			if( $pricing['branch_id'] == 1 ) {
	        				$service->update([
	        					'cost' => $pricing['cost'],
	        					'charge' => $pricing['charge'],
	        					'tip' => $pricing['tip'],
	        					'com_agent' => $pricing['com_agent'],
	        					'com_client' => $pricing['com_client']
	        				]);
	        			} else {
		        			ServiceBranchCost::updateOrCreate(
		        				['service_id' => $service->id, 'branch_id' => $pricing['branch_id']],
		        				[
		        					'cost' => $pricing['cost'],
		        					'charge' => $pricing['charge'],
		        					'tip' => $pricing['tip'],
		        					'com_agent' => $pricing['com_agent'],
		        					'com_client' => $pricing['com_client']
		        				]
		        			);
	        			}
	        		}
        		}

        		$response['status'] = 'Success';
        		$response['code'] = 200;
        	} else {
        		$response['status'] = 'Failed';
        		$response['errors'] = 'No query results.';
				$response['code'] = 404;
        	}
        }

         return Response::json($response);
	}

	public function destroy($id) {
		$service = Service::find($id);

		if( $service ) {
			$service->update(['is_active' => 0]);

			$response['status'] = 'Success';
        	$response['code'] = 200;
		} else {
			$response['status'] = 'Failed';
        	$response['errors'] = 'No query results.';
			$response['code'] = 404;
		}

		return Response::json($response);
	}

}

<?php

namespace App\Http\Controllers;

use App\Service;

use App\ServiceProfile;

use App\ServiceProfileCost;

use App\Branch;

use App\ServiceBranchCost;

use App\Breakdown;

use App\Http\Controllers\ServiceBranchCostController;

use App\Http\Controllers\ServiceProfileCostController;

use DB, Response, Validator;

use Illuminate\Http\Request;

class ServiceController extends Controller
{
	public function manageServices() {
		$parents = Service::where('parent_id', 0)->where('is_active', 1)->orderBy('detail')
			->select(array('id', 'parent_id', 'detail', 'cost', 'charge', 'tip', DB::raw('SUM(cost + charge + tip + com_agent + com_client) as total_service_charge')))
			->groupBy('id')
			->get();

		$services = [];
		foreach($parents as $parent) {
            $services[] = $parent;

            $children = Service::where('parent_id', $parent->id)->where('is_active', 1)->orderBy('detail')
				->select(array('id', 'parent_id', 'detail', 'cost', 'charge', 'tip', DB::raw('SUM(cost + charge + tip + com_agent + com_client) as total_service_charge')))
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

	public function manageParentServices(){
		$parentServices = Service::where('parent_id', 0)->where('is_active', 1)->orderBy('detail')
			->select(array('id', 'parent_id', 'detail', DB::raw('SUM(cost + charge + tip + com_agent + com_client) as total_service_charge')))
			->groupBy('id')
			->get();

		$response['status'] = 'Success';
		$response['data'] = [
		    'parent_services' => $parentServices
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function store(Request $request) {
		$validator = Validator::make($request->all(), [
            'type' => 'required',
            'service_name' => 'required|unique:services,detail',
            'parent_id' => 'required_if:type,child',
            'mode' => 'required_if:type,child',
            'breakdowns' => 'required_if:type,child|array'
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
        	if( $request->type == 'parent' ) {
        		$service = Service::create([
        			'parent_id' => 0,
        			'detail' => $request->service_name,
        			'detail_cn' => ($request->service_name_chinese) ? $request->service_name_chinese : null,
        			'description' => ($request->description) ? $request->description : null,
        			'description_cn' => ($request->description_chinese) ? $request->description_chinese : null
        		]);

        		ServiceBranchCostController::createData([$service->id]);
        		ServiceProfileCostController::createData([$service->id]);
        	} elseif( $request->type == 'child' ) {
        		$service = Service::create([
        			'parent_id' => $request->parent_id,
        			'detail' => $request->service_name,
        			'detail_cn' => ($request->service_name_chinese) ? $request->service_name_chinese : null,
        			'description' => ($request->description) ? $request->description : null,
        			'description_cn' => ($request->description_chinese) ? $request->description_chinese : null,
        			'mode' => $request->mode,
        			'months_required' => $request->months_required,
        			'min_months' => $request->minimum_months,
        			'max_months' => $request->maximum_months,
        			'form_id' => $request->form_id
        		]);

        		foreach( $request->breakdowns as $breakdown ) {
        			foreach( $breakdown['branches'] as $branch ) {
        				// Breadowns::cost
        				foreach( $branch['costs'] as $cost ) {
        					Breakdown::create([
        						'type' => 'cost',
        						'description' => $cost['description'],
        						'amount' => $cost['amount'],
        						'service_id' => $service->id,
        						'branch_id' => $branch['id'],
        						'service_profile_id' => $breakdown['service_profile_id']
        					]);
        				}

        				// Breadowns::charge
        				foreach( $branch['charges'] as $charge ) {
        					Breakdown::create([
        						'type' => 'charge',
        						'description' => $charge['description'],
        						'amount' => $charge['amount'],
        						'service_id' => $service->id,
        						'branch_id' => $branch['id'],
        						'service_profile_id' => $breakdown['service_profile_id']
        					]);
        				}

        				// Breadowns::tip
        				foreach( $branch['tips'] as $tip ) {
        					Breakdown::create([
        						'type' => 'tip',
        						'description' => $tip['description'],
        						'amount' => $tip['amount'],
        						'service_id' => $service->id,
        						'branch_id' => $branch['id'],
        						'service_profile_id' => $breakdown['service_profile_id']
        					]);
        				}

        				// Regular
	        			if( $breakdown['service_profile_id'] == 0 ) {
	        				// Manila
	        				if( $branch['id'] == 1 ) {
	        					$service->update([
		        					'cost' => $branch['totalCosts'],
		        					'charge' => $branch['totalCharges'],
		        					'tip' => $branch['totalTips'],
		        					'com_agent' => $branch['com_agent'],
		        					'com_client' => $branch['com_client']
		        				]);
	        				} else {
	        					ServiceBranchCost::create([
	        						'service_id' => $service->id,
	        						'branch_id' => $branch['id'],
	        						'cost' => $branch['totalCosts'],
		        					'charge' => $branch['totalCharges'],
		        					'tip' => $branch['totalTips'],
		        					'com_agent' => $branch['com_agent'],
		        					'com_client' => $branch['com_client']
	        					]);
	        				}
	        			} else {
	        				ServiceProfileCost::create([
	        					'service_id' => $service->id,
	        					'profile_id' => $breakdown['service_profile_id'],
	        					'branch_id' => $branch['id'],
	        					'cost' => $branch['totalCosts'],
		        				'charge' => $branch['totalCharges'],
		        				'tip' => $branch['totalTips'],
		        				'com_agent' => $branch['com_agent'],
		        				'com_client' => $branch['com_client']
	        				]);
	        			}
        			}
        		}
        	}

        	$response['status'] = 'Success';
			$response['code'] = 200;
        }

        return Response::json($response);
	}

	public function show($id) {
		$service = Service::with('serviceBranchCosts', 'serviceProfileCosts')->find($id);

		if( $service ) {
			$service['breakdowns'] = DB::table('breakdowns')->where('service_id', $id)->get();
			
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
            'service_profile' => 'required_if:type,child',
            'parent_id' => 'required_if:type,child',
            'mode' => 'required_if:type,child',
            'costs' => 'required_if:type,child|array'
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
	        			'description_cn' => ($request->description_chinese) ? $request->description_chinese : null,
	        			'mode' => $request->mode,
	        			'months_required' => $request->months_required,
	        			'min_months' => $request->minimum_months,
	        			'max_months' => $request->maximum_months,
	        			'form_id' => $request->form_id
	        		]);

        			if( $request->service_profile == 0 ) {
		        		foreach($request->costs as $cost) {
		        			if( $cost['branch_id'] == 1 ) {
		        				$service->update([
		        					'cost' => $cost['cost'],
		        					'charge' => $cost['charge'],
		        					'tip' => $cost['tip'],
		        					'com_agent' => $cost['com_agent'],
		        					'com_client' => $cost['com_client']
		        				]);
		        			}
		        		}

		        		ServiceBranchCostController::updateData($service->id, $request->costs);
	        		} else {
	        			ServiceProfileCostController::updateData($service->id, $request->costs, $request->service_profile);
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

	public function serviceProfilesDetails($id) {
		$serviceProfilesDetails = ServiceProfile::select(['id', 'name'])
			->where('is_active', 1)->orderBy('name')->get();
		$serviceProfilesDetails->prepend(collect(['id' => 0, 'name' => 'Regular']));

		$branches = Branch::select(['id', 'name'])->where('name', '<>', 'Both')->get();

		foreach( $serviceProfilesDetails as $serviceProfilesDetail ) {
			$data = [];

			foreach( $branches as $branch ) {
				if( $serviceProfilesDetail['id'] == 0 ) { // Regular
					if( $branch->id == 1 ) { // Manila
						$service = Service::select(['cost', 'charge', 'tip', 'com_agent', 'com_client'])
							->find($id);
					} else {
						$service = ServiceBranchCost::select(['cost', 'charge', 'tip', 'com_agent', 'com_client'])
							->where('service_id', $id)->where('branch_id', $branch->id)->first();
					}
				} else {
					$service = ServiceProfileCost::select(['cost', 'charge', 'tip', 'com_agent', 'com_client'])
						->where('service_id', $id)->where('profile_id', $serviceProfilesDetail['id'])
						->where('branch_id', $branch->id)->first();
				}

				$costBreakdown = Breakdown::select(['description', 'amount'])
					->where('type', 'cost')->where('service_id', $id)
					->where('branch_id', $branch->id)->where('service_profile_id', $serviceProfilesDetail['id'])
					->get();

				$chargeBreakdown = Breakdown::select(['description', 'amount'])
					->where('type', 'charge')->where('service_id', $id)
					->where('branch_id', $branch->id)->where('service_profile_id', $serviceProfilesDetail['id'])
					->get();

				$tipBreakdown = Breakdown::select(['description', 'amount'])
					->where('type', 'tip')->where('service_id', $id)
					->where('branch_id', $branch->id)->where('service_profile_id', $serviceProfilesDetail['id'])
					->get();

				$data[] = [
					'id' => $branch->id,
					'name' => $branch->name,
					'cost' => ($service) ? $service->cost : 0,
					'cost_breakdown' => $costBreakdown,
					'charge' =>  ($service) ? $service->charge : 0,
					'charge_breakdown' => $chargeBreakdown,
					'tip' =>  ($service) ? $service->tip : 0,
					'tip_breakdown' => $tipBreakdown,
					'com_agent' =>  ($service) ? $service->com_agent : 0,
					'com_client' =>  ($service) ? $service->com_client : 0
				];
			}

			$serviceProfilesDetail['branches'] = $data;
		}

		$response['status'] = 'Success';
		$response['data'] = [
			'serviceProfilesDetails' => $serviceProfilesDetails
		];
		$response['code'] = 200;

		return Response::json($response);
	}

}

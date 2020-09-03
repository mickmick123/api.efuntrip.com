<?php

namespace App\Http\Controllers;

use App\Service;

use App\ServiceProfile;

use App\ServiceProfileCost;

use App\User;
use App\Group;
use App\Branch;
use App\BranchUser;
use App\BranchGroup;

use App\ServiceBranchCost;

use App\Breakdown;

use App\Http\Controllers\ServiceBranchCostController;

use App\Http\Controllers\ServiceProfileCostController;

use Carbon\Carbon, DB, Response, Validator;

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

        		$branchIds  = DB::table('branches')->where('name', '<>', 'Manila')->pluck('id');
        		
        		foreach( $branchIds  as $branchId ) {
        			ServiceBranchCost::create([
        				'service_id' => $service->id,
        				'branch_id' => $branchId,
        				'cost' => 0, 
						'charge' => 0,
						'tip' => 0,
						'com_agent' => 0,
						'com_client' => 0
        			]);
        		}
        		
        		$profileIds = DB::table('service_profiles')->pluck('id');
        		$branchIds  = DB::table('branches')->pluck('id');

        		foreach( $profileIds as $profileId ) {
        			foreach( $branchIds as $branchId ) {
        				ServiceProfileCost::create([
        					'service_id' => $service->id,
        					'profile_id' => $profileId,
        					'branch_id' => $branchId,
        					'cost' => 0, 
							'charge' => 0,
							'tip' => 0,
							'com_agent' => 0,
							'com_client' => 0
        				]);
        			}
        		}
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
		$serviceProfilesDetails = ServiceProfile::select(['id', 'name', 'type'])
			->where('is_active', 1)->orderBy('name')->get();
		$serviceProfilesDetails->prepend(collect(['id' => 0, 'name' => 'Market Price', 'type' => 'default']));

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

	private function getServiceProfilesDetails($id, $serviceProfiles, $branchId) {
		$service = Service::select(['cost', 'charge', 'tip'])->findOrFail($id);

		$serviceProfiles->map(function($item) use($id, $service, $branchId) {
			$cost = null;
			$charge = null;
			$tip = null;
			$comAgent = null;
			$comClient = null;
			$total = 0;
			$active = 0;

			// Market Price
			if( $item['id'] == 0 ) {
				// Init
				$cost = 0;
				$charge = 0;
				$tip = 0;
				$total = 0;

				// Manila
				if( $branchId == 1 ) {
					$cost = $service->cost;
					$charge = $service->charge;
					$tip = $service->tip;
				} else {
					$serviceBranchCost = ServiceBranchCost::select(['cost', 'charge', 'tip'])
						->where('service_id', $id)
						->where('branch_id', $branchId)
						->first();

					if( $serviceBranchCost ) {
						$cost = $serviceBranchCost->cost;
						$charge = $serviceBranchCost->charge;
						$tip = $serviceBranchCost->tip;
					}
				}
				$total = $cost + $charge + $tip;
			} else {
				// Default Rates
				if( $item['type'] == 'default' ) {
					// Init
					$cost = 0;
					$charge = 0;
					$tip = 0;
					$total = 0;

					$select = ['cost', 'charge', 'tip'];
				}

				// Customized Rates
				else {
					// Init
					$cost = 0;
					$charge = 0;
					$tip = 0;
					$comAgent = 0;
					$comClient = 0;
					$total = 0;

					$select = ['active', 'cost', 'charge', 'tip', 'com_agent', 'com_client'];
				}

				$serviceProfileCost = ServiceProfileCost::select($select)
					->where('service_id', $id)
					->where('profile_id', $item['id'])
					->where('branch_id', $branchId)
					->first();

				if( $serviceProfileCost ) {
					$cost = $serviceProfileCost->cost;
					$charge = $serviceProfileCost->charge;
					$tip = $serviceProfileCost->tip;
					$active = $serviceProfileCost->active;
					$total = $cost + $charge + $tip;

					if( $serviceProfileCost->com_agent ) {
						$comAgent = $serviceProfileCost->com_agent;
						$total += $comAgent;
					}
					if( $serviceProfileCost->com_client ) {
						$comClient = $serviceProfileCost->com_client;
						$total += $comClient;
					}
				}
			}

			$item['active'] = $active;
			$item['total'] = !is_null($total) ? number_format($total, 2) : $total;
			$item['cost'] = !is_null($cost) ? number_format($cost, 2) : $cost;
			$item['cost_breakdown'] = Breakdown::select(['description', 'amount'])->where('type', 'cost')
				->where('service_id', $id)->where('branch_id', $branchId)
				->where('service_profile_id', $item['id'])->get();
			$item['charge'] = !is_null($charge) ? number_format($charge, 2) : $charge;
			$item['charge_breakdown'] = Breakdown::select(['description', 'amount'])->where('type', 'charge')
				->where('service_id', $id)->where('branch_id', $branchId)
				->where('service_profile_id', $item['id'])->get();
			$item['tip'] = !is_null($tip) ? number_format($tip, 2) : $tip;
			$item['tip_breakdown'] = Breakdown::select(['description', 'amount'])->where('type', 'tip')
				->where('service_id', $id)->where('branch_id', $branchId)
				->where('service_profile_id', $item['id'])->get();
			$item['comAgent'] = !is_null($comAgent) ? number_format($comAgent, 2) : $comAgent;
			$item['comClient'] = !is_null($comClient) ? number_format($comClient, 2) : $comClient;

			return $item;
		});

		return $serviceProfiles->toArray();
	}

	public function expandedDetails($id) {
		$branches = Branch::select(['id', 'name'])->get();

		$serviceProfiles = ServiceProfile::select(['id', 'name', 'type'])
			->where('is_active', 1)
			->get();

		$marketPrice = [
			'id' => 0, 
			'name' => 'Market Price', 
			'type' => 'default'
		];
		$serviceProfiles->prepend(collect($marketPrice));

		$default = $serviceProfiles->filter(function($item) {
			return $item['type'] == 'default';
		})->values();

		$customized = $serviceProfiles->filter(function($item) {
			return $item['type'] == 'customized';
		})->values();

		$defaultRates = [];
		$customizedRates = [];

		foreach( $branches as $branch ) {
			// Default Rates
			$defaultRates[] = [
				'branch' => $branch,
				'serviceProfiles' => $this->getServiceProfilesDetails(
					$id,
					$default,
					$branch->id
				)
			];

			// Customized Rates
			$customizedRates[] = [
				'branch' => $branch,
				'serviceProfiles' => $this->getServiceProfilesDetails(
					$id,
					$customized,
					$branch->id
				)
			];
		}

		$response['status'] = 'Success';
		$response['data'] = [
			'serviceId' => $id,
			'defaultRates' => $defaultRates,
			'customizedRates' => $customizedRates
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function getServiceRate($type, $id, $service_id) {
		$branch_id = 1;
		if($type == 'client'){
			$client = User::findOrFail($id);
			$level = $client->service_profile_id;
			$branch = BranchUser::where('user_id',$id)->first();
			if($branch){
				$branch_id = $branch->branch_id;
			}
		}
		else {
			$group = Group::findOrFail($id);
			$level = $group->service_profile_id;
			$branch = BranchGroup::where('group_id',$id)->first();
			if($branch){
				$branch_id = $branch->branch_id;
			}
		}

		$service = Service::findorfail($service_id);


		  $scharge = $service->charge;
	      $scost = $service->cost;
	      $stip = $service->tip;
	      $sclient = $service->com_client;
	      $sagent = $service->com_agent;

	      if($branch_id > 1){
	          $bcost = ServiceBranchCost::where('branch_id',$branch_id)->where('service_id',$service_id)->first();
	          $scost = $bcost->cost;
	          $stip = $bcost->tip;
	          $scharge = $bcost->charge; 
	          $sclient = $bcost->com_client;
	      	  $sagent = $bcost->com_agent;  
	      }
	      else{
	          $scost = $service->cost;
	          $stip = $service->tip;
	          $scharge = $service->charge;
	          $sclient = $service->com_client;
	      	  $sagent = $service->com_agent;  
	      }

	      //has profile id
	      if($level > 0 && $level != null){
	          $newcost = ServiceProfileCost::where('profile_id',$level)
	                        ->where('branch_id',$branch_id)
	                        ->where('service_id',$service_id)
	                        ->first();
	          if($newcost){
	              $scharge = $newcost->charge;
	              $scost = $newcost->cost;
	              $stip = $newcost->tip;
	              $sclient = $newcost->com_client;
	      	  	  $sagent = $newcost->com_agent; 
	          }
	      }

	      $scharge = ($scharge > 0 ? $scharge : $service->charge);
	      $scost = ($scost > 0 ? $scost : $service->cost);
	      $stip = ($stip > 0 ? $stip : $service->tip);
	      $sclient = ($sclient > 0 ? $sclient : $service->com_client);
	      $sagent = ($sagent > 0 ? $sagent : $service->com_agent);

		$response['status'] = 'Success';
		$response['data'] = [
			'cost' => $scharge,
			'charge' => $scost,
			'tip' => $stip,
			'com_client' => $sclient,
			'com_agent' => $sagent
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	// public function addNotesToService() {
	// 	$services = Service::where('is_active', 1)->where('parent_id', '!=', 0)->orderBy('id', 'ASC')->get();

	// 	foreach($services as $service) {
	// 		DB::table('service_procedures')->insert([
	// 			'service_id' => $service->id,
	// 			'name' => 'Note to Service',
	// 			'preposition' => 'to',
	// 			'action_id' => 17,
	// 			'category_id' => 26,
	// 			'is_required' => 0,
	// 			'is_suggested_count' => 0,
	// 			'created_at' => Carbon::now()->toDateString(),
	// 			'updated_at' => Carbon::now()->toDateString()
	// 		]);
	// 	}

	// 	$response['data'] = $services;

	// 	return Response::json($response);
	// }

}

<?php

namespace App\Http\Controllers;

use App\Branch;

use App\ServiceBranchCost;

use Illuminate\Http\Request;

class ServiceBranchCostController extends Controller
{
    
	public static function createData($serviceId, $costs = []) {
		$branches = Branch::where('name', '<>', 'Manila')->where('name', '<>', 'Both')->get();

		foreach($branches as $branch) {
			$branchId = $branch->id;
			$cost = 0;
			$charge = 0;
			$tip = 0;
			$comAgent = 0;
			$comClient = 0;

			foreach($costs as $c) {
				if( $c['branch_id'] == $branch->id ) {
					$branchId = $c['branch_id'];
					$cost = $c['cost'];
					$charge = $c['charge'];
					$tip = $c['tip'];
					$comAgent = $c['com_agent'];
					$comClient = $c['com_client'];
				}
			}

			ServiceBranchCost::updateOrCreate(
				['service_id' => $serviceId, 'branch_id' => $branchId],
				[
					'cost' => $cost, 
					'charge' => $charge,
					'tip' => $tip,
					'com_agent' => $comAgent,
					'com_client' => $comClient
				]
			);
		}
	}

	public static function updateData($serviceId, $costs) {
		foreach( $costs as $cost ) {
			if( $cost['branch_id'] != 1 ) {
				ServiceBranchCost::updateOrCreate(
					['service_id' => $serviceId, 'branch_id' => $cost['branch_id']],
					[
						'cost' => $cost['cost'],
		        		'charge' => $cost['charge'],
		       			'tip' => $cost['tip'],
		     			'com_agent' => $cost['com_agent'],
		      			'com_client' => $cost['com_client']
					]
				);
			}
		}
	}

}

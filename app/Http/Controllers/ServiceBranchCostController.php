<?php

namespace App\Http\Controllers;

use App\ServiceBranchCost;

use Illuminate\Http\Request;

class ServiceBranchCostController extends Controller
{

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

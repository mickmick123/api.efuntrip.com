<?php

namespace App\Http\Controllers;

use App\Branch;

use App\ServiceProfile;

use App\ServiceProfileCost;

use Illuminate\Http\Request;

class ServiceProfileCostController extends Controller
{
    
    public static function createData($serviceId, $costs = []) {
    	$serviceProfiles = ServiceProfile::where('is_active', 1)->get();
    	$branches = Branch::where('name', '<>', 'Both')->get();

    	foreach($serviceProfiles as $serviceProfile) {
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

    			ServiceProfileCost::updateOrCreate(
    				[
    					'service_id' => $serviceId, 
    					'profile_id' => $serviceProfile->id, 
    					'branch_id' => $branchId
    				],
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
    }

    public static function updateData($serviceId, $costs, $serviceProfileId) {
    	foreach( $costs as $cost ) {
    		ServiceProfileCost::updateOrCreate(
    			[
    				'service_id' => $serviceId,
    				'profile_id' => $serviceProfileId,
    				'branch_id' => $cost['branch_id']
    			],
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

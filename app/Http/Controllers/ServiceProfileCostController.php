<?php

namespace App\Http\Controllers;

use App\ServiceProfileCost;

use Illuminate\Http\Request;

class ServiceProfileCostController extends Controller
{
    
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

<?php

namespace App\Http\Controllers;

use DB, Response;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    
	public function clientsServices(Request $request) {
		$clientIds = $request->input("client_ids") ? $request->client_ids : [];
		$clientIds = explode("," , $clientIds);
		//dd($clientIds);

        $clientsServices = [];
        foreach($clientIds as $clientId) {
        	$client = DB::table('users')->select(array('id', 'first_name', 'last_name'))->where('id', $clientId)->first();

        	if( $client ) {
        		$services = DB::table('client_services as cs')
	                ->select(DB::raw('cs.id, date_format(cs.created_at, "%M %e, %Y") as date, cs.tracking, cs.detail, cs.cost, cs.charge, cs.tip, IFNULL(transactions.discount, 0) as discount'))
	                ->leftjoin(DB::raw('
	                    (
	                        Select 
	                            SUM(IF(ct.type = "Discount", ct.amount, 0)) as discount,
	                            ct.client_service_id

	                        from 
	                            client_transactions as ct

	                        where 
	                            ct.deleted_at is null

	                        group by 
	                            ct.client_service_id
	                    ) as transactions'),
	                    'transactions.client_service_id', '=', 'cs.id')
	                ->where('cs.client_id', $clientId)
	                ->orderBy('cs.id', 'desc')
	                ->get();

        		$clientsServices[] = [
	        		'id' => $client->id,
	        		'name' => $client->first_name . ' ' . $client->last_name,
	        		'services' => $services,
	        		
	        	];
        	}
        }

		$response['status'] = 'Success';
		$response['data'] = [
		    'clientsServices' => $clientsServices
		];
		$response['code'] = 200;
		return Response::json($response);
	}

}

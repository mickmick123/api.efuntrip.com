<?php

namespace App\Http\Controllers;

use App\Service;

use App\User;

use DB, Response;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    
	public function clientsServices(Request $request) {
		$clientIds = $request->input("client_ids") ? $request->client_ids : [];
		$clientIds = explode("," , $clientIds);

        $clientsServices = [];
        foreach($clientIds as $clientId) {
        	$client = DB::table('users')->select(array('id', 'first_name', 'last_name'))->where('id', $clientId)->first();

        	if( $client ) {
        		$services = DB::table('client_services as cs')
	                ->select(DB::raw('cs.id, date_format(cs.created_at, "%M %e, %Y") as date, cs.tracking, cs.detail, cs.cost, cs.charge, cs.tip, cs.active, IFNULL(transactions.discount, 0) as discount'))
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
	                ->where('cs.active', 1)
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

	public function reportServices(Request $request) {
		$clientServicesId = json_decode($request->client_services_id);

		if( is_array($clientServicesId) ) {
			// $clients = User::whereHas('clientServices', function($query) use($clientServicesId) {
			// 		$query->whereIn('id', $clientServicesId);
			// 	})
			// 	->with(['clientServices' => function($query) {
			// 		$query->select(['id', 'client_id']);
			// 	}])
			// 	->get(['id']);
				// ->map(function($items) {
				// 	$data['documents'] = 123; 
				// 	return $data;
				// });

				// ->with('clientServices.clientReports.clientReportDocuments')

			$services = Service::whereHas('clientServices', function($query) use($clientServicesId) {
				$query->whereIn('id', $clientServicesId);
			})
			->with(['serviceProcedures' => function($query) {
				$query->select(['id', 'name', 'service_id', 'step'])->orderBy('step');
			}])
			->with(['clientServices' => function($query1) use($clientServicesId) {
				$query1->select(['id', 'client_id', 'service_id', 'tracking'])
					->whereIn('id', $clientServicesId)
					->with(['client' => function($query2) {
						$query2->select(['id', 'first_name', 'last_name']);
					}]);
			}])
			->select(array('id', 'detail'))->get();

			$response['status'] = 'Success';
			$response['data'] = [
				// 'clients' => $clients,
		    	'services' => $services
			];
			$response['code'] = 200;
		} else {
			$response['status'] = 'Failed';
        	$response['errors'] = 'No query results.';
			$response['code'] = 404;
		}

		return Response::json($response);
	}

}

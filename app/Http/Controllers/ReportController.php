<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ClientController;

use App\ClientService;

use App\Document;

use App\GroupUser;

use App\Log;

use App\Report;

use App\ClientReportDocument;

use App\Service;

use App\ServiceProcedure;

use App\ServiceProcedureDocument;

use App\OnHandDocument;

use App\User;

use Auth, Carbon\Carbon, DB, Response, Validator;

use Illuminate\Http\Request;


class ReportController extends Controller
{

    public function index(Request $request, $perPage = 20) {
    	$search = $request->search;

    	$startDate = null;
    	$endDate = null;

    	if( $request->date != null && $request->date != 'null' ) {
    		$date = explode(',', $request->date);
    		$startDate = $date[0];
    		$endDate = $date[1];
    	}


    	$reports = Report::orderBy('id', 'desc')
    		->select(['id', 'processor_id', 'created_at'])
    		->whereHas('clientReports.clientService.client', function($query) use($search) {

          if( $search ) {
    				$query->where('id', $search)
    					->orWhere(function($q) use($search) {
    						$q->where('first_name', 'LIKE', '%'.$search.'%')
    							->orWhere('last_name', 'LIKE', '%'.$search.'%');
    					})
						->orWhere(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', "%".$search."%");
    			}

    		})
    		->with(['processor' => function($query) {
    			$query->select('id', 'first_name', 'last_name');
    		}])
    		->with([
    			'clientReports' => function($query) {
    				$query->select(['id', 'detail', 'client_service_id', 'report_id']);
    			},
    			'clientReports.clientService' => function($query) {
    				$query->select(['id', 'client_id', 'service_id']);
    			},
    			'clientReports.clientService.service' => function($query) {
    				$query->select(['id', 'detail']);
    			},
    			'clientReports.clientService.client' => function($query) use($search) {
    				$query = $query->select(['id', 'first_name', 'last_name']);

    				if( $search ) {
	    				$query->where('id', $search)
	    					->orWhere(function($q) use($search) {
	    						$q->where('first_name', 'LIKE', '%'.$search.'%')
	    							->orWhere('last_name', 'LIKE', '%'.$search.'%');
	    					})
	    					->orWhere(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', "%".$search."%");
	    			}
    			}
    		])
    		->where(function($query) use($startDate, $endDate) {
    			if( $startDate && $endDate ) {
    				$query->whereDate('created_at', '>=', $startDate)
    					->whereDate('created_at', '<=', $endDate);
    			}
    		})
    		->paginate($perPage);

    $response['status'] = 'Success';
		$response['data'] = [
		    'reports' => $reports
		];
		$response['code'] = 200;

		return Response::json($response);
    }


   public function reportsByService($clientServiceId) {

    $reports = Report::orderBy('id', 'desc')
      ->select(['id', 'processor_id', 'created_at'])
      ->whereHas('clientReports.clientService.client', function($query) use($clientServiceId) {
        if($clientServiceId){
          $query->where('client_service_id', $clientServiceId);
        }
      })
      ->with(['processor' => function($query) {
        $query->select('id', 'first_name', 'last_name');
      }])

      ->with([
        'clientReports' => function($query) {
          $query->select(['id', 'detail', 'client_service_id', 'report_id']);
        },
        'clientReports.clientService' => function($query) {
          $query->select(['id', 'client_id', 'service_id']);
        },
        'clientReports.clientService.client' => function($query) {
          $query = $query->select(['id', 'first_name', 'last_name']);
        }
      ])
      ->get();

      $response['status'] = 'Success';
      $response['data'] = $reports;
      $response['code'] = 200;

      return Response::json($response);

  }


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
	                ->where('cs.status', '<>', 'released')
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
			$services = Service::whereHas('clientServices', function($query) use($clientServicesId) {
				$query->whereIn('id', $clientServicesId);
			})
			->with([
				'serviceProcedures' => function($query) {
					$query->select(['id', 'name', 'service_id', 'step', 'action_id', 'category_id', 'is_required', 'required_service_procedure'])->orderBy('step');
				},
				'serviceProcedures.action' => function($query) {
					$query->select(['id', 'name']);
				},
				'serviceProcedures.category' => function($query) {
					$query->select(['id', 'name']);
				},
				'serviceProcedures.serviceProcedureDocuments' => function($query) {
					$query->select(['service_procedure_id', 'document_id', 'is_required']);
				},
				'serviceProcedures.serviceProcedureDocuments.document' => function($query) {
					$query->select(['id', 'title', 'is_unique', 'is_company_document']);
				}
			])
			->with(['clientServices' => function($query1) use($clientServicesId) {
				$query1->select(['id', 'client_id', 'group_id', 'service_id', 'tracking'])
					->whereIn('id', $clientServicesId)
					->with([
						'client' => function($query2) {
							$query2->select(['id', 'first_name', 'last_name']);
						},
						'clientReports' => function($query3) {
							$query3->select(['id', 'client_service_id', 'service_procedure_id']);
						},
						'clientReports.clientReportDocuments' => function($query4) {
							$query4->select(['id', 'client_report_id', 'document_id']);
						},
						'clientReports.clientReportDocuments.document' => function($query5) {
							$query5->select(['id', 'title', 'is_unique']);
						}
					]);
			}])
			->select(array('id', 'parent_id', 'detail'))->get();

			$onHandDocuments = User::select(['id'])
				->whereHas('clientServices', function($query) use($clientServicesId) {
					$query->whereIn('id', $clientServicesId);
				})
				->with([
					'clientServices' => function($query) use($clientServicesId) {
						$query->whereIn('id', $clientServicesId)->select(['id', 'client_id', 'group_id']);
					},

					'clientServices.client' => function($query) {
						$query->select(['id', 'first_name', 'last_name']);
					},
					'clientServices.client.onHandDocuments' => function($query) {
						$query->select(['id', 'client_id', 'document_id']);
					},
					'clientServices.client.onHandDocuments.document' => function($query) {
						$query->select(['id', 'title', 'is_unique', 'is_company_document']);
					},

					'clientServices.group' => function($query) {
						$query->select(['id', 'name']);
					},
					'clientServices.group.onHandDocuments' => function($query) {
						$query->select(['id', 'group_id', 'document_id']);
					},
					'clientServices.group.onHandDocuments.document' => function($query) {
						$query->select(['id', 'title', 'is_unique', 'is_company_document']);
					}
				])
				->get();

			$response['status'] = 'Success';
			$response['data'] = [
		    	'services' => $services,
		    	'onHandDocuments' => $onHandDocuments
			];
			$response['code'] = 200;
		} else {
			$response['status'] = 'Failed';
        	$response['errors'] = 'No query results.';
			$response['code'] = 404;
		}

		return Response::json($response);
	}

	private function generateDetail($report, $clientService) {
		$serviceProcedure = ServiceProcedure::find($report['service_procedure']);

		$detail = $serviceProcedure->name;

		// Documents
		if( array_key_exists('documents', $clientService)
			&& is_array($clientService['documents'])
			&& count($clientService['documents']) > 0
		) {
			$documents = Document::whereIn('id', $clientService['documents'])->pluck('title')->toArray();

			$documents = ' (' . trim(implode(',', $documents)) . ')';

			$detail .= $documents . '.';
		}

		// Extensions
		if( array_key_exists('extensions', $report) ) {
			if( array_key_exists('estimated_releasing_date', $report['extensions']) ) {
				$estimatedReleasingDate = $report['extensions']['estimated_releasing_date'];
				$estimatedReleasingDate = Carbon::parse($estimatedReleasingDate)->format('F d, Y');

				$detail .= ' Estimated releasing date is ' . $estimatedReleasingDate . '.';
			}

			if( array_key_exists('scheduled_hearing_date_and_time', $report['extensions'])
				&& is_array($report['extensions']['scheduled_hearing_date_and_time'])
				&& count($report['extensions']['scheduled_hearing_date_and_time']) > 0
			) {
				$scheduledHearingDateAndTimes = $report['extensions']['scheduled_hearing_date_and_time'];
				$count = count($scheduledHearingDateAndTimes);

				$detail .= ' The scheduled hearing date are as follows: ';
				foreach($scheduledHearingDateAndTimes as $index => $scheduledHearingDateAndTime) {
					$s = Carbon::parse($scheduledHearingDateAndTime['value'])->format('F d, Y h:i A');

					$detail .= $s;
					if( $index+1 != $count ) {
						$detail .= ', ';
					}
				}
				$detail .= '.';
			}
		}

		// Extras
		if( array_key_exists('extras', $report) ) {
			// Reason
			if( array_key_exists('reason', $report['extras']) ) {
				$detail .= ' With a reason of ' . $report['extras']['reason'] . '.';
			}
			// Cost
			if( array_key_exists('cost', $report['extras']) ) {
				$_clientService = ClientService::findOrFail($clientService['id']);

				$oldCost = number_format($_clientService->cost, 2);
				$newCost = number_format($report['extras']['cost'], 2);

				$detail .= ' from  ' . $oldCost . ' to ' . $newCost . '.';
			}
		}

		return $detail;
	}

	private function getDetail($report, $clientService) {
		$detail = '';

		$cs = ClientService::find($clientService['id']);

		if( $cs ) {
			$serviceParentId = ClientService::find($clientService['id'])->service->parent_id;

			// 63 = 9A Visa Extension
			// 70 = 9G Working Visa Conversion
			if( $serviceParentId == 63 || $serviceParentId == 70 ) {
				$detail = $this->generateDetail($report, $clientService);
			}
		}

		return $detail;
	}

	private function handleStatusUponCompletion($clientService, $serviceProcedureId) {
		$clientService_id = $clientService->id;
		$clientService_serviceId = $clientService->service_id;
		$clientService_status = $clientService->status;

		$service = Service::with([
				'serviceProcedures' => function($query) use($serviceProcedureId) {
					$query->where('id', $serviceProcedureId);
				},
				'serviceProcedures.serviceProcedureDocuments' => function($query) {
					$query->where('is_required', 1);
				}
			])
			->findOrFail($clientService_serviceId);

		if( count($service->serviceProcedures) == 1 ) {
			$serviceProcedure = $service->serviceProcedures[0];

			if( $serviceProcedure->status_upon_completion != null ) {
				$statuses = ['pending','on process','complete', 'released'];

				$currentStatus = $clientService_status;
				$currentStatusIndex = array_search($currentStatus, $statuses, true);

				$targetStatus = $serviceProcedure->status_upon_completion;
				$targetStatusIndex = array_search($targetStatus, $statuses, true);

				if( $targetStatusIndex > $currentStatusIndex ) {
					$serviceProcedureRequiredDocuments = $serviceProcedure->serviceProcedureDocuments
						->map(function($item, $key) {
							return $item['document_id'];
						})->toArray();

					// Service procedure: without required documents
					if( count($serviceProcedureRequiredDocuments) == 0 ) {
						$clientService->update(['status' => $targetStatus]);
						ClientController::updatePackageStatus($clientService->tracking);
					} 
					// Service procedure: with required documents
					else {
						$reportedDocuments = ClientReportDocument::whereHas('clientReport', 
							function($query) use($clientService_id, $serviceProcedureId) {
								$query->where('client_service_id', $clientService_id)
									->where('service_procedure_id', $serviceProcedureId);
							})
							->pluck('document_id')->toArray();

						$difference = array_diff($serviceProcedureRequiredDocuments, $reportedDocuments);

						if( count($difference) == 0 ) {
							$clientService->update(['status' => $targetStatus]);
							ClientController::updatePackageStatus($clientService->tracking);
						}
					}
				}
			}
		}
	}

	private function handleCancelledService($clientService, $serviceProcedureId) {
		$serviceProcedure = ServiceProcedure::with('action', 'category')->findOrFail($serviceProcedureId);

		if( $serviceProcedure->action->name == 'Cancelled' && $serviceProcedure->category->name == 'Service' ) {
			$clientService->update([
				'status' => 'cancelled',
				'active' => 0, 
				'cost' => 0,
				'charge' => 0,
				'tip' => 0
			]);
			ClientController::updatePackageStatus($clientService->tracking);
		}
	}

	private function handleUpdatedTheCost($clientService, $serviceProcedureId, $report) {
		$serviceProcedure = ServiceProcedure::with('action', 'category')->findOrFail($serviceProcedureId);

		if( $serviceProcedure->action->name == 'Updated' && $serviceProcedure->category->name == 'Cost' ) {
			$clientService->update(['cost' => $report['extras']['cost']]);
		}
	}

	public function store(Request $request) {
		$validator = Validator::make($request->all(), [
            'reports' => 'required|array',
            'reports.*.service_procedure' => 'required',
            'reports.*.client_services' => 'required|array',
            'reports.*.client_services.*.id' => 'required',
            'reports.*.client_services.*.documents' => 'array'
        ], [
        	'reports.*.service_procedure.required' => 'The service procedure field is required.',
        	'reports.*.client_services.required' => 'The client services field is required.',
        	'reports.*.client_services.array' => 'The client services field must be an array.',
        	'reports.*.client_services.*.id.required' => 'The client services id field is required.',
        	'reports.*.client_services.*.documents.array' => 'The client services documents field must be an array.',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
        	$reports = $request->reports;
        	$processorId = Auth::user()->id;

        	foreach($reports as $report) {
        		$r = Report::create([
        			'processor_id' => $processorId
        		]);

        		$clientServices = $report['client_services'];
        		foreach($clientServices as $clientService) {
        			$detail = $this->getDetail($report, $clientService);

        			$cr = $r->clientReports()->create([
        				'detail' => $detail,
	        			'client_service_id' => $clientService['id'],
	        			'service_procedure_id' => $report['service_procedure']
	        		]);

        			$cs = ClientService::findOrFail($clientService['id']);

        			if( array_key_exists('documents', $clientService) ) {
	        			$sp = ServiceProcedure::find($report['service_procedure']);
	        			$today = Carbon::now()->toDateString();

	        			$log = Log::updateOrCreate(
	        				[
	        					'client_service_id' => $cs->id,
	        					'service_procedure_id' => $sp->id,
	        					'log_date' => $today,
	        					'log_type' => 'Document',
	        				],
	        				[
	        					'client_id' => $cs->client_id,
	        					'group_id' => $cs->group_id,
	        					'processor_id' => $processorId,
	        					'detail' => $sp->name,
	        				]
	        			);

        				$documents = $clientService['documents'];

        				foreach($documents as $document) {
	        				$cr->clientReportDocuments()->create([
			        			'document_id' => $document
			        		]);

			        		$log->documents()->attach($document);

			        		$serviceProcedureDocument = ServiceProcedureDocument::where('service_procedure_id', $report['service_procedure'])->where('document_id', $document)->first();
			        		if( $serviceProcedureDocument ) {
			        			$mode = $serviceProcedureDocument->mode;
			        			$isCompanyDocument = Document::findOrFail($document)->is_company_document;

			        			if( $mode == 'add' || $mode == 'stay' ) {
			        				OnHandDocument::firstOrCreate([
			        					'client_id' => ($cs->group_id && $isCompanyDocument == 1)
			        						? null
			        						: $cs->client_id,
			        					'group_id' => ($cs->group_id && $isCompanyDocument == 1)
			        						? $cs->group_id
			        						: null,
			        					'document_id' => $document
			        				]);
			        			} elseif( $mode == 'remove' ) {
			        				if( $cs->group_id && $isCompanyDocument == 1 ) {
			        					OnHandDocument::where('group_id', $cs->group_id)
			        						->where('document_id', $document)->delete();
			        				} else {
			        					OnHandDocument::where('client_id', $cs->client_id)
				        					->where('document_id', $document)->delete();
			        				}
			        				
			        			}
			        		}
	        			}
        			}

        			$this->handleStatusUponCompletion($cs, $report['service_procedure']);

        			$this->handleCancelledService($cs, $report['service_procedure']);

        			$this->handleUpdatedTheCost($cs, $report['service_procedure'], $report);
        		}
        	}

        	$response['status'] = 'Success';
			$response['code'] = 200;
        }

		return Response::json($response);
	}

}

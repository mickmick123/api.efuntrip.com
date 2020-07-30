<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ClientController;

use App\ClientService;

use App\Document;

use App\GroupUser;

use App\Log;

use App\Report;

use App\ClientReport;

use App\ClientReportDocument;

use App\Service;

use App\ServiceProcedure;

use App\OnHandDocument;

use App\SuggestedDocument;

use App\User;

use App\ClientTransaction;

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
						// $services = DB::table('client_services as cs')
						$services = ClientService::from('client_services as cs')->with('updatedCost', 'getPoints')
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
//TO BE E
	public function reportServices(Request $request) {
		$clientServicesId = json_decode($request->client_services_id);

		// if( is_array($clientServicesId) ) {

      //Select full paid services
      $fullyPaidServiceId = ClientService::where('id', $clientServicesId)
				->where('is_full_payment', 1)->pluck('service_id')->toArray();

			$services = Service::whereHas('clientServices', function($query) use($clientServicesId) {
				$query->whereIn('id', $clientServicesId);
			})
			->with([
				'serviceProcedures' => function($query) use($fullyPaidServiceId) {
					$query->select(['id', 'service_id', 'name', 'step', 'action_id', 'category_id', 'is_required', 'required_service_procedure', 'documents_mode', 'documents_to_display', 'is_suggested_count'])->orderBy('step');
				},
				'serviceProcedures.action' => function($query) {
					$query->select(['id', 'name']);
				},
				'serviceProcedures.category' => function($query) {
					$query->select(['id', 'name']);
				},
				'serviceProcedures.suggestedDocuments' => function($query) {
					$query->select(['id', 'service_procedure_id', 'document_id', 'points', 'suggested_count'])->where('points', '>', 0);
				},
				'serviceProcedures.suggestedDocuments.document' => function($query) {
					$query->select(['id', 'title', 'shorthand_name', 'is_unique', 'is_company_document']);
				}
			])
			->with(['clientServices' => function($query1) use($clientServicesId) {
				$query1->select(['id', 'client_id', 'group_id', 'service_id', 'tracking', 'status'])
					->whereIn('id', $clientServicesId)
					->with([
						'client' => function($query2) {
							$query2->select(['id', 'first_name', 'last_name']);
						},
						'clientReports' => function($query3) {
							$query3->select(['id', 'client_service_id', 'service_procedure_id']);
						},
						'clientReports.clientReportDocuments' => function($query4) {
							$query4->select(['id', 'client_report_id', 'document_id', 'count']);
						},
						'clientReports.clientReportDocuments.document' => function($query5) {
							$query5->select(['id', 'title', 'shorthand_name', 'is_unique', 'is_company_document']);
						},
					]);
			}])
			->select(array('id', 'parent_id', 'detail'))->get();

			$documents = Document::select(['id', 'title', 'shorthand_name', 'is_unique', 'is_company_document'])->get();

		    $onHandDocuments = User::select(['id'])
				->whereHas('clientServices', function($query) use($clientServicesId) {
					$query->whereIn('id', $clientServicesId);
				})
				->with([
					'clientServices' => function($query) use($clientServicesId) {
						$query->whereIn('id', $clientServicesId)->select(['id', 'client_id']);
					},

					'clientServices.client' => function($query) {
						$query->select(['id', 'first_name', 'last_name']);
					},
					'clientServices.client.onHandDocuments' => function($query) {
						$query->select(['id', 'client_id', 'document_id', 'count']);
					},
					'clientServices.client.onHandDocuments.document' => function($query) {
						$query->select(['id', 'title', 'shorthand_name', 'is_unique', 'is_company_document']);
					}
				])
				->get();

				$clientServices = ClientService::from('client_services as cs')->with('updatedCost', 'getPoints')
	                ->select(DB::raw('cs.id, cs.client_id, date_format(cs.created_at, "%M %e, %Y") as date, cs.tracking, cs.detail, cs.cost, cs.charge, cs.tip, cs.active, IFNULL(transactions.discount, 0) as discount'))
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
	                ->whereIn('cs.id', $clientServicesId)
	                ->where('cs.active', 1)
	                ->where('cs.status', '<>', 'released')
	                ->orderBy('cs.id', 'desc')
	                ->get();

      $tempServices = [];
      $ctr = 0;
      foreach($services as $s){
        if(in_array($s->id,$fullyPaidServiceId)){
          $s['paid_service'] = true;
        }else{
          $s['paid_service'] = false;
        }
        $tempServices[$ctr] = $s;
        $ctr++;
      }

			$response['status'] = 'Success';
			$response['data'] = [
		    	'services' => $tempServices,
		    	'documents' => $documents,
					'onHandDocuments' => $onHandDocuments,
					'clientServices' => $clientServices
			];
			$response['code'] = 200;
		// } else {
		// 	$response['status'] = 'Failed';
    //     	$response['errors'] = 'No query results.';
		// 	$response['code'] = 404;
		// }

		return Response::json($response);
	}

	private function getDetail($clientService, $report) {
		$serviceProcedure = ServiceProcedure::with('action', 'category')->find($report['service_procedure']);

		$detail = $serviceProcedure->name;

		// Immigration Branch
		if( $serviceProcedure->action->name == 'Filed' && $serviceProcedure->category->name == 'Immigration' ) {
			if( $report['extras']['immigration_branch'] ) {
				$detail .= '[' . $report['extras']['immigration_branch'] . ']';
			}
		}

		// Documents
		if( count($clientService['documents']) > 0 ) {
			$selectedDocumentsWithCount = collect($clientService['documents'])->filter(function($item) {
				return $item['count'] > 0;
			})->values()->toArray();

			foreach( $selectedDocumentsWithCount as $index => $document ) {
				$documentTitle = Document::findOrFail($document['id'])->title;

				$detail .= ' (' . $document['count'] . ')' . $documentTitle;

				if( $index == count($selectedDocumentsWithCount) - 1 ) {
					$detail .= '.';
				} else {
					$detail .= ', ';
				}
			}
		}

		// Extensions
		if( $report['extensions']['estimated_releasing_date'] ) {
			$estimatedReleasingDate = $report['extensions']['estimated_releasing_date'];
			$estimatedReleasingDate = Carbon::parse($estimatedReleasingDate)->format('F d, Y');

			$detail .= ' Estimated releasing date is ' . $estimatedReleasingDate . '.';
		}
		if( count($report['extensions']['scheduled_hearing_date_and_time']) > 0 ) {
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

		// Extras
		if( $serviceProcedure->action->name == 'Cancelled' && $serviceProcedure->category->name == 'Service' ) {
			if( $report['extras']['reason'] ) {
				$detail .= ' with a reason of ' . $report['extras']['reason'] . '.';
			}
		}

		if( $serviceProcedure->action->name == 'Updated' && $serviceProcedure->category->name == 'Cost' ) {
			if( $report['extras']['cost'] ) {
				$cs = ClientService::findOrFail($clientService['id']);

				$oldCost = number_format($cs->cost, 2);
				$newCost = number_format($report['extras']['cost'], 2);

				$detail .= ' from  ' . $oldCost . ' to ' . $newCost . '.';
			}
		}

		if( $serviceProcedure->action->name == 'Conversion' && $serviceProcedure->category->name == 'Status' ) {
			if( $report['extras']['conversion_of_status'] && $report['extras']['conversion_of_status_reason'] ) {
				// $report['extras']['conversion_of_status']
					// 1 = pending to on process
					// 2 = on process to pending
				if( $report['extras']['conversion_of_status'] == 1 ) {
					$detail .= ' from pending to on process ';
				} elseif( $report['extras']['conversion_of_status'] == 2 ) {
					$detail .= ' from on process to pending ';
				}

				$detail .= ' with a reason of ' . $report['extras']['conversion_of_status_reason'] . '.';
			}
		}

		if( $serviceProcedure->action->name == 'Discounted' && $serviceProcedure->category->name == 'Service' ) {
			if( $report['extras']['discount_amount'] && $report['extras']['discount_reason'] ) {
				$discountAmount = $report['extras']['discount_amount'];
				$discountReason = $report['extras']['discount_reason'];

				$detail .= ' with an amount of ' . $discountAmount . ' and with a reason of ' . $discountReason . '.';
			}
		}

		return $detail;
	}

	private function handleReports($processorId) {
		$report = Report::create([
       		'processor_id' => $processorId
        ]);

        return $report;
	}

	private function handleClientReports($report, $detail, $clientService, $serviceProcedureId) {
		$clientReport = $report->clientReports()->create([
        	'detail' => $detail,
	        'client_service_id' => $clientService['id'],
	        'service_procedure_id' => $serviceProcedureId
	    ]);

		return $clientReport;
	}

	private function handleClientReportDocuments($clientReport, $clientService, $serviceProcedureId) {
		$clientServiceId = $clientService['id'];
		$documents = $clientService['documents'];

		foreach( $documents as $document ) {
			$old = ClientReportDocument::whereHas('clientReport',
				function($query) use($clientServiceId, $serviceProcedureId) {
					$query->where('client_service_id', $clientServiceId)
						->where('service_procedure_id', $serviceProcedureId);
				}
			)
			->where('document_id', $document['id'])
			->first();

			if( $old ) {
				$oldCount = $old->count;

				if( $oldCount == 0 ) {
					$old->delete();
				}

				if( $oldCount == 0 || ($oldCount != 0 && $document['count'] != 0) ) {
					$clientReport->clientReportDocuments()->create([
					 	'document_id' => $document['id'],
					 	'count' => $document['count']
					]);
				}
			} else {
				$clientReport->clientReportDocuments()->create([
				 	'document_id' => $document['id'],
				 	'count' => $document['count']
				]);
			}
		}
	}

	private function handleLogDocumentLog($clientService, $report, $processorId, $detail) {
		// For report with documents
		$documents = collect($clientService['documents'])->filter(function($item) {
			return $item['count'] > 0;
		})->values()->toArray();

		// For conversion of status
		$serviceProcedure = ServiceProcedure::with('action', 'category')->find($report['service_procedure']);
		$actionName = $serviceProcedure->action->name;
		$categoryName = $serviceProcedure->category->name;

		if( count($documents) > 0 || ($actionName == 'Conversion' && $categoryName == 'Status') || ($actionName == 'Discounted' && $categoryName == 'Service') ) {
			$cs = ClientService::findOrFail($clientService['id']);

			if( $actionName == 'Prepared' && $categoryName == 'Documents' ) {
				$label = 'Prepare required documents, the following documents are needed';
			} elseif( $actionName == 'Filed' && $categoryName == 'Immigration' ) {
				$label = $serviceProcedure->name;

				if( $report['extras']['immigration_branch'] ) {
					$label .= '[' . $report['extras']['immigration_branch'] . ']';
				}
			} elseif( $actionName == 'Discounted' && $categoryName == 'Service' ) {
				$logType = 'Transaction';

				$label = $serviceProcedure->name;

				if( $report['extras']['discount_amount'] && $report['extras']['discount_reason'] ) {
					$discountAmount = $report['extras']['discount_amount'];
					$discountReason = $report['extras']['discount_reason'];

					$label .= ' with an amount of ' . $discountAmount . ' and with a reason of ' . $discountReason . '.';
				}
			} else {
				$label = $serviceProcedure->name;
			}

			// For report with documents
			if( count($documents) > 0 ) {
				$logType = 'Document';
			}

			// For conversion of status
			elseif( $actionName == 'Conversion' && $categoryName == 'Status' ) {
				$logType = 'Status';

				if( $report['extras']['conversion_of_status'] && $report['extras']['conversion_of_status_reason'] ) {
					// $report['extras']['conversion_of_status']
						// 1 = pending to on process
						// 2 = on process to pending
					if( $report['extras']['conversion_of_status'] == 1 ) {
						$label .= ' from pending to on process ';
					} elseif( $report['extras']['conversion_of_status'] == 2 ) {
						$label .= ' from on process to pending ';
					}

					$label .= ' with a reason of ' . $report['extras']['conversion_of_status_reason'] . '.';
				}
			}

			// Log
	        $log = Log::create([
	        	'client_service_id' => $cs->id,
	        	'client_id' => $cs->client_id,
	        	'group_id' => $cs->group_id,
	        	'service_procedure_id' => $serviceProcedure->id,
	        	'processor_id' => $processorId,
	        	'log_type' => $logType,
	        	'detail' => $detail,
	        	'label' => $label,
	        	'log_date' => Carbon::now()->toDateString()
	        ]);

	        // Document log
	        foreach( $documents as $document ) {
	        	$previousOnHand = 0;

	        	$onHandDocument = OnHandDocument::where('client_id', $cs->client_id)
	        		->where('document_id', $document['id'])->first();

	        	if( $onHandDocument ) {
	        		$previousOnHand = $onHandDocument->count;
	        	}

	        	$log->documents()->attach($document['id'], [
	        		'count' => $document['count'],
	        		'previous_on_hand' => $previousOnHand
	        	]);
	        }

	        // Missing documents
	        if( $actionName != 'Filed' ) {
	        	if( $actionName == 'Prepared' && $categoryName == 'Documents' ) {
	        		$preparedDocuments = ClientReport::with('clientReportDocuments')
						->where('client_service_id', $cs->id)
						->where('service_procedure_id', $serviceProcedure->id)
						->orderBy('id', 'desc')
						->get();

					$onHandDocuments = OnHandDocument::where('client_id', $cs->client_id)->get();

					$temp = [];
					$missingDocuments = [];
					foreach( $preparedDocuments as $preparedDocument ) {
						foreach( $preparedDocument->clientReportDocuments as $document ) {

							if( !in_array($document['document_id'], $temp)) {
								$temp[] = $document['document_id'];

								$reportDocumentId = $document['document_id'];
								$reportCount = $document['count'];

								$arr = collect($onHandDocuments)->filter(function($item) use($reportDocumentId) {
									return $item['document_id'] == $reportDocumentId;
								})->values()->toArray();

								if( count($arr) == 0 ) {
									$missingDocuments[] = [
										'document_id' => $reportDocumentId,
										'count' => 0,
										'pending_count' => $reportCount
									];
								} elseif( count($arr) == 1 && $arr[0]['count'] < $reportCount ) {
									$missingDocuments[] = [
										'document_id' => $reportDocumentId,
										'count' => 0,
										'pending_count' => $reportCount - $arr[0]['count']
									];
								}
							}

						}
					}

					foreach( $missingDocuments as $missingDocument ) {
						$previousOnHand = 0;

				        $onHandDocument = OnHandDocument::where('client_id', $cs->client_id)
				        		->where('document_id', $missingDocument['document_id'])->first();

				        if( $onHandDocument ) {
				        	$previousOnHand = $onHandDocument->count;
				        }

				        $log->documents()->attach($missingDocument['document_id'], [
				        	'count' => $missingDocument['count'],
				        	'previous_on_hand' => $previousOnHand,
				        	'pending_count' => $missingDocument['pending_count']
				        ]);
					}
	        	} else {
	        		$clientReports = ClientReport::with(['clientReportDocuments' => function($query) {
			        		$query->where('count', 0);
			        	}])
			        	->where('client_service_id', $cs->id)
			        	->where('service_procedure_id', $serviceProcedure->id)
			        	->get();

			        foreach( $clientReports as $clientReport ) {
			        	foreach( $clientReport->clientReportDocuments as $document ) {
			        		$previousOnHand = 0;

				        	$onHandDocument = OnHandDocument::where('client_id', $cs->client_id)
				        		->where('document_id', $document['document_id'])->first();

				        	if( $onHandDocument ) {
				        		$previousOnHand = $onHandDocument->count;
				        	}

				        	$log->documents()->attach($document['document_id'], [
				        		'count' => $document['count'],
				        		'previous_on_hand' => $previousOnHand
				        	]);
			        	}
			        }
	        	}
	        }
		}
	}

	private function convertToPhotocopyDocuments($documents) {
		$temp = [];

		$documents = collect($documents)->filter(function($item) {
			return $item['count'] > 0;
		})->values()->toArray();

	    foreach( $documents as $document ) {
	       	$photocopyDocument = $this->getPhotocopyDocument($document['id']);

	        if( $photocopyDocument ) {
	        	$index = -1;
		        foreach( $temp as $i => $t ) {
		        	if( $t['id'] == $photocopyDocument->id ) {
		        		$index = $i;
		        	}
		        }

		        if( $index != -1 ) {
		        	$temp[$index]['count'] += $document['count'];
		        } else {
		        	$temp[] = [
			        	'id' => $photocopyDocument->id,
			        	'count' => $document['count']
			        ];
		        }
	        }
	    }

	    return $temp;
	}

	private function getPhotocopyDocument($id) {
		$document = Document::findOrFail($id);
		$documentTitle = trim($document->title);

		$isPhotocopy = substr_count($documentTitle, 'Photocopy');

		if( $isPhotocopy > 0 ) {
			$photocopyDocument = Document::findOrFail($id);
		} else {
			$documentTitle = substr($documentTitle, 11);

			$photocopyDocumentTitle = 'Photocopy - ' . $documentTitle;

			$photocopyDocument = Document::where('title', $photocopyDocumentTitle)->first();
		}

		return $photocopyDocument;
	}

	private function handleOnHandDocuments($clientService, $serviceProcedureId) {
		$clientServiceId = $clientService['id'];
		$documents = $clientService['documents'];

		if( count($documents) > 0 ) {
			$cs = ClientService::findOrFail($clientServiceId);

			$serviceProcedure = ServiceProcedure::findOrFail($serviceProcedureId);
			$mode = $serviceProcedure->documents_mode;
			$actionName = $serviceProcedure->action->name;
			$categoryName = $serviceProcedure->category->name;

			foreach( $documents as $document ) {
				if( $mode == 'add' ) {
					$onHand = OnHandDocument::where('client_id', $cs->client_id)
						->where('document_id', $document['id'])->first();

					if( $onHand ) {
						$isUnique = Document::findOrFail($document['id'])->is_unique;

						if( $isUnique == 0 ) {
							$onHand->increment('count', $document['count']);
						}
					} else {
						$query = OnHandDocument::create([
							'client_id' => $cs->client_id,
							'document_id' => $document['id'],
							'count' => $document['count']
						]);

						if( $document['count'] == 0 ) {
							$query->delete();
						}
					}
				} elseif( $mode == 'remove' ) {
					$onHand = OnHandDocument::where('client_id', $cs->client_id)
						->where('document_id', $document['id'])->first();

					if( $onHand ) {
						if( $onHand->count -  $document['count'] < 1 ) {
							$onHand->update(['count' => 0]);
							$onHand->delete();
						} else {
							$onHand->decrement('count', $document['count']);
						}
					}
				}
			}
		}
	}

	private function handleStatusUponCompletion($clientService, $serviceProcedureId, $conversionOfStatus) {
		$serviceProcedure = ServiceProcedure::with('action', 'category')->findOrFail($serviceProcedureId);
		$action = $serviceProcedure->action->name;
		$category = $serviceProcedure->category->name;

		$statusUponCompletion = $serviceProcedure->status_upon_completion;

		if( $statusUponCompletion ) {
			$documentsMode = $serviceProcedure->documents_mode;

			if( $documentsMode ) {
				$clientServiceId = $clientService['id'];

				$pendingDocumentsCount = ClientReportDocument::whereHas('clientReport',
					function($query) use($clientServiceId, $serviceProcedureId) {
						$query->where('client_service_id', $clientServiceId)
							->where('service_procedure_id', $serviceProcedureId);
					}
				)
				->where('count', 0)
				->count();

				if( $pendingDocumentsCount == 0 ) {
					$cs = ClientService::findOrFail($clientServiceId);

					// Additional Log
					if( $cs->status != $statusUponCompletion ) {
						$detail = 'Documents complete, service[' . $cs->detail . '] is now ' . $statusUponCompletion . '.';
						$label = 'Documents complete, service is now ' . $statusUponCompletion . '.';

						if( $statusUponCompletion == 'complete' ) {
							$totalCharge = $cs->cost + $cs->charge + $cs->tip + $cs->com_client + $cs->com_agent;

							$discounts = ClientTransaction::where('type', 'Discount')
								->where('client_service_id', $cs->id)->get();
							foreach( $discounts as $discount ) {
								$totalCharge -= $discount->amount;
							}

							$detail .= ' Total charge is PHP' . $totalCharge . '.';
							$label .= ' Total charge is PHP' . $totalCharge . '.';
						}

						Log::create([
				        	'client_service_id' => $cs->id,
				        	'client_id' => $cs->client_id,
				        	'group_id' => $cs->group_id,
				        	'service_procedure_id' => $serviceProcedureId,
				        	'processor_id' => Auth::user()->id,
				        	'log_type' => 'Status',
				        	'detail' => $detail,
				        	'label' => $label,
				        	'log_date' => Carbon::now()->toDateString()
				        ]);
					}

					$arr = ['status' => $statusUponCompletion];

					if( $action == 'Cancelled' && $category == 'Service' ) {
						$arr['active'] = 0;
						$arr['cost'] = 0;
						$arr['charge'] = 0;
						$arr['tip'] = 0;
					}

					$cs->update($arr);

					ClientController::updatePackageStatus($cs->tracking);
				}
			} else {
				$clientServiceId = $clientService['id'];

				$cs = ClientService::findOrFail($clientServiceId);

				if( $action == 'Conversion' && $category == 'Status' ) {
					// $conversionOfStatus
						// 1 = pending to on process
						// 2 = on process to pending
					if( $conversionOfStatus == 1 ) {
						$statusUponCompletion = 'on process';
					} elseif( $conversionOfStatus == 2 ) {
						$statusUponCompletion = 'pending';
					}
				} elseif( $action == 'Prepared' && $category == 'Documents' ) {
					if( $cs->status == 'pending' ) {
						$this->handleUpdateStatus($cs->client_id, 1, $clientServiceId);
					} elseif( $cs->status == 'on process' ) {
						$this->handleUpdateStatus($cs->client_id, 2, $clientServiceId);
					}
				}

				// Additional Log
				if( $cs->status != $statusUponCompletion && $action != 'Prepared' && $category != 'Documents' ) {
					$detail = 'Service[' . $cs->detail . '] is now ' . $statusUponCompletion . '.';
					$label = 'Service is now ' . $statusUponCompletion . '.';

					Log::create([
			        	'client_service_id' => $cs->id,
			        	'client_id' => $cs->client_id,
			        	'group_id' => $cs->group_id,
			        	'service_procedure_id' => $serviceProcedureId,
			        	'processor_id' => Auth::user()->id,
			        	'log_type' => 'Status',
			        	'detail' => $detail,
			        	'label' => $label,
			        	'log_date' => Carbon::now()->toDateString()
			        ]);

			        $cs->update(['status' => $statusUponCompletion]);

					ClientController::updatePackageStatus($cs->tracking);
				}
			}
		}
	}

	private function handleUpdatedTheCost($clientService, $serviceProcedureId, $cost) {
		$serviceProcedure = ServiceProcedure::with('action', 'category')->findOrFail($serviceProcedureId);
		$action = $serviceProcedure->action->name;
		$category = $serviceProcedure->category->name;

		if( $action == 'Updated' && $category == 'Cost' ) {
			$clientServiceId = $clientService['id'];

			$cs = ClientService::findOrFail($clientServiceId);

			$cs->update(['cost' => $cost]);
		}
	}

	private function handleDiscountedService($clientService, $serviceProcedureId, $discountAmount, $discountReason) {
		$serviceProcedure = ServiceProcedure::with('action', 'category')->findOrFail($serviceProcedureId);
		$action = $serviceProcedure->action->name;
		$category = $serviceProcedure->category->name;

		if( $action == 'Discounted' && $category == 'Service' ) {
			$cs = ClientService::findOrFail($clientService['id']);

			ClientTransaction::create([
				'type' => 'Discount',
				'client_id' => $cs->client_id,
				'group_id' => $cs->group_id,
				'client_service_id' => $cs->id,
				'amount' => $discountAmount,
				'tracking' => $cs->tracking,
				'reason' => $discountReason
			]);
		}
	}

	private function handleSuggestedDocuments($clientService, $serviceProcedureId) {
		$documents = $clientService['documents'];

		$selectedDocuments = collect($documents)->map(function($item) {
			return $item['id'];
		})->values()->toArray();

		SuggestedDocument::where('service_procedure_id', $serviceProcedureId)
			->whereNotIn('document_id', $selectedDocuments)
			->update(['points' => -1]);

		foreach( $documents as $document ) {
			$suggestedDocument = SuggestedDocument::where('service_procedure_id', $serviceProcedureId)
				->where('document_id', $document['id'])->first();

			if( $suggestedDocument ) {
				if( $suggestedDocument->points != 4 ) {
					$suggestedDocument->increment('points', 1);
				}

				$suggestedDocument->update(['suggested_count' => $document['count']]);
			} else {
				SuggestedDocument::create([
					'service_procedure_id' => $serviceProcedureId,
					'document_id' => $document['id'],
					'points' => 1,
					'suggested_count' => $document['count']
				]);
			}
		}
	}

	public function store(Request $request) {
		$reports = $request->reports;
        $processorId = Auth::user()->id;

        foreach($reports as $report) {
        	// reports table
        	$r = $this->handleReports($processorId);

        	$clientServices = $report['client_services'];

        	foreach($clientServices as $clientService) {
        		$detail = $this->getDetail($clientService, $report);

        		// client_reports table
	        	$cr = $this->handleClientReports($r, $detail, $clientService, $report['service_procedure']);

	        	// client_report_documents table
	        	$this->handleClientReportDocuments($cr, $clientService, $report['service_procedure']);

	        	// logs && document_log table
	        	$this->handleLogDocumentLog($clientService, $report, $processorId, $detail);

	        	// on_hand_documents table
	        	$this->handleOnHandDocuments($clientService, $report['service_procedure']);

	        	// suggested_documents table
        		$this->handleSuggestedDocuments($clientService, $report['service_procedure']);

	        	$this->handleStatusUponCompletion(
	        		$clientService,
	        		$report['service_procedure'],
	        		$report['extras']['conversion_of_status']
	        	);

  				$this->handleUpdatedTheCost($clientService, $report['service_procedure'], $report['extras']['cost']);

  				$this->handleDiscountedService(
  					$clientService,
  					$report['service_procedure'],
  					$report['extras']['discount_amount'],
  					$report['extras']['discount_reason']
  				);
        	}
        }

		$response['status'] = 'Success';
		$response['code'] = 200;

		return Response::json($response);
	}

	private function handleStandAloneLogDocumentLog($action, $user, $documents) {
		$processorId = Auth::user()->id;

		$detail = $action;

		$documents = collect($documents)->filter(function($item) {
			return $item['count'] > 0;
		})->values()->toArray();

		foreach( $documents as $index => $document ) {
			$documentTitle = Document::findOrFail($document['id'])->title;

			$detail .= ' (' . $document['count'] . ')' . $documentTitle;

			if( $index == count($documents) - 1 ) {
				$detail .= '.';
			} else {
				$detail .= ', ';
			}
		}

		// logs
		$log = Log::create([
			'client_id' => $user['id'],
			'processor_id' => $processorId,
			'log_type' => 'Document',
			'detail' => $detail,
			'label' => $action,
			'log_date' => Carbon::now()->toDateString()
		]);

		// document_log
		foreach( $documents as $document ) {
	        $previousOnHand = 0;

	        $onHandDocument = OnHandDocument::where('client_id', $user['id'])
	        	->where('document_id', $document['id'])->first();

	        if( $onHandDocument ) {
	        	$previousOnHand = $onHandDocument->count;
	        }

	        $log->documents()->attach($document['id'], [
	        	'count' => $document['count'],
	        	'previous_on_hand' => $previousOnHand
	        ]);
	    }
	}

	private function handleStandAloneOnHandDocuments($action, $user) {
		$documents = collect($user['documents'])->filter(function($item) {
			return $item['count'] > 0;
		})->values()->toArray();

		// on_hand_documents
	    foreach( $documents as $document ) {
	    	if( strpos($action, "Received documents") !== false ) {
	    		$onHand = OnHandDocument::where('client_id', $user['id'])
					->where('document_id', $document['id'])->first();

				if( $onHand ) {
					$isUnique = Document::findOrFail($document['id'])->is_unique;

					if( $isUnique == 0 ) {
						$onHand->increment('count', $document['count']);
					}
				} else {
					$query = OnHandDocument::create([
						'client_id' => $user['id'],
						'document_id' => $document['id'],
						'count' => $document['count']
					]);
				}
	    	} elseif( strpos($action, "Released documents") !== false ) {
	    		$onHand = OnHandDocument::where('client_id', $user['id'])
					->where('document_id', $document['id'])->first();

				if( $onHand ) {
					if( $onHand->count -  $document['count'] < 1 ) {
						$onHand->update(['count' => 0]);
						$onHand->delete();
					} else {
						$onHand->decrement('count', $document['count']);
					}
				}
	    	} elseif( strpos($action, "Generate photocopies of documents") !== false ) {
	    		$documentId = null;

				$photocopyDocument = $this->getPhotocopyDocument($document['id']);

				if( $photocopyDocument ) {
					$documentId = $photocopyDocument->id;
				}

				if( $documentId ) {
					$onHand = OnHandDocument::where('client_id', $user['id'])
						->where('document_id', $documentId)->first();

					if( $onHand ) {
						$isUnique = Document::findOrFail($documentId)->is_unique;

						if( $isUnique == 0 ) {
							$onHand->increment('count', $document['count']);
						}
					} else {
						$query = OnHandDocument::create([
							'client_id' => $user['id'],
							'document_id' => $documentId,
							'count' => $document['count']
						]);
					}
				}
	    	}
	    }
	}

	public function receivedDocuments(Request $request) {
		foreach( $request->users as $user ) {
			$action = 'Received documents';

	        $this->handleStandAloneLogDocumentLog($action, $user, $user['documents']);

	        $this->handleStandAloneOnHandDocuments($action, $user);

	        $this->handleUpdateStatus($user['id'], 1);
		}

		$response['status'] = 'Success';
		$response['code'] = 200;

		return Response::json($response);
	}

	public function releasedDocuments(Request $request) {
		foreach( $request->users as $user ) {
	        $action = 'Released documents';

			if( strlen(trim($user['recipient'])) > 0 ) {
				$action .= ' to client\'s representative ' . $user['recipient'];
			}

	        $this->handleStandAloneLogDocumentLog($action, $user, $user['documents']);

	        $this->handleStandAloneOnHandDocuments($action, $user);
		}

		$response['status'] = 'Success';
		$response['code'] = 200;

		return Response::json($response);
	}

	public function generatePhotocopies(Request $request) {
		foreach( $request->users as $user ) {
	       	$action = 'Generate photocopies of documents';

	       	$documents = $this->convertToPhotocopyDocuments($user['documents']);

	        $this->handleStandAloneLogDocumentLog($action, $user, $documents);

	        $this->handleStandAloneOnHandDocuments($action, $user);

	        $this->handleUpdateStatus($user['id'], 1);
		}

		$response['status'] = 'Success';
		$response['code'] = 200;

		return Response::json($response);
	}

	public function getDocuments() {
		$documents = Document::select(['id', 'title', 'shorthand_name', 'is_unique', 'is_company_document'])->get();

		$response['status'] = 'Success';
		$response['data'] = [
		    'documents' => $documents
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function getDocumentsById($id) {
		$logs = Log::where('client_id', $id)->pluck('id');

		$latestDocs = DB::table('document_log')->whereIn('log_id', $logs)->groupBy('document_id')
						->pluck('document_id');

		$docs1 = Document::select(['id', 'title', 'shorthand_name', 'is_unique', 'is_company_document'])
					->whereIn('id', $latestDocs)->get();

		$docs2 = Document::select(['id', 'title', 'shorthand_name', 'is_unique', 'is_company_document'])
					->whereNotIn('id', $latestDocs)->get();

	  $d1 = collect($docs1);
    $d2 = collect($docs2);

		$response['status'] = 'Success';

    $response['data'] = [
         'latestDocuments' => $d1,
         'otherDocuments' => $d2
     ];

		$response['code'] = 200;

		return Response::json($response);
	}

	public function getOnHandDocuments($id) {
		$onHandDocuments = OnHandDocument::with('document')->where('client_id', $id)->get();

		$response['status'] = 'Success';
		$response['data'] = [
		    'onHandDocuments' => $onHandDocuments
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function documentLogs($id) {
		$documentLogs = Log::with('documents', 'processor', 'serviceProcedure', 'clientService')
			->where(function($query) use($id) {
				$query->where('client_id', $id)
					->where('log_type', 'Document')
					->where('client_service_id', null)
					->where('service_procedure_id', null);
			})
			->orWhere(function($query) use($id) {
				$query->where('client_id', $id)
					->where('log_type', 'Document')
					->whereHas('serviceProcedure', function($query2) {
						$query2->whereNotNull('documents_mode');
					});
			})
			->orderBy('id', 'desc')
			->get();

		$response['status'] = 'Success';
		$response['data'] = [
		    'documentLogs' => $documentLogs
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	private function handleUpdateStatus($clientId, $type, $_clientServiceId = null) {
		if( $type == 1 ) {
			$status = 'pending';
		} elseif( $type == 2 ) {
			$status = 'on process';
		}

		// if( $_clientServiceId ) {
		// 	$clientServicesId = ClientService::where('id', $_clientServiceId)
		// 		->where('active', 1)->where('status', $status)->pluck('id')->toArray();
		// } else {
		// 	$clientServicesId = ClientService::where('client_id', $clientId)
		// 		->where('active', 1)->where('status', $status)->pluck('id')->toArray();
		// }
		$clientServicesId = ClientService::where('client_id', $clientId)
				->where('active', 1)->pluck('id')->toArray();

		$clientReports = ClientReport::with('clientReportDocuments')
			->whereIn('client_service_id', $clientServicesId)
			->whereHas('serviceProcedure', function($query) {
				$query->where('step', 1);
			})
			->orderBy('id', 'desc')
			->get();

		if( count($clientReports) > 0 ) {
			$onHandDocuments = OnHandDocument::where('client_id', $clientId)->join('documents', 'on_hand_documents.document_id', '=', 'documents.id')->get();

			

			$clientDocsArr = [];
			$clientArray = [];
			$allRcvdDocs = [];

			foreach( $clientReports as $clientReport ) {
				if( !in_array($clientReport->client_service_id, $clientDocsArr) ) {

					array_push($clientDocsArr, $clientReport->client_service_id);

					$clientArray[] = $clientReport;

				}
			}
			$temp = [];

			$clientArray = collect($clientArray)->sortBy('client_service_id')->toArray();

			foreach( $clientArray as $clientReport ) {

				$field1 = $clientReport['client_service_id'];
				$field2 = $clientReport['service_procedure_id'];

				$found = collect($temp)->filter(function($item) use($field1, $field2) {
					return $item['client_service_id'] == $field1 && $item['service_procedure_id'] == $field2;
				});

				if( count($found) == 0 ) {
					$temp[] = [
						'client_service_id' => $field1,
						'service_procedure_id' => $field2
					];

					$counter = 0;

					foreach( $clientReport['client_report_documents'] as $cli => $clientReportDocument ) {
						$index = -1;

						foreach( $onHandDocuments as $i => $onHandDocument ) {
							if( $clientReportDocument['document_id'] == $onHandDocument->document_id ) {
								$index = $i;
							}
						}

						if( $index == -1 ) {
							$counter++;
						} else {
							if( $clientReportDocument['count'] > $onHandDocuments[$index]->count ) {
								$counter++;
							} else {
								if( str_contains($onHandDocuments[$index]->title, 'Photocopy') ) {
									
									if(!$this->ifDocsExist($allRcvdDocs, $onHandDocuments[$index]->id)) {
										$allRcvdDocs[] = [
											'id' => $onHandDocuments[$index]->id,
											'count' => 0
										];
									}

									$docIndex = $this->getDocIndex($allRcvdDocs, $onHandDocuments[$index]->id);

									if( $allRcvdDocs[$docIndex]['count'] < $onHandDocuments[$index]->count ) {
										$allRcvdDocs[$docIndex]['count'] += $clientReportDocument['count'];
									} else {
										$allRcvdDocs[$docIndex]['count'] += $clientReportDocument['count'];
										$counter++;
									}
								}
							}
						}
					}


					if( $counter == 0 ) {
						$newStatus = 'on process';

						$label = 'Documents complete, service is now ' . $newStatus . '.';
					} elseif( $counter != 0 ) {
						$newStatus = 'pending';

						$label = 'Documents incomplete, service is now ' . $newStatus . '.';
					}

					$cs = ClientService::findOrfail($clientReport['client_service_id']);

					if( $cs->status != $newStatus ) {
						$cs->update(['status' => $newStatus]);

						ClientController::updatePackageStatus($cs->tracking);

						$detail = 'Service[' . $cs->detail . '] is now ' . $newStatus . '.';

						Log::create([
								'client_service_id' => $cs->id,
								'client_id' => $cs->client_id,
								'group_id' => $cs->group_id,
								'processor_id' => Auth::user()->id,
								'log_type' => 'Status',
								'detail' => $detail,
								'label' => $label,
								'log_date' => Carbon::now()->toDateString()
						]);
					}
				}

			}
		}
	}

	public function getFiledReports(){
		$csIds = ClientReport::where('id','>=',133)->whereHas('serviceProcedure.action', function($query) {
		 				$query->where('name', 'Filed');
				})->groupBy('client_service_id')->orderBy('client_service_id','DESC')->pluck('client_service_id');

		$testAccts = [8724, 8725, 8726, 8727, 8728, 10499, 10504, 12006, 15055, 15654, 15666];

		$filed = ClientService::with('client')->whereNotIn('client_services.client_id', $testAccts)->whereIn('client_services.id',$csIds)
				->with(array('client.groups' => function($query){
                    $query->select('name');
                }))
				->leftjoin('group_user', 'client_services.client_id', '=', 'group_user.user_id')
                ->leftjoin('groups', 'group_user.group_id', '=', 'groups.id')
                ->select('client_services.*', 'groups.name as group_name')
                ->where('client_services.active',1)->where('client_services.status','!=','cancelled')->orderBy('client_services.id','DESC')->get();

		$response['status'] = 'Success';
		$response['data'] = [
		    'charge' => $filed->sum('charge'),
		    'cost' => $filed->sum('cost'),
		    'tip' => $filed->sum('tip'),
		    'com_client' => $filed->sum('com_client'),
		    'com_agent' => $filed->sum('com_agent'),
		    'services_filed' => $filed,
		];
		$response['code'] = 200;
		return Response::json($response);
	}


	public function checkUpdatedCost(Request $request) {
		$clientIds = $request->input("client_ids") ? $request->client_ids : [];
		$clientIds = explode("," , $clientIds);

		$data = [];
		foreach($clientIds as $clientId) {
			$clientServiceCount = ClientReport::where('client_reports.client_service_id', $clientId)
														->leftjoin('service_procedures', 'client_reports.service_procedure_id', '=', 'service_procedures.id')
														->where('service_procedures.name', 'Updated Cost')
														->first();

			$clientServicePoints = DB::table('client_service_points')->where('client_service_id', $clientId)
										->first();

			$data[] = [
				'id' => $clientId,
				'update_cost' => $clientServiceCount,
				'points' => $clientServicePoints,
			];
		}

		$response['data'] = $data;
		return Response::json($response);
	}

	public function updateClientReportScore(Request $request) {
		$clientServices = $request->data;
		$status = $request->status;

		$data = '';

		foreach($clientServices as $cs) {
			$value = 0;

			if($status === 0) {
				$value = $cs['points']['points'] - 4;

			} else {
				if($cs['points']['points'] > 4) {
					$value = $cs['points']['points'] + 1;
				}
			}

			$updateServicePoints = DB::table('client_service_points')->where('client_service_id', $cs['id'])->update(['points' => $value]);

		}

		$response['status'] = 'Success';
		return Response::json($response);
	}

	public function checkOnHandDocs($clientId) {
		// $onHandDocuments = OnHandDocument::where('client_id', $clientId)->join('documents', 'on_hand_documents.document_id', '=', 'documents.id')->get();


		// foreach($onHandDocuments as $docs) {
		// 	if(str_contains($docs->title, 'Photocopy')) {
		// 		$data[] = [
		// 			'data' => $docs
		// 		];
		// 	}
			
		// }

		$clientServicesId = ClientService::where('client_id', $clientId)
				->where('active', 1)->pluck('id')->toArray();

				$clientReports = ClientReport::with('clientReportDocuments')
			->whereIn('client_service_id', $clientServicesId)
			->whereHas('serviceProcedure', function($query) {
				$query->where('step', 1);
			})
			->orderBy('id', 'desc')
			->get();

			if( count($clientReports) > 0 ) {
				$onHandDocuments = OnHandDocument::where('client_id', $clientId)->join('documents', 'on_hand_documents.document_id', '=', 'documents.id')->get();

			}
			
			$clientDocsArr = [];
			$clientArray = [];
			$allRcvdDocs = [];

			foreach( $clientReports as $clientReport ) {
				if( !in_array($clientReport->client_service_id, $clientDocsArr) ) {

					array_push($clientDocsArr, $clientReport->client_service_id);

					array_push($clientArray, $clientReport);

				}
			}

			
			$clientArray = collect($clientArray)->sortBy('client_service_id')->toArray();

			foreach( $clientArray as $clientReport ) {

					$counter = 0;

					foreach( $clientReport['client_report_documents'] as $cli => $clientReportDocument ) {
						$index = -1;

						foreach( $onHandDocuments as $i => $onHandDocument ) {
							if( $clientReportDocument['document_id'] == $onHandDocument->document_id ) {
								$index = $i;
							}
						}

						if( $index == -1 ) {
							$counter++;
						} else {
							if( $clientReportDocument['count'] > $onHandDocuments[$index]->count ) {
								$counter++;
							} else {
								if( str_contains($onHandDocuments[$index]->title, 'Photocopy') ) {
									
									if(!$this->ifDocsExist($allRcvdDocs, $onHandDocuments[$index]->id)) {
										$allRcvdDocs[] = [
											'id' => $onHandDocuments[$index]->id,
											'count' => 0
										];
									}

									$docIndex = $this->getDocIndex($allRcvdDocs, $onHandDocuments[$index]->id);

									if( $allRcvdDocs[$docIndex]['count'] < $onHandDocuments[$index]->count ) {
										$allRcvdDocs[$docIndex]['count'] += $clientReportDocument['count'];
									} else {
										$allRcvdDocs[$docIndex]['count'] += $clientReportDocument['count'];
										$counter++;
									}
								}
							}
						}
					}
				

			}
		
	
		$response['data'] = $clientDocsArr;
		$response['array_data'] = $clientArray;
		$response['docCount'] = $allRcvdDocs;
		return Response::json($response);
	}


	public function ifDocsExist($array, $data) {
		$counter = 0;

		if(count($array) > 0) {
			foreach($array as $arr) {
				if($arr['id'] === $data) {
					$counter++;
				}
			}
		}

		if($counter > 0) {
			return true;
		}

		return false;
	}
	

	public function getDocIndex($array, $data) {
		$arrIndex = 0;

		if(count($array) > 0) {
			foreach($array as $i => $arr) {
				if($arr['id'] === $data) {
					$arrIndex = $i;
				}
			}
		}

		return $arrIndex;
	}
}



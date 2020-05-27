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

			$response['status'] = 'Success';
			$response['data'] = [
		    	'services' => $services,
		    	'documents' => $documents,
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

	private function getDetail($clientService, $report) {
		$serviceProcedure = ServiceProcedure::with('action', 'category')->find($report['service_procedure']);

		$detail = $serviceProcedure->name;

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

	private function handleLogDocumentLog($clientService, $report, $processorId) {
		// For report with documents
		$documents = collect($clientService['documents'])->filter(function($item) {
			return $item['count'] > 0;
		})->values()->toArray();

		// For conversion of status
		$serviceProcedure = ServiceProcedure::with('action', 'category')->find($report['service_procedure']);
		$actionName = $serviceProcedure->action->name;
		$categoryName = $serviceProcedure->category->name;

		if( count($documents) > 0 || ($actionName == 'Conversion' && $categoryName == 'Status') ) {
			$cs = ClientService::findOrFail($clientService['id']);
			$detail = $serviceProcedure->name;

			// For report with documents
			if( count($documents) > 0 ) {
				$logType = 'Document';

				if( $actionName == 'Released' && $categoryName == 'Client' ) {
					if( strlen(trim($clientService['recipient'])) > 0 ) {
						$detail .= '\'s representative ' . $clientService['recipient'];
					}
				}
			} 

			// For conversion of status
			elseif( $actionName == 'Conversion' && $categoryName == 'Status' ) {
				$logType = 'Status';

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

			// Log
	        $log = Log::create([
	        	'client_service_id' => $cs->id,
	        	'client_id' => $cs->client_id,
	        	'group_id' => $cs->group_id,
	        	'service_procedure_id' => $serviceProcedure->id,
	        	'processor_id' => $processorId,
	        	'log_type' => $logType,
	        	'detail' => $detail,
	        	'log_date' => Carbon::now()->toDateString()
	        ]);

	        // Document log
	        if( $actionName == 'Generate Photocopies' && $categoryName == 'Documents' ) {
	        	$documents = $this->convertToPhotocopyDocuments($documents);
	        }

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
	        if( $actionName != 'Generate Photocopies' && $actionName != 'Filed' ) {
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

	private function convertToPhotocopyDocuments($documents) {
		$temp = [];

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
					$documentId = null;

					if( $actionName == 'Generate Photocopies' && $categoryName == 'Documents' ) {
						$photocopyDocument = $this->getPhotocopyDocument($document['id']);

						if( $photocopyDocument && $document['count'] > 0 ) {
							$documentId = $photocopyDocument->id;
						}
					} else {
						$documentId = $document['id'];
					}

					if( $documentId ) {
						$onHand = OnHandDocument::where('client_id', $cs->client_id)
							->where('document_id', $documentId)->first();
						
						if( $onHand ) {
							$isUnique = Document::findOrFail($documentId)->is_unique;

							if( $isUnique == 0 ) {
								$onHand->increment('count', $document['count']);
							}
						} else {
							$query = OnHandDocument::create([
								'client_id' => $cs->client_id,
								'document_id' => $documentId,
								'count' => $document['count']
							]);

							if( $document['count'] == 0 ) {
								$query->delete();
							}
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
						if( $statusUponCompletion == 'released' ) {
							$detail = 'Service completed and all documents released.';
						} else {
							$detail = 'Documents complete, service is now ' . $statusUponCompletion . '.';
						}

						Log::create([
				        	'client_service_id' => $cs->id,
				        	'client_id' => $cs->client_id,
				        	'group_id' => $cs->group_id,
				        	'service_procedure_id' => $serviceProcedureId,
				        	'processor_id' => Auth::user()->id,
				        	'log_type' => 'Status',
				        	'detail' => $detail,
				        	'log_date' => Carbon::now()->toDateString()
				        ]);
					}

					$arr = ['status' => $statusUponCompletion];

					$action = $serviceProcedure->action->name;
					$category = $serviceProcedure->category->name;
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

				if( $serviceProcedure->action->name == 'Conversion' && $serviceProcedure->category->name == 'Status' ) {
					// $conversionOfStatus
						// 1 = pending to on process
						// 2 = on process to pending
					if( $conversionOfStatus == 1 ) {
						$statusUponCompletion = 'on process';
					} elseif( $conversionOfStatus == 2 ) {
						$statusUponCompletion = 'pending';
					}
				}

				// Additional Log
				if( $cs->status != $statusUponCompletion ) {
					$detail = 'Service is now ' . $statusUponCompletion . '.';

					Log::create([
			        	'client_service_id' => $cs->id,
			        	'client_id' => $cs->client_id,
			        	'group_id' => $cs->group_id,
			        	'service_procedure_id' => $serviceProcedureId,
			        	'processor_id' => Auth::user()->id,
			        	'log_type' => 'Status',
			        	'detail' => $detail,
			        	'log_date' => Carbon::now()->toDateString()
			        ]);
				}

				$cs->update(['status' => $statusUponCompletion]);

				ClientController::updatePackageStatus($cs->tracking);
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
	        	$this->handleLogDocumentLog($clientService, $report, $processorId);

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
        	}
        }

		$response['status'] = 'Success';
		$response['code'] = 200;

		return Response::json($response);
	}

}

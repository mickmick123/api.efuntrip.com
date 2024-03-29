<?php

namespace App\Http\Controllers;

use App\ServiceProcedure;

use App\Service;

use Response, Validator;

use Illuminate\Http\Request;

class ServiceProcedureController extends Controller
{
    
    public function index($serviceId) {
    	$service = Service::find($serviceId);

		if( $service ) {
			$serviceProcedures = ServiceProcedure::where('service_id', $serviceId)
                ->select(array('id', 'name', 'step'))
                ->whereNotNull('step')
				->orderBy('step')
				->get();

            $optionalServiceProcedures = ServiceProcedure::where('service_id', $serviceId)
                ->select(array('id', 'name', 'step'))
                ->whereNull('step')
                ->orderBy('name')
                ->get();

			$response['status'] = 'Success';
			$response['data'] = [
			    'serviceProcedures' => $serviceProcedures,
                'optionalServiceProcedures' => $optionalServiceProcedures,
                'serviceName' => $service->detail
			];
			$response['code'] = 200;
		} else {
			$response['status'] = 'Failed';
        	$response['errors'] = 'No query results.';
			$response['code'] = 404;
		}

		return Response::json($response);
    }

    public function show($id) {
    	$serviceProcedure = ServiceProcedure::find($id);

    	if( $serviceProcedure ) {
    		$response['status'] = 'Success';
			$response['data'] = [
			    'serviceProcedure' => $serviceProcedure
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
            'name' => 'required',
            'action_id' => 'required',
            'category_id' => 'required',
            'is_required' => 'required'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
        	$serviceProcedure = ServiceProcedure::find($id);

        	if( $serviceProcedure ) {
        		$serviceProcedure->update([
        			'name' => $request->name,
                    'preposition' => $request->preposition,
        			'action_id' => $request->action_id,
        			'category_id' => $request->category_id,
        			'is_required' => $request->is_required,
                    'required_service_procedure' => $request->required_service_procedure,
                    'status_upon_completion' => $request->status_upon_completion,
                    'documents_mode' => $request->documents_mode,
                    'documents_to_display' => $request->documents_to_display,
                    'is_suggested_count' => $request->is_suggested_count
        		]);

                if( $serviceProcedure->step != null && $request->is_required == 0 ) {
                    $serviceProcedure->update(['step' => null]);

                    $_serviceId = $serviceProcedure->service_id;

                    $_serviceProcedures = ServiceProcedure::where('service_id', $_serviceId)
                        ->whereNotNull('step')
                        ->orderBy('step')
                        ->get();

                    foreach($_serviceProcedures as $index => $_serviceProcedure) {
                        ServiceProcedure::findOrFail($_serviceProcedure->id)
                            ->update(['step' => ($index + 1)]);
                    }
                } elseif( $serviceProcedure->step == null && $request->is_required == 1 ) {
                    $_serviceId = $serviceProcedure->service_id;

                    $stepsCount = ServiceProcedure::where('service_id', $_serviceId)->whereNotNull('step')->count();

                    $serviceProcedure->update(['step' => $stepsCount + 1]);
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

    public function store(Request $request) {
    	$validator = Validator::make($request->all(), [
    		'service_id' => 'required',
            'name' => 'required',
            'action_id' => 'required',
            'category_id' => 'required',
            'is_required' => 'required'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
        	$serviceProcedure = ServiceProcedure::create([
        		'service_id' => $request->service_id,
        		'name' => $request->name,
                'preposition' => $request->preposition,
        		'action_id' => $request->action_id,
        		'category_id' => $request->category_id,
        		'is_required' => $request->is_required,
                'required_service_procedure' => $request->required_service_procedure,
                'status_upon_completion' => $request->status_upon_completion,
                'documents_mode' => $request->documents_mode,
                'documents_to_display' => $request->documents_to_display,
                'is_suggested_count' => $request->is_suggested_count
        	]);

            if( $request->is_required == 1 ) {
                $stepsCount = ServiceProcedure::where('service_id', $request->service_id)
                    ->whereNotNull('step')->count();

                $serviceProcedure->update([
                    'step' => $stepsCount + 1
                ]);
            }

        	$response['status'] = 'Success';
			$response['code'] = 200;
        }

        return Response::json($response);
    }

    public function destroy($id) {
    	$serviceProcedure = ServiceProcedure::find($id);

		if( $serviceProcedure ) {
			$serviceId = $serviceProcedure->service_id;
            $step = $serviceProcedure->step;

			$serviceProcedure->delete();

            if( $step != null ) {
                $serviceProcedures = ServiceProcedure::where('service_id', $serviceId)
                    ->whereNotNull('step')
                    ->orderBy('step')
                    ->get();

                $_step = 1;
                foreach($serviceProcedures as $serviceProcedure) {
                    $serviceProcedure->update(['step' => $_step]);

                    $_step += 1;
                }
            }

			$response['status'] = 'Success';
        	$response['code'] = 200;
		} else {
			$response['status'] = 'Failed';
        	$response['errors'] = 'No query results.';
			$response['code'] = 404;
		}

		return Response::json($response);
    }

    public function sort(Request $request) {
        $validator = Validator::make($request->all(), [
            'service_procedures' => 'required|array'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            foreach($request->service_procedures as $index => $serviceProcedure) {
                ServiceProcedure::findOrFail($serviceProcedure['id'])
                    ->update(['step' => ($index + 1)]);
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }
}

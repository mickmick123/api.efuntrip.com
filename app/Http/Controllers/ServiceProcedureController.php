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
                ->select(array('id', 'name', 'step', 'is_required'))
				->orderBy('step')
				->get();

			$response['status'] = 'Success';
			$response['data'] = [
			    'serviceProcedures' => $serviceProcedures,
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
			    'serviceProcedure' => $serviceProcedure->load('serviceProcedureDocuments')
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
            'preposition' => 'required',
            'action_id' => 'required',
            'category_id' => 'required',
            'is_required' => 'required',
            'required_documents' => 'nullable|array',
            'optional_documents' => 'nullable|array'
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
        			'is_required' => $request->is_required
        		]);

        		$serviceProcedure->serviceProcedureDocuments()->delete();

        		foreach($request->required_documents as $requiredDocument) {
	        		$serviceProcedure->serviceProcedureDocuments()->create([
	        			'document_id' => $requiredDocument,
	        			'is_required' => 1
	        		]);
	        	}

	        	foreach($request->optional_documents as $optionalDocument) {
	        		$serviceProcedure->serviceProcedureDocuments()->create([
	        			'document_id' => $optionalDocument,
	        			'is_required' => 0
	        		]);
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
            'preposition' => 'required',
            'action_id' => 'required',
            'category_id' => 'required',
            'is_required' => 'required',
            'required_documents' => 'nullable|array',
            'optional_documents' => 'nullable|array'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
        	$stepsCount = ServiceProcedure::where('service_id', $request->service_id)->count();

        	$serviceProcedure = ServiceProcedure::create([
        		'service_id' => $request->service_id,
        		'name' => $request->name,
                'preposition' => $request->preposition,
        		'step' => $stepsCount + 1,
        		'action_id' => $request->action_id,
        		'category_id' => $request->category_id,
        		'is_required' => $request->is_required
        	]);

        	foreach($request->required_documents as $requiredDocument) {
        		$serviceProcedure->serviceProcedureDocuments()->create([
        			'document_id' => $requiredDocument,
        			'is_required' => 1
        		]);
        	}

        	foreach($request->optional_documents as $optionalDocument) {
        		$serviceProcedure->serviceProcedureDocuments()->create([
        			'document_id' => $optionalDocument,
        			'is_required' => 0
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

			$serviceProcedure->delete();

			$serviceProcedures = ServiceProcedure::where('service_id', $serviceId)->orderBy('step')->get();
			$step = 1;
			foreach($serviceProcedures as $serviceProcedure) {
				$serviceProcedure->update(['step' => $step]);

				$step += 1;
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

}

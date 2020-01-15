<?php

namespace App\Http\Controllers;

use App\ClientDocumentType;

use Response, Validator;

use Illuminate\Http\Request;

class ClientDocumentTypeController extends Controller
{
    
	public function index() {
		$response['status'] = 'Success';
		$response['data'] = [
		    'clientDocumentTypes' => ClientDocumentType::orderBy('name')->get()
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function show($id) {
		$clientDocumentType = ClientDocumentType::find($id);

		if( $clientDocumentType ) {
			$response['status'] = 'Success';
			$response['data'] = [
			    'clientDocumentType' => $clientDocumentType
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
            'name' => 'required|unique:client_document_types,name,'.$id
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	$clientDocumentType = ClientDocumentType::find($id);

        	if( $clientDocumentType ) {
        		$clientDocumentType->update(['name' => $request->name]);

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
            'name' => 'required|unique:client_document_types,name'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	ClientDocumentType::create(['name' => $request->name]);

        	$response['status'] = 'Success';
			$response['code'] = 200;
        }

        return Response::json($response);
	}

	public function destroy($id) {
		$clientDocumentType = ClientDocumentType::find($id);

		if( $clientDocumentType ) {
			$clientDocumentType->delete();

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

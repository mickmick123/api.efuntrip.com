<?php

namespace App\Http\Controllers;

use App\ClientDocumentType;

use Response, Validator;

use Illuminate\Http\Request;

use Storage;

class ClientDocumentTypeController extends Controller
{
    
	public function index(Request $request, $perPage = 20) {
		$sort = $request->input('sort');
		$search = $request->input('search');
		$response['status'] = 'Success';
		// $response['data'] = ClientDocumentType::where('name','LIKE','%'.$request->name.'%')
		$response['data'] = ClientDocumentType::where('name','LIKE','%'.$search.'%')
		->when($sort != '', function ($q) use($sort){
			$sort = explode('-' , $sort);
			return $q->orderBy($sort[0], $sort[1]);
		})
		->paginate($perPage);
		$response['code'] = 200;
		$response['name'] = $search;

		return Response::json($response);
	}

	public function show($id) {
		$clientDocumentType = ClientDocumentType::find($id);

		if( $clientDocumentType ) {
			$response['status'] = 'Success';
			$response['data'] = $clientDocumentType;
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

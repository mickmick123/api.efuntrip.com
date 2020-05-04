<?php

namespace App\Http\Controllers;

use App\Document;

use Response, Validator;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    
    public function index() {
		$response['status'] = 'Success';
		$response['data'] = [
		    'documents' => Document::orderBy('title')->get()
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function show($id) {
		$document = Document::find($id);

		if( $document ) {
			$response['status'] = 'Success';
			$response['data'] = [
			    'document' => $document
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
            'title' => 'required|unique:documents,title,'.$id,
            'shorthand_name' => 'nullable|unique:documents,shorthand_name,'.$id,
            'is_unique' => 'required',
            // 'is_company_document' => 'required'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	$document = Document::find($id);

        	if( $document ) {
        		$document->update([
        			'title' => $request->title,
        			'title_cn' => $request->title_cn,
                    'shorthand_name' => $request->shorthand_name,
        			'is_unique' => $request->is_unique,
                    // 'is_company_document' => $request->is_company_document
        		]);

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
            'title' => 'required|unique:documents,title',
            'shorthand_name' => 'nullable|unique:documents,shorthand_name',
            'is_unique' => 'required',
            // 'is_company_document' => 'required'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	Document::create([
        		'title' => $request->title,
        		'title_cn' => $request->title_cn,
                'shorthand_name' => $request->shorthand_name,
        		'is_unique' => $request->is_unique,
                // 'is_company_document' => $request->is_company_document
        	]);

        	$response['status'] = 'Success';
			$response['code'] = 200;
        }

        return Response::json($response);
	}

	public function destroy($id) {
		$document = Document::find($id);

		if( $document ) {
			$document->delete();

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

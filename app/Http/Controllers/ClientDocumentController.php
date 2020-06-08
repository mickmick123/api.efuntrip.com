<?php

namespace App\Http\Controllers;

use App\ClientDocument;

use App\ClientDocumentType;

use Illuminate\Http\Request;

use Auth, DB, Response, Validator;

use Storage;

class ClientDocumentController extends Controller
{
    
    public function index($id) {
        $clientDocument = ClientDocument::where('client_document_type_id',$id)->get();
        
        $response['status'] = 'Success';
        $response['data'] = $clientDocument;
        $response['code'] = 200;
		
        return Response::json($response);
    }

    public function store(Request $request) {
        
        foreach($request->data as $item) {
            $this->uploadDocuments($item);
            $path = 'client-documents/' . $item['file_path'] . '/'.$item['img_name'];
            $expired_at = ($item['expired_at'] === null) ? '' : $item['expired_at'];

            $checkDuplicate = ClientDocument::where('client_id',$item['client_id'])
                                ->where('client_document_type_id',$item['client_document_type_id'])
                                ->where('file_path',$path)
                                ->where('issued_at',$item['issued_at'])
                                ->when($expired_at != '', function ($q) use($expired_at){
                                    return $q->where('expired_at',$expired_at);
                                })
                                ->count();
            if($checkDuplicate > 0) {
                ClientDocument::where('client_id',$item['client_id'])
                                ->where('client_document_type_id',$item['client_document_type_id'])
                                ->where('file_path',$path)
                                ->where('issued_at',$item['issued_at'])
                                ->when($expired_at != '', function ($q) use($expired_at){
                                    return $q->where('expired_at',$expired_at);
                                })
                                ->delete();
            }

            ClientDocument::create([
                'client_id' => $item['client_id'],
                'client_document_type_id' => $item['client_document_type_id'], 
                'file_path' => $path, 
                'issued_at' => $item['issued_at'], 
                'expired_at' => $expired_at
            ]);
        }

        return json_encode([
            'success' => true,
            'message' => 'Successfully saved.'
        ]);
    }


    public function uploadDocuments($data) {
        $img64 = $data['imgBase64'];

        list($type, $img64) = explode(';', $img64);
        list(, $img64) = explode(',', $img64); 
        
        if($img64!=""){ // storing image in storage/app/public Folder 
                \Storage::disk('public')->put('client-documents/' . $data['file_path'] . '/' . $data['img_name'], base64_decode($img64)); 
        } 
    }

	public function getDocumentsByClient($id) {
        // $clientDocument = ClientDocument::where('client_id',$id)->get();
        $clientDocument = DB::table('client_documents as cd')
                                ->leftjoin('client_document_types', 'cd.client_document_type_id', '=', 'client_document_types.id')
                                ->where('cd.client_id',$id)
                                ->where('cd.status', 1)
                                ->orderBy('cd.id', 'desc')
                                ->get();

        $response['status'] = 'Success';
        $response['data'] = $clientDocument;
        $response['code'] = 200;
		
        return Response::json($response);
    }

	public function getDocumentsByClientApp($id) {
        // $clientDocument = ClientDocument::where('client_id',$id)->get();
        $clientDocument = ClientDocument::from('client_documents as cd')
                                // ->with('clientDocuments')
                                ->leftjoin('client_document_types', 'cd.client_document_type_id', '=', 'client_document_types.id')
                                ->where('cd.client_id',$id)
                                ->where('cd.status', 1)
                                // ->groupBy('client_document_type_id')
                                ->distinct('client_document_type_id')
                                ->orderBy('cd.id', 'desc')
                                ->select('cd.*', 'client_document_types.name as name')
                                ->get();

        $response['status'] = 'Success';
        $response['data'] = $clientDocument;
        $response['code'] = 200;
		
        return Response::json($response);
    }
    
    public function getDocumentTypes() {
        $clientDocumentType = ClientDocumentType::orderBy('name', 'asc')->get();
        
        $response['status'] = 'Success';
        $response['data'] = $clientDocumentType;
        $response['code'] = 200;
		
        return Response::json($response);
    }

    public function uploadDocumentsByClient(Request $request, $id) {
        $arrayTest = [];

        foreach($request->data as $item) {
            $expired_at = ($item['expired_at'] === null) ? '' : $item['expired_at'];
            $documentType = ClientDocumentType::where('id', $item['client_document_type_id'])->first();

            $path = 'client-documents/' . $documentType->name . '/'.$item['file_path'];

            $checkDuplicate = ClientDocument::where('client_id',$item['client_id'])
                                ->where('client_document_type_id',$item['client_document_type_id'])
                                ->where('file_path',$path)
                                ->where('issued_at',$item['issued_at'])
                                ->when($expired_at != '', function ($q) use($expired_at){
                                    return $q->where('expired_at',$expired_at);
                                })
                                ->count();

            if($checkDuplicate > 0) {
                ClientDocument::where('client_id',$item['client_id'])
                                ->where('client_document_type_id',$item['client_document_type_id'])
                                ->where('file_path',$path)
                                ->where('issued_at',$item['issued_at'])
                                ->when($expired_at != '', function ($q) use($expired_at){
                                    return $q->where('expired_at',$expired_at);
                                })
                                ->delete();
            }

            ClientDocument::create([
                'client_id' => $item['client_id'],
                'client_document_type_id' => $item['client_document_type_id'], 
                'file_path' => $path, 
                'issued_at' => $item['issued_at'], 
                'expired_at' => $expired_at
            ]);

            $imgData = [
                'imgBase64' => $item['imgBase64'],
                'file_path' => $documentType->name,
                'img_name' => $item['file_path']
            ];

            $this->uploadDocuments($imgData);
        }

        return json_encode([
            'success' => true,
            'message' => 'Successfully saved.'
        ]);
    }

    

    public function uploadDocumentsByClientApp(Request $request) {
        $arrayTest = [];
        $expired_at = ($request['expired_at'] === null) ? '' : $request['expired_at'];
        $documentType = ClientDocumentType::where('id', $request['client_document_type_id'])->first();

        
        $checkDuplicate = ClientDocument::where('client_id',$request['client_id'])
                            ->where('client_document_type_id',$request['client_document_type_id'])
                            ->where('issued_at',$request['issued_at'])
                            ->when($expired_at != '', function ($q) use($expired_at){
                                return $q->where('expired_at',$expired_at);
                            })
                            ->count();

        if($checkDuplicate > 0) {
            ClientDocument::where('client_id',$request['client_id'])
                            ->where('client_document_type_id',$request['client_document_type_id'])
                            ->where('issued_at',$request['issued_at'])
                            ->when($expired_at != '', function ($q) use($expired_at){
                                return $q->where('expired_at',$expired_at);
                            })
                            ->delete();
        }

        
        
        foreach($request->images as $item) {
            
            $path = 'client-documents/' . $documentType->name . '/'.$item['file_path'];

            ClientDocument::create([
                'client_id' => $request['client_id'],
                'client_document_type_id' => $request['client_document_type_id'], 
                'file_path' => $path, 
                'issued_at' => $request['issued_at'], 
                'expired_at' => $expired_at
            ]);
    
            $imgData = [
                'imgBase64' => $item['imgBase64'],
                'file_path' => $documentType->name,
                'img_name' => $item['file_path']
            ];
    
            $this->uploadDocuments($imgData);
        }

        

        return json_encode([
            'success' => true,
            'message' => 'Successfully saved.',
            'data' => $request->images
        ]);
    }

    public function deleteClientDocument(Request $request) {
        $data = $request->data;
        
        if($data['expired_at'] === '0000-00-00' || $data['issued_at'] === null) {
            $expired_at = '';
        } else {
            $expired_at = $data['expired_at'];
        }

        $clientResult = ClientDocument::where('client_id',$data['client_id'])
                            ->where('client_document_type_id',$data['client_document_type_id'])
                            ->where('file_path',$data['file_path'])
                            ->where('issued_at',$data['issued_at'])
                            ->when($expired_at != '', function ($q) use($expired_at){
                                return $q->where('expired_at',$expired_at);
                            })
                            ->update([ 'status' => 0 ]);
                            // ->delete();


        if($clientResult) {
            return json_encode([
                'success' => true,
                'message' => 'Document successfully deleted.'
            ]);
        } else {
            return json_encode([
                'success' => false,
                'message' => 'Failed to delete document.'
            ]);
        }
    }
}

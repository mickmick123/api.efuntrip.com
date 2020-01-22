<?php

namespace App\Http\Controllers;

use App\ClientDocument;

use Illuminate\Http\Request;

use Response, Validator;

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
}

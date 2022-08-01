<?php

namespace App\Http\Controllers;

use App\FuntripPackages;

use Response;

use Illuminate\Http\Request;

class FuntripController extends Controller
{
    public function addPackage(REQUEST $request) {
        $img64 = $request->package_url;

        list($type, $img64) = explode(';', $img64);
        list(, $img64) = explode(',', $img64); 
        \Storage::disk('public')->put('funtrip/' . $request->filePath, base64_decode($img64)); 

        $fp = new FuntripPackages;
        $fp->filePath =  'storage/funtrip/'.$request->filePath;
        $fp->package_name = $request->package_name;
        $fp->package_price = $request->package_price;
        // $fp->package_url = $request->package_url;
        $fp->package_description = $request->package_description;
        $fp->save();
        
        $response['status'] = 'Success';
		$response['data'] = [];
		$response['code'] = 200;

		return Response::json($response);
    }

    public function getPackages() {
        $fp = FuntripPackages::get();
		$response['status'] = 'Success';
		$response['data'] = $fp;
		$response['code'] = 200;

		return Response::json($response);
	}

    public function updatePackage(REQUEST $request) {
        $fp = FuntripPackages::where('id', '=', $request->id)->first();
        $img64 = $request->package_url;

        list($type, $img64) = explode(';', $img64);
        list(, $img64) = explode(',', $img64); 
        \Storage::disk('public')->put('funtrip/' . $request->filePath, base64_decode($img64)); 
        
        $fp->update([
            'filePath' =>  'storage/funtrip/'.$request->filePath,
            'package_name' => $request->package_name,
            'package_price' => $request->package_price,
            // 'package_url' => $request->package_url,
            'package_description' => $request->package_description
        ]);
        $response['status'] = 'Success';
		$response['data'] = [];
		$response['code'] = 200;

		return Response::json($response);
    }

    public function deletePackage(REQUEST $request) {
        FuntripPackages::where('id', '=', $request->id)->delete();
        $response['status'] = 'Success';
		$response['data'] = [];
		$response['code'] = 200;

        return Response::json($response);
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

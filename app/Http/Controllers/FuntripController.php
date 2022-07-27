<?php

namespace App\Http\Controllers;

use App\FuntripPackages;

use Response;

use Illuminate\Http\Request;

class FuntripController extends Controller
{
    public function addPackage(REQUEST $request) {
        $fp = new FuntripPackages;
        $fp->package_name = $request->package_name;
        $fp->package_price = $request->package_price;
        $fp->package_url = $request->package_url;
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
        $fp->update([
            'package_name' => $request->package_name,
            'package_price' => $request->package_price,
            'package_url' => $request->package_url,
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

}

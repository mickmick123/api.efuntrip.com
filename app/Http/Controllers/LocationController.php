<?php

namespace App\Http\Controllers;

use App\Location;
use App\LocationDetail;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function getLocation(){
        $loc = Location::all();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $loc;

        return Response::json($response);
    }

    public function getLocationDetail(Request $request){
        $loc = LocationDetail::where("loc_id", $request->id)->orderBy("location_detail", "ASC")->get();

        $response = [];
        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $loc;

        return Response::json($response);
    }
}

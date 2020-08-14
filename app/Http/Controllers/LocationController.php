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
        if(!is_numeric($request->id)){
            $loc = Location::where("location", "=", $request->id)->first();
            if($loc){
                $location_id = $loc->id;
            }else{
                $location_id = 0;
            }
        }else{
            $location_id = $request->id;
        }

        $detail = LocationDetail::where("loc_id", $location_id)->orderBy("location_detail", "ASC")->get();

        $response = [];
        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $detail;

        return Response::json($response);
    }
}

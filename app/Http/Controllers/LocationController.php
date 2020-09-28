<?php

namespace App\Http\Controllers;

use App\InventoryConsumables;
use App\InventoryParentUnit;
use App\Location;
use App\LocationDetail;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function getLocation(Request $request){
        $loc = Location::where("type", "=", $request->type)->get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $loc;

        return Response::json($response);
    }

    public function getLocationDetail(Request $request){
        if(!is_numeric($request->id)){
            $loc = Location::where([["location", "=", $request->id],["type", "=", $request->type]])->first();
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

    public function getLocationConsumable(Request $request){
        $invId = InventoryConsumables::where([
            ['type','=','Purchased'],
            ['inventory_id',$request->inventory_id]
        ])->pluck('location_id');

        $locId = LocationDetail::whereIn('id',$invId)->pluck('loc_id');
        $locDet = LocationDetail::whereIn('id',$invId)->get();
        $loc = Location::whereIn('id',$locId)->get();

        foreach ($locDet as $k=>$v){
            $v->remaining = InventoryConsumables::where([
                ['inventory_id',$request->inventory_id],
                ['location_id',$v->id]
            ])->orderBy('id','DESC')->limit(1)->pluck('remaining');
//            $v->convertedRemaining = InventoryController::unitFormat($request->inventory_id, $v->remaining[0]);
            $min_purchase = InventoryParentUnit::where('inv_id',$request->inventory_id)->orderBy('id','DESC')->pluck('min_purchased');
//            $v->convertedMinPurchased = InventoryController::unitFormat($request->inventory_id, $min_purchase[0]);
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['location'] = $loc;
        $response['locationDetail'] = $locDet;

        return Response::json($response);
    }
}

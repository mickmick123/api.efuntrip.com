<?php

namespace App\Http\Controllers;

use App\InventoryConsumables;
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

    public function getLocationConsumable(Request $request){
        $invId = InventoryConsumables::where([
            ['type','=','Purchased'],
            ['inventory_id',$request->inventory_id]
        ])->pluck('location_id');

        $inv = InventoryConsumables::where([
            ['inventory_id',$request->inventory_id],
            ['location_id',$request->location_detail]
        ])->get();

        $item = InventoryConsumables::where([
            ['inventory_id',$request->inventory_id],
            ['location_id',$request->location_detail]
        ])->orderBy('id','DESC')->limit(1)->first();

        if($item){
            $remaining = $item->remaining;
        }else{
            $remaining = 0;
        }

        $consumed = 0;
        $purchased = 0;

        foreach($inv as $k=>$v){
            if($v->type === 'Consumed'){
                $consumed = $consumed + $v->qty;
            }else if($v->type === 'Purchased'){
                $purchased = $purchased + $v->qty;
            }
        }

        $limit = $purchased - $consumed;

        $locId = LocationDetail::whereIn('id',$invId)->pluck('loc_id');
        $loc = Location::whereIn('id',$locId)->get();

        if($request->location_id === "undefined"){
            $locDet = LocationDetail::whereIn('id',$invId)->get();
            $loc = Location::whereIn('id',$locId)->get();
        }else{
            $locDet = LocationDetail::where('loc_id',$request->location_id)
                ->whereIn('id',$invId)->get();
        }

        $convertedQty = InventoryController::contentToMinPurchased($request->inventory_id,$request->unit_id,$request->qty);

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['location'] = $loc;
        $response['locationDetail'] = $locDet;
        $response['limit'] = $limit;
        $response['quantities'] = $convertedQty;
        $response['remaining'] = InventoryController::unitFormat($request->inventory_id, $remaining);
//        $response['request'] = $request->all();

        return Response::json($response);
    }
}

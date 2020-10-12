<?php

namespace App\Http\Controllers;

use App\InventoryConsumables;
use App\InventoryPurchaseUnit;
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
        $getRemaining = InventoryPurchaseUnit::leftJoin('inventory_unit as iun','inventory_purchase_unit.unit_id','iun.unit_id')
            ->where('inventory_purchase_unit.inv_id',$request->inventory_id)
            ->get(['inventory_purchase_unit.inv_id','inventory_purchase_unit.unit_id','iun.name','inventory_purchase_unit.qty']);
        foreach($getRemaining as $k=>$v){
            $purchased[$k] = 0;
            $notPurchased[$k] = 0;
            $icon = InventoryConsumables::where([['inventory_id',$v->inv_id],['unit_id',$v->unit_id]])->get();
            foreach($icon as $kk=>$vv){
                if($vv->type === 'Purchased'){
                    $purchased[$k] += $vv->qty;
                }else{
                    $notPurchased[$k] += $vv->qty;
                }
            }
            $v->remaining = $purchased[$k] - $notPurchased[$k];
        }

        $data = ['location'=>$loc,'locationDetail'=>$locDet,'locationRemaining'=>$getRemaining];

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $data;

        return Response::json($response);
    }
}

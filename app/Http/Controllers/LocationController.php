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
    /*
        http://localhost:8082/#/inventory/list/{inventory_id}
        It will show the location as per its type
    */
    public function getLocation(Request $request)
    {
        $loc = Location::where("type", "=", $request->type)->get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $loc;

        return Response::json($response);
    }

    /*
        http://localhost:8082/#/inventory/list/{inventory_id}
        It will show the location detail once the location is selected and its type
    */
    public function getLocationDetail(Request $request)
    {
        if (!is_numeric($request->id)) {
            $loc = Location::where([["location", "=", $request->id], ["type", "=", $request->type]])->first();
            if ($loc) {
                $location_id = $loc->id;
            } else {
                $location_id = 0;
            }
        } else {
            $location_id = $request->id;
        }

        $detail = LocationDetail::where("loc_id", $location_id)->orderBy("location_detail", "ASC")->get();

        $response = [];
        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $detail;

        return Response::json($response);
    }

    /*
        http://localhost:8082/#/inventory/list/{inventory_id}
        It will Show the Location and Location Detail then the Current remaing of last unit
        for every location of the selected Inventory
    */
    public function getLocationConsumable(Request $request)
    {
        $invId = InventoryConsumables::where([
            ['type', '=', 'Purchased'],
            ['inventory_id', $request->inventory_id]
        ])->pluck('location_id');

        $locId = LocationDetail::whereIn('id', $invId)->pluck('loc_id');
        $locDet = LocationDetail::whereIn('id', $invId)->get();
        $loc = Location::whereIn('id', $locId)->get();
        $getRemaining = InventoryPurchaseUnit::leftJoin('inventory_unit as iun', 'inventory_purchase_unit.unit_id', 'iun.unit_id')
            ->where('inventory_purchase_unit.inv_id', $request->inventory_id)
            ->get(['inventory_purchase_unit.inv_id', 'inventory_purchase_unit.unit_id', 'iun.name', 'inventory_purchase_unit.qty']);
        $totalRemaining = [];
        $unitCalculation = [];
        foreach ($loc as $k => $v) {
            $totalRemaining[$k] = ['loc_id' => 0, 'remaining' => 0, 'consumeRemaining' => 0];
        }
        foreach ($loc as $k => $v) {
            foreach ($getRemaining as $kk => $vv) {
                $unitCalculation[$kk] = $vv->qty;
                $purchased[$kk] = 0;
                $notPurchased[$kk] = 0;
                $icon = InventoryConsumables::where([['inventory_id', $vv->inv_id], ['unit_id', $vv->unit_id]])->get();
                foreach ($icon as $kkk => $vvv) {
                    if ($v->id === $vvv->location_id) {
                        if ($vvv->type === 'Purchased') {
                            $purchased[$kk] += $vvv->qty;
                        } else {
                            $notPurchased[$kk] += $vvv->qty;
                        }
                    }
                }
                $vv->remaining = $purchased[$kk] - $notPurchased[$kk];
                $totalRemaining[$k]['loc_id'] = $v->id;
                $totalRemaining[$k]['remaining'] += $purchased[$kk] - $notPurchased[$kk];
                $totalRemaining[$k]['consumeRemaining'] += $notPurchased[$kk];
            }
        }


        $data = ['location' => $loc, 'locationDetail' => $locDet, 'unitCalculation' => $getRemaining, 'locationTotalRemaining' => $totalRemaining];

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $data;

        return Response::json($response);
    }
}

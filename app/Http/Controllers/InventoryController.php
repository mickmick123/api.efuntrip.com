<?php

namespace App\Http\Controllers;

use App\Company;
use App\Helpers\ArrayHelper;
use App\Inventory;
use App\InventoryConsumables;
use App\InventoryConsumablesSetting;
use App\InventoryLogs;
use App\InventoryLocation;
use App\InventorySellingUnit;
use App\Location;
use App\LocationDetail;
use App\Role;
use App\User;
use App\InventoryPurchaseUnit;
use App\InventoryUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\InventoryParentCategory;
use App\InventoryCategory;
use App\InventoryAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\PageHelper;
use Auth;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function getAllInventoryCategories(Request $request)
    {
        $list = DB::table('inventory_parent_category AS ipcat')
            ->leftJoin('company AS com', 'ipcat.company_id', '=', 'com.company_id')
            ->leftJoin('inventory_category AS icat', 'ipcat.category_id', '=', 'icat.category_id')
            ->where('ipcat.company_id', '=', $request->company_id)
            ->where('ipcat.parent_id', '=', $request->category_id)
            ->orderBy('icat.name')
            ->get();

        foreach ($list as $l) {
            $l->child_count = [];
            $l->child_count = InventoryParentCategory::where('parent_id', '=', $l->category_id)->get();
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;
        return Response::json($response);
    }

    public function getTreeCategory(Request $request){
        if(in_array($request->company_id,[null,0])){
            $com = Company::orderBy('name','ASC')->get();
            foreach($com as $index=>$value){
                $com[$index] = Company::where('company_id',$value->company_id)->get();
                foreach ($com[$index] as $k=>$v) {
                    $v->company = true;
                    $v->sub_categories = InventoryParentCategory::with('inventories','subCategories')
                        ->leftJoin('inventory_category as icat', 'icat.category_id', '=', 'inventory_parent_category.category_id')
                        ->where([
                            ['inventory_parent_category.company_id', $v->company_id],
                            ['inventory_parent_category.parent_id', 0]
                        ])
                        ->orderBy('name','ASC')
                        ->get();
                }
            }
            $tree = ArrayHelper::ArrayMerge($com);
        }else{
            $tree = Company::where('company_id',$request->company_id)->get();
            foreach ($tree as $k=>$v) {
                $v->sub_categories = InventoryParentCategory::with('inventories','subCategories')
                    ->leftJoin('inventory_category as icat', 'icat.category_id', '=', 'inventory_parent_category.category_id')
                    ->where([
                        ['inventory_parent_category.company_id', $v->company_id],
                        ['inventory_parent_category.parent_id', $request->category_id]
                    ])
                    ->orderBy('name','ASC')
                    ->get();
            }
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $tree;

        return Response::json($response);
    }

    public function getTabCategory(Request $request)
    {
        $list = DB::table('inventory_parent_category AS ipcat')
            ->leftJoin('company AS com', 'ipcat.company_id', '=', 'com.company_id')
            ->leftJoin('inventory_category AS icat', 'ipcat.category_id', '=', 'icat.category_id')
            ->where('ipcat.company_id', '=', $request->company_id)
            ->where('ipcat.category_id', '=', $request->category_id)
            ->orderBy('icat.name')
            ->get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;

        return Response::json($response);
    }

    public function getCompanyCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
            'category_id' => 'required',
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $ban = [];
            $categ = [InventoryParentCategory::where('category_id', $request->category_id)->where('company_id', $request->company_id)->first()->getAllChildren()->pluck('category_id')];
            array_push($categ, [$request->category_id]);
            foreach ($categ as $i) {
                foreach ($i as $j) {
                    $ban[] = $j;
                }
            }
            $list = DB::table('inventory_parent_category AS ipcat')
                ->leftJoin('inventory_category AS icat', 'ipcat.category_id', '=', 'icat.category_id')
                ->where('ipcat.company_id', '=', $request->company_id)
                ->whereNotIn('ipcat.category_id', $ban)
                ->orderBy('icat.name')
                ->get();
            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $list;
        }
        return Response::json($response);
    }

    public function moveInventoryCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $mov = InventoryParentCategory::find($request->id);
            $mov->parent_id = $request->parent_id;
            $mov->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $mov;
        }

        return Response::json($response);
    }

    protected static function checkUnit($data, $loop = false, $withId = false){
        if($loop === true){
            $units = [];
            foreach(json_decode($data) as $k=>$v) {
                $unit = InventoryUnit::where('name',$v->unit)->get();
                if(count($unit) === 0) {
                    $addUnit = new InventoryUnit;
                    $addUnit->name = $v->unit;
                    $addUnit->created_at = strtotime("now");
                    $addUnit->updated_at = strtotime("now");
                    $addUnit->save();
                    $unit = InventoryUnit::where('name', $v->unit)->get();
                }
                $units[$k] = $unit;
                if($withId === true){
                    ArrayHelper::ArrayQueryPush($units[$k],['id','qty'],[$v->id,$v->qty]);
                }else{
                    ArrayHelper::ArrayQueryPush($units[$k],['qty'],[$v->qty]);
                }
            }
            return ArrayHelper::ArrayMerge($units);
        }else if($loop === false){
            $unit = InventoryUnit::where('name',$data)->get();
            if(count($unit) === 0) {
                $addUnit = new InventoryUnit;
                $addUnit->name = $data;
                $addUnit->created_at = strtotime("now");
                $addUnit->updated_at = strtotime("now");
                $addUnit->save();
                $unit = InventoryUnit::where('name', $data)->get();
            }
            return $unit;
        }
    }

    public function addInventory(Request $request){
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
            'category_id' => 'required',
            'name' => 'required',
            'description' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $user = auth()->user();
            $inv = new Inventory;
            $inv->company_id = $request->company_id;
            $inv->category_id = $request->category_id;
            $inv->name = $request->name;
            $inv->name_chinese = $request->name_chinese;
            if ($request->imgBase64 !== null && $request->imgBase64 !== 'undefined') {
                $inv->inventory_img = md5($request->imgBase64) . '.' . explode('.', $request->imgName)[1];
                $this->uploadCategoryAvatar($request, 'inventories/');
            }
            $inv->description = $request->description;
            $inv->specification = $request->specification;
            $inv->type = $request->type;
            $inv->created_at = strtotime("now");
            $inv->updated_at = strtotime("now");
            $inv->save();

            if($request->type === 'Property'){
                $unit = self::checkUnit($request->unit);
                $addPurchaseUnit = new InventoryPurchaseUnit;
                $addPurchaseUnit->inv_id = $inv->inventory_id;
                $addPurchaseUnit->unit_id = $unit[0]->unit_id;
                $addPurchaseUnit->qty = 0;
                $addPurchaseUnit->last_unit_id = 0;
                $addPurchaseUnit->parent_id = 0;
                $addPurchaseUnit->save();
            }elseif($request->type === 'Consumables'){
                $setting = json_decode($request->setting);
                $addConsumableSetting = new InventoryConsumablesSetting;
                $addConsumableSetting->inv_id = $inv->inventory_id;
                $addConsumableSetting->length = $setting->length_m;
                $addConsumableSetting->width = $setting->width_m;
                $addConsumableSetting->height = $setting->height_m;
                $addConsumableSetting->imported_rmb_price = $setting->imported_rmb_price;
                $addConsumableSetting->shipping_fee_per_cm = $setting->shipping_fee_per_cm;
                $addConsumableSetting->rmb_rate = $setting->rmb_rate;
                $addConsumableSetting->market_price_min = $setting->market_price_min;
                $addConsumableSetting->market_price_max = $setting->market_price_max;
                $addConsumableSetting->advised_sale_price = $setting->advised_sale_price;
                $addConsumableSetting->actual_sale_price = $setting->actual_sale_price;
                $addConsumableSetting->id = $setting->id;
                $addConsumableSetting->expiration_date = $setting->expiration_date;
                $addConsumableSetting->remarks = $setting->remarks;
                $addConsumableSetting->save();

                $purchase = self::checkUnit($request->purchase, true);
                $last_unit = collect(json_decode($request->purchase));
                foreach($purchase as $k=>$v){
                    $addPurchaseUnit = new InventoryPurchaseUnit;
                    $addPurchaseUnit->inv_id = $inv->inventory_id;
                    $addPurchaseUnit->unit_id = $v->unit_id;
                    $addPurchaseUnit->qty = $v->qty;
                    if($k+1 === count($purchase)){
                        $unit = self::checkUnit($last_unit[count($last_unit)-1]->last_unit_id);
                        $addPurchaseUnit->last_unit_id = $unit[0]->unit_id;
                        $addPurchaseUnit->parent_id = $k === 0 ? $k : $purchase[$k-1]->unit_id;;
                    }elseif($k === 0){
                        $addPurchaseUnit->last_unit_id = 0;
                        $addPurchaseUnit->parent_id = 0;
                    }else{
                        $addPurchaseUnit->last_unit_id = 0;
                        $addPurchaseUnit->parent_id = $purchase[$k-1]->unit_id;
                    }
                    $addPurchaseUnit->save();
                }
                $selling = self::checkUnit($request->selling, true);
                foreach($selling as $k=>$v){
                    $addSellingUnit = new InventorySellingUnit;
                    $addSellingUnit->inv_id = $inv->inventory_id;
                    $addSellingUnit->unit_id = $v->unit_id;
                    $addSellingUnit->qty = $v->qty;
                    $addSellingUnit->save();
                }
            }

            $ilog = new InventoryLogs;
            $ilog->inventory_id = $inv->inventory_id;
            $ilog->type = 'Stored';
            $ilog->reason = $user->first_name.' added '.$request->name;
            $ilog->created_by = $user->id;
            $ilog->created_at = strtotime("now");
            $ilog->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = 'Item Profile has been Created!';
        }
        return Response::json($response);
    }

    public function addInventoryCategory(Request $request){
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
            'parent_id' => 'required',
            'name' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $categ = new InventoryCategory();
            $categ->name = $request->name;
            $categ->name_chinese = $request->name_chinese;
            $categ->created_at = strtotime("now");
            $categ->updated_at = strtotime("now");
            $categ->save();

            $parentExist = InventoryParentCategory::where([
                ['company_id','=',$request->company_id],
                ['category_id','=',$categ->category_id]
            ])->get();

            if(count($parentExist) === 0) {
                $parentCateg = new InventoryParentCategory();
                $parentCateg->company_id = $request->company_id;
                $parentCateg->category_id = $categ->category_id;
                $parentCateg->parent_id = $request->parent_id;
                $parentCateg->save();
            }

            $treeKey = InventoryParentCategory::where('category_id',$categ->category_id)->get();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $treeKey;
        }
        return Response::json($response);
    }

    public function editInventory(Request $request){
        $validator = Validator::make($request->all(), [
            'inventory_id' => 'required',
            'description' => 'required',
            'specification' => 'nullable',
            'type' => 'required',
            'unit_id' => 'required',
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $now = strtotime("now");
            if(!is_numeric($request->unit_id)){
                $u = new InventoryUnit;
                $u->name = $request->unit_id;
                $u->created_at = $now;
                $u->updated_at = $now;
                $u->save();
            }else{
                $u = InventoryUnit::where("unit_id", $request->unit_id)->first();
            }

            $pUnit = InventoryPurchaseUnit::find($request->parent_unit_id);
            $pUnit->unit_id = $u->unit_id;
            $pUnit->save();

            $inv = Inventory::find($request->inventory_id);
            $inv->description = $request->description;
            $inv->type = $request->type;
            $inv->specification = $request->specification;
            $inv->updated_at = strtotime("now");
            $inv->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
        }
        return Response::json($response);
    }


    public function editInventoryCategory(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'name' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $categ = InventoryCategory::find($request->category_id);
            $categ->name = $request->name;
            $categ->name_chinese = $request->name_chinese;
            $categ->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = explode('.', $request->imgName);
        }

        return Response::json($response);
    }

    public function deleteInventory(Request $request){
        $validator = Validator::make($request->all(), [
            'inventory_id' => 'required|exists:inventory',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            //Logs
            $user = Auth::user();
            $reason = "$user->first_name deleted the $request->path | $request->item_name";
            self::saveLogs($request->inventory_id, 'Item Profile Deleted', $reason);

            $inv = Inventory::find($request->inventory_id);
            $inv->status = 0;
            $inv->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = [];
        }
        return Response::json($response);
    }

    public function getCompanyCategoryInventory(Request $request){
        $com = DB::table('company')
            ->where('company_id',$request->company_id)->get();

        $ipcat = DB::table('inventory_parent_category AS ipcat')
            ->leftJoin('inventory_category AS icat','ipcat.category_id','=','icat.category_id')
            ->where('ipcat.company_id',$request->company_id)->get();

        $inv = DB::table('inventory')
            ->where('company_id',$request->company_id)->get();

        $list = ['company'=>$com,'companyCount'=>count($com),'category'=>$ipcat,'categoryCount'=>count($ipcat),'inventory'=>$inv,'inventoryCount'=>count($inv)];

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;

        return Response::json($response);
    }

    public function getCategoryInventory(Request $request){
        $categ = InventoryParentCategory::where('category_id', $request->category_id)->where('company_id', $request->company_id)->first()->getAllChildren()->pluck('category_id');
        $categoryList = DB::table('inventory_category')
            ->whereIn('category_id',$categ)
            ->orderBy('name')
            ->get();
        $inventoryList = DB::table('inventory')
            ->where('company_id', $request->company_id)
            ->where(function ($q) use ($categ, $request) {
                $q->whereIn('category_id', $categ);
                $q->orwhere('category_id', $request->category_id);
            })->orderBy('name')
            ->get();

        $list = ['category'=>$categoryList,'categoryCount'=>count($categoryList),'inventory'=>$inventoryList,'inventoryCount'=>count($inventoryList)];
        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;

        return Response::json($response);
    }

    public function deleteInventoryCategory(Request $request){
        $categ = InventoryParentCategory::where('category_id', $request->category_id)->where('company_id', $request->company_id)->first()->getAllChildren()->pluck('category_id');

        InventoryParentCategory::where('company_id', $request->company_id)
            ->where(function ($q) use ($categ, $request) {
                $q->whereIn('category_id', $categ);
                $q->orwhere('category_id', $request->category_id);
            })->delete();

        InventoryCategory::where(function ($q) use ($categ, $request) {
                $q->whereIn('category_id', $categ);
                $q->orwhere('category_id', $request->category_id);
            })->delete();

        $invId = Inventory::where('company_id', $request->company_id)
            ->where(function ($q) use ($categ, $request) {
                $q->whereIn('category_id', $categ);
                $q->orwhere('category_id', $request->category_id);
            })->pluck('inventory_id');

        Inventory::where('company_id', $request->company_id)
            ->where(function ($q) use ($categ, $request) {
                $q->whereIn('category_id', $categ);
                $q->orwhere('category_id', $request->category_id);
            })->delete();

        InventoryAssigned::whereIn('inventory_id',$invId)->delete();

        InventoryConsumables::whereIn('inventory_id',$invId)->delete();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = 'Category and Inventory has been Deleted!';
        return Response::json($response);
    }

    public function test(Request $request){
        $tree = Inventory::where('inventory_id',$request->inventory_id)->get();
        InventoryPurchaseUnit::$inventory_id = $request->inventory_id;
        foreach ($tree as $k=>$v) {
            $v->sub_categories = InventoryPurchaseUnit::with('subCategories')
                ->leftJoin('inventory_unit as iunit', 'iunit.unit_id', '=', 'inventory_purchase_unit.unit_id')
                ->where([
                    ['inventory_purchase_unit.inv_id', $request->inventory_id],
                    ['inventory_purchase_unit.parent_id', $request->unit_id]
                ])
                ->get();
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $tree;
        return Response::json($response);
    }

    public static function contentToMinPurchased($inventory_id,$unit_id,$qty){
        $ids = [];
        $names = [];
        $values = [];
        $result = $qty;

        $record = 0;
        $ctr = 0;
        $data = InventoryPurchaseUnit::leftJoin('inventory_unit AS iu','inventory_purchase_unit.unit_id','iu.unit_id')
            ->where('inventory_purchase_unit.inv_id',$inventory_id)->get();
        for($i=0;$i<count($data);$i++){
            if($data[$i]->unit_id == $unit_id){
                $record = 1;
            }
            if($record === 1){
                array_push($ids,$data[$i]->unit_id);
                array_push($names,$data[$i]->name);
                array_push($values,$data[$i]->content);
                if($values[$ctr] !== 0){
                    $result = $result * $values[$ctr];
                }
                $ctr++;
            }
        }
        return $result;
    }

    public function uploadCategoryAvatar($data,$folder) {
        $img64 = $data->imgBase64;

        list($type, $img64) = explode(';', $img64);
        list(, $img64) = explode(',', $img64);

        if($img64!=""){ // storing image in storage/app/public Folder
            \Storage::disk('public')->put($folder . md5($data->imgBase64) . '.' . explode('.', $data->imgName)[1], base64_decode($img64));
        }
    }

    public function getNewlyAdded(Request $request, $perPage = 10)
    {
        $sort = $request->sort;
        $search = $request->search;

        $newlyAdded = Inventory::select(['inventory.*',
            DB::raw("(inventory.name) AS name,
                (co.name) AS company,
                CASE WHEN inventory.type!='Consumables' THEN
                    (SELECT COUNT(id) FROM inventory_assigned a WHERE a.inventory_id = inventory.inventory_id AND a.status !=3)
                END AS total_asset"),
            'datetime' => function ($query) {
                $query->select(DB::raw("FROM_UNIXTIME(created_at, '%m/%d/%Y %H:%i:%s') AS datatime"))
                    ->from('inventory_logs')
                    ->whereColumn('inventory_id', 'inventory.inventory_id')
                    ->orderBy('created_at','DESC')
                    ->limit(1);
            },
            'action_done' => function ($query) {
                $query->select('type')
                    ->from('inventory_logs')
                    ->whereColumn('inventory_id', 'inventory.inventory_id')
                    ->orderBy('created_at','DESC')
                    ->limit(1);
            },
            'operator' => function ($query) {
                $query->select('users.first_name')
                    ->from('inventory_logs AS ilog')
                    ->leftJoin('users','ilog.created_by','users.id')
                    ->whereColumn('ilog.inventory_id', 'inventory.inventory_id')
                    ->orderBy('ilog.created_at','DESC')
                    ->limit(1);
            },
        ])
            //->leftJoin("inventory_unit as u", "inventory.unit_id", "u.unit_id")
            ->leftjoin('company as co', 'inventory.company_id', 'co.company_id')
            ->orHaving('datetime', 'LIKE','%'.$search.'%')
            ->orHaving('total_asset', 'LIKE','%'.$search.'%')
            ->orHaving('name','LIKE', '%'.$search.'%')
            ->orHaving('company','LIKE', '%'.$search.'%')
            ->orHaving('action_done', 'LIKE','%'.$search.'%')
            ->orHaving('operator', 'LIKE','%'.$search.'%')
            ->when($sort != '', function ($q) use($sort){
                $sort = explode('-' , $sort);
                return $q->orderBy($sort[0], $sort[1]);
            })->paginate($perPage);

        foreach($newlyAdded as $n){
            if($n->type=="Consumables") {
                $units = DB::table('inventory_purchase_unit as su')->select(DB::raw('su.unit_id as unitId, u.name as unit'))
                    ->leftJoin("inventory_unit as u", "su.unit_id", "u.unit_id")
                    ->where("inv_id", $n->inventory_id)->orderBy("id", "ASC")->get();

                $this->getUnitAndSet($n, $units);
//                $sell = InventorySellingUnit::where("inv_id", $n->inventory_id)->orderBy("id", "ASC")->get('id');
//
//                $dUnit = DB::table('inventory_consumables as c')
//                    ->select(DB::raw('c.*, iu.name as unit, iu1.name as sUnit, su.qty as sQty'))
//                    ->leftJoin("inventory_unit as iu", "c.unit_id", "iu.unit_id")
//                    ->leftJoin("inventory_selling_unit as su", "c.selling_id", "su.id")
//                    ->leftJoin("inventory_unit as iu1", "su.unit_id", "iu1.unit_id")
//                    ->where("inventory_id", $n->inventory_id)->get();
//                $set = 0; $qty = []; $sellQty = []; $i=0;
//                foreach ($units as $u) {
//                    $qty[$u->unitId] = 0;
//                    $rUnit[$i] = "";
//                    $i++;
//                }
//                foreach ($sell as $s) {
//                    $sellQty[$s->id] = 0;
//                    $rSet[$s->id] = "";
//                }
//                $n->rUnit = 0;
//                $n->rSet = 0;
//                $n->toolTipSet = "";
//                foreach ($dUnit as $p) {
//                    if($i==0 && $p->type == "Purchased") {
//                        $qty[$p->unit_id] += $p->qty;
//                    }
//                    if($i!=0) {
//                        if ($p->type == "Purchased") {
//                            $qty[$p->unit_id] += $p->qty;
//                        }
//                        if ($p->type == "Consumed" || $p->type == "Wasted" || $p->type == "Converted") {
//                            $qty[$p->unit_id] -= $p->qty;
//                        }
//
//                        if ($p->type == "Converted") {
//                            $cQty = self::convertToSet($n->inventory_id, $p->unit_id, $p->selling_id, $p->qty);
//                            $sellQty[$p->selling_id] += $cQty;
//                            $set += $cQty;
//                        }
//                        if ($p->type == "Sold") {
//                            $cQty = self::convertToSet($n->inventory_id, $p->unit_id, $p->selling_id, $p->qty);
//                            $p->qtySet = $cQty;
//                            $sellQty[$p->selling_id] -= $cQty;
//                            $set -= $cQty;
//                        }
//
//                        $i++;
//                    }
//
//                    $j=0;
//                    foreach ($units as $u) {
//                        if($p->unit_id == $u->unitId) {
//                            $rUnit[$j] = $qty[$p->unit_id]." $p->unit";
//                        }
//                        $j++;
//                    }
//                    foreach ($sell as $s) {
//                        if (($p->type == "Converted" || $p->type == "Sold")) {
//                            $rSet[$p->selling_id] = $sellQty[$p->selling_id]." Set($p->sQty $p->sUnit)";
//                        }
//                    }
//
//                    $n->rUnit = trim(implode(" ", $rUnit));
//                    $n->rSet = $set;
//                    $n->toolTipSet = trim(implode(" ",$rSet));
//
//                    $qty[$p->unit_id] = $qty[$p->unit_id];
//                    $set = $set;
//                    if ($p->type == "Converted" || $p->type == "Sold") {
//                        $sellQty[$p->selling_id] = $sellQty[$p->selling_id];
//                    }
//                }
            }
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $newlyAdded;

        return Response::json($response);
    }

    public function getParents($where, $field, $inArray){
        $category = DB::table('inventory_parent_category AS pc')
            ->select(DB::raw("pc.*,com.name as company,c.name, c.name_chinese"))
            ->leftJoin('company AS com', 'pc.company_id', '=', 'com.company_id')
            ->leftJoin('inventory_category AS c', 'pc.category_id', '=', 'c.category_id')
            ->where($where);
        if(!empty($field) && count($inArray)>0){
            $category = $category->whereIn($field, $inArray)
                    ->orderBy('c.name')
                    ->get();
        }else{
            $category = $category->get();
        }
        foreach ($category as $d) {
            //$d->name = $d->name.": ".$d->category_id;
            $nParent = InventoryParentCategory::where('inventory_parent_category.category_id',$d->category_id)
                ->where('inventory_parent_category.company_id',$d->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            foreach($nParent as $np){
                $tree = $np->parents->reverse();
                $d->path = $d->company;
                $j=0;
                foreach($tree as $t){
                    $d->x[$j] = $t->name;
                    $d->path = $d->company." > ".implode(" > ", $d->x);
                    $j++;
                }
            }
            $d->isCompany = false;
        }
        return $category;
    }

    public function list(Request $request)
    {
        $name = $request->input("q", "");
        $co_id = intval($request->input("company_id", 0));
        $ca_id = intval($request->input("category_id", 0));
        $page = intval($request->input("page", 1));
        $pageSize = intval($request->input("limit", 20));
        $sort = $request->input("sort", "");
        if(empty($sort))
        {
            $sort_field = "inventory_id";
            $sort_order = "desc";
        }else{
            $x = explode("-",$sort);
            $sort_field = $x[0];
            $sort_order = $x[1];
        }

        $nFilter1 = array();
        $nFilter2 = array();
        if ($ca_id != 0)
        {
            $nFilter1[] = ["category_id", $ca_id];
        }
        $category_ids =  InventoryCategory::where($nFilter1)->pluck('category_id')->toArray();
        if ($name != "" && $ca_id ==0)
        {
            $nFilter2[] = ["name", $name];
            $category_ids =  InventoryCategory::where($nFilter2)->pluck('category_id')->toArray();

        }
        $category_ids1 = array();
        $category_ids2 = array();
        if($name != ""){
            $xx = array();
            $xxx = array();
            $limit = PHP_INT_MAX;
            if($name != "" && $co_id !=0){
                $xx[] = ["pc.company_id", $co_id];
                $limit = 1;
            }
            $cat = DB::table('inventory_parent_category AS pc')->select("c.category_id", "pc.company_id")
                ->leftJoin('company AS co', 'pc.company_id', '=', 'co.company_id')
                ->leftJoin('inventory_category AS c', 'pc.category_id', '=', 'c.category_id')
                ->where("c.name", $name);
            if($ca_id != ""){
                $xxx = array($ca_id);
                $cat = $cat->wherein("pc.parent_id", $xxx)
                    ->limit($limit)->get();
            }else{
                $cat = $cat->where($xx)
                    ->limit($limit)->get();
            }

            if(count($cat) > 0){
                foreach ($cat as $c){
                    $category_ids2[] = InventoryParentCategory::where('category_id', $c->category_id)->where('company_id', $c->company_id)->first()->getAllChildren()->pluck('category_id');
                }
                foreach($category_ids2 as $i){
                    foreach($i as $j){
                        $category_ids1[] = $j;
                    }
                }
            }
            if(count($category_ids1)<=0 && count($cat)>0){
                $category_ids1 = array($cat[0]->category_id);
            }
        }

        $filter = array();
        if ($co_id != 0)
        {
            $filter[] = ["inventory.company_id", $co_id];
        }

        $cats = InventoryParentCategory::whereIn('category_id', $category_ids)->get();

        $items = [];
        foreach($cats as $c){
            $items[] = InventoryParentCategory::where('category_id', $c->category_id)->where('company_id', $c->company_id)->first()->getAllChildren()->pluck('category_id');
        }
        $items1 = [];
        foreach($items as $i){
            foreach($i as $j){
                $items1[] = $j;
            }
        }

        $filter2 = array();
        $filter3 = array();
        $filter4 = array();
        $filter5 = array();
        if($name !="") {
            if(is_numeric($name)) {
                $filter2[] = ["inventory.inventory_id", "LIKE", "%" . $name . "%"];
            }
            if($name=="Property"||$name=="Consumables") {
                $filter3[] = ["inventory.type", $name];
            }
            if(!is_numeric($name)) {
                $filter4[] = ["inventory.name", "LIKE", "%".$name."%"];
                $filter5[] = ["inventory.description", "LIKE", "%" . $name . "%"];
            }
        }

        $sql = Inventory::where($filter)->orwhere($filter2)->orwhere($filter3)->orwhere($filter4)->orwhere($filter5)->pluck('category_id')->toArray();

        if(count($sql)==0){
            $filter2 = array();
            $filter3 = array();
            $filter4 = array();
            $filter5 = array();
        }

        $items2 = array_merge(array($ca_id), $items1, $sql);

        if(count($items2)>0){
            $item_found = $items2;
        }else{
            $item_found = $category_ids;
        }

        $page_obj = new PageHelper($page, $pageSize);
        if (empty($page_obj)) {
            return array();
        }

        $count = DB::table('inventory')
            ->where($filter)
            ->where(function ($sql) use ($filter2,$filter3,$filter4,$filter5){
                $sql->where($filter2)
                    ->orwhere($filter3)
                    ->orwhere($filter4)
                    ->orwhere($filter5);
            })
            ->whereIn("category_id", $item_found)
            ->orwherein("category_id", $category_ids1)
            ->where("status", 1)
            ->count();

        $arrFilter = array();
        if($ca_id !== 0 || $name == ""){
            $arrFilter[] = ['pc.parent_id', '=', $ca_id];
        }
        $categoryIds = array();
        $nFilter = array();
        if($name != ""){
            $nFilter[] = ["name", $name];
            $categoryIds = InventoryCategory::where($nFilter)->pluck('category_id');

            $arrFilter[] = ['c.name', $name];
        }

        $category = Company::all();
        foreach ($category as $d) {
            $d->isCompany = true;
        }
        if(count($categoryIds)>0 && $co_id !=0){
            $category = $this->getParents(array(['pc.company_id', '=', $co_id]), "pc.parent_id", $categoryIds);
        }
        if(count($categoryIds)==0 && $co_id !=0){
            $arrFilter[] = ['pc.company_id', '=', $co_id];
            $category = $this->getParents($arrFilter, "", array());
        }
        if($name != "" || ($name !="" && $ca_id !=0)) {
            $items = array();
            $field = "";
            if($ca_id !=0) {
                $field = "pc.category_id";
                $items = InventoryParentCategory::where('category_id', $ca_id)->where('company_id', InventoryParentCategory::where('category_id', $ca_id)->first()->company_id)->first()->getAllChildren()->pluck('category_id');
            }
            $filterX = array();
            if($co_id!=0){
                $filterX[] = ['pc.company_id', '=', $co_id];
            }
            $filterX[] = ["c.name", $name];
            $category = $this->getParents($filterX, $field, $items);
        }

        if ($count==0)
        {
            $response['status'] = 'No results found.';
            $response['code'] = 404;
            $response['category'] = $category;
            $response['data'] = '';
            return Response::json($response);
        }

        $page_obj->set_count($count);
        if (empty($count)) {
            return array();
        }
        $limit = $page_obj->page_size;
        $page = $page_obj->curr_page;

        $list = DB::table('inventory')
            ->select(DB::raw('
                co.name as company_name, inventory.*,
                CASE WHEN inventory.type!="Consumables" THEN
                    (SELECT COUNT(id) FROM inventory_assigned a WHERE a.inventory_id = inventory.inventory_id AND a.status !=3)
                END AS qty
            '))
            ->leftjoin('company as co', 'inventory.company_id', 'co.company_id')
            ->where($filter)
            ->where(function ($sql) use ($filter2,$filter3,$filter4,$filter5){
                $sql->where($filter2)
                    ->orwhere($filter3)
                    ->orwhere($filter4)
                    ->orwhere($filter5);
            })
            ->whereIn("category_id", $item_found)
            ->orwherein("category_id", $category_ids1)
            ->where("status", 1)
            ->groupBy("inventory_id")
            ->orderBy($sort_field,$sort_order)
            ->limit($limit)->offset(($page - 1) * $limit)->get()->toArray();
        $i=0;
        foreach($list as $n){
            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            $n->created_at = gmdate("F j, Y", $n->created_at);
            $n->updated_at = gmdate("F j, Y", $n->updated_at);
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $n->item_name = $np->name;
                if(count($tree)==0){
                    $n->path = $np->name;
                }
                $j=0;
                foreach($tree as $t){
                    $n->x[$j] = $t->name;
                    $n->asset_name = implode(" > ", $n->x);
                    $n->path = implode(" > ", $n->x)." > ".$n->item_name;
                    $j++;
                }
            }
            $units = DB::table('inventory_purchase_unit as su')->select(DB::raw('su.unit_id as unitId, u.name as unit'))
                ->leftJoin("inventory_unit as u", "su.unit_id", "u.unit_id")
                ->where("inv_id", $n->inventory_id)->orderBy("id", "ASC")->get();
            $n->unit = $units[0]->unit;

            if($n->type=="Consumables") {
                $this->getUnitAndSet($n, $units);
            }

            $i++;
        }

        $data = array(
            "totalNum" => $page_obj->total_num,
            "currPage" => $page_obj->curr_page,
            "list" => $list,
            "pageSize" => $page_obj->page_size,
            "totalPage" => $page_obj->total_page,
        );

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['category'] = $category;
        $response['data'] = $data;
        return Response::json($response);
    }

    public function show($id){
        $list = DB::table('inventory as i')
            ->select(DB::raw('co.name as company_name, i.*, cs.*'))
            ->leftjoin('company as co', 'i.company_id', 'co.company_id')
            ->leftJoin("inventory_consumables_setting as cs", "i.inventory_id", "cs.inv_id")
            ->where("inventory_id", $id)->get();
        $unitList = DB::table('inventory_purchase_unit as su')->select(DB::raw('su.unit_id as unitId, u.name as unit'))
            ->leftJoin("inventory_unit as u", "su.unit_id", "u.unit_id")
            ->where("inv_id", $id)->orderBy("id", "ASC")->get();
        foreach($list as $n){
            $n->units = Inventory::with('units')->where("inventory_id", $n->inventory_id)->first()->units;
            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            $n->created_at = gmdate("F j, Y", $n->created_at);
            $n->updated_at = gmdate("F j, Y", $n->updated_at);
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $n->item_name = $np->name;
                if(count($tree)==0){
                    $n->path = $np->name;
                }
                $j=0;
                foreach($tree as $t){
                    $n->x[$j] = $t->name;
                    $n->asset_name = implode(" > ", $n->x);
                    $n->path = implode(" > ", $n->x)." > ".$n->item_name;
                    $j++;
                }
            }
            if($n->type == "Consumables") {
                $i=0;
                foreach ($n->units as $u){
                    $unitA[$i+1] = "1 $u->name = $u->qty";
                    if($i!=0){
                        $unitB[$i] = $u->name;
                    }
                    if($i == count($n->units)-1){
                        $unitB[count($n->units)] = InventoryUnit::where("unit_id", $u->last_unit_id)->first()->name;
                    }
                    $i++;
                }
                foreach ($unitA as $k => $v) {
                    $units[] = $v.$unitB[$k];
                }
                $n->pUnit = implode(", ", $units);

                $selling = Inventory::with('selling')->where("inventory_id", $n->inventory_id)->first()->selling;
                foreach ($selling as $s){
                    $sell[] = "$s->qty $s->name = 1 Set";
                }

                $n->sUnit = implode(", ", $sell);

                $n->length = $n->length?$n->length:0;
                $n->width = $n->width?$n->width:0;
                $n->height = $n->height?$n->height:0;
                $n->imported_rmb_price = $n->imported_rmb_price?$n->imported_rmb_price:0;
                $n->shipping_fee_per_cm = $n->shipping_fee_per_cm?$n->shipping_fee_per_cm:0;
                $n->rmb_rate = $n->rmb_rate?$n->rmb_rate:0;
                $n->market_price_min = $n->market_price_min?$n->market_price_min:0;
                $n->market_price_max = $n->market_price_max?$n->market_price_max:0;
                $n->advised_sale_price = $n->advised_sale_price?$n->advised_sale_price:0;
                $n->actual_sale_price = $n->actual_sale_price?$n->actual_sale_price:0;

                $n->expiration_date = Carbon::parse($n->expiration_date)->format('F j, Y');
                $n->item_volume = $n->length*$n->width*$n->height;
                $n->import_cost = $n->imported_rmb_price * $n->rmb_rate + $n->shipping_fee_per_cm;
                $n->profit_min = $n->market_price_min - $n->import_cost;
                $n->profit_max = $n->market_price_max - $n->import_cost;

                $n->profit_rate_min = 0;
                if($n->market_price_min!=0){
                    $n->profit_rate_min = ( $n->profit_min / $n->market_price_min ) * 100;
                }

                $n->profit_rate_max = 0;
                if($n->market_price_max!=0){
                    $n->profit_rate_max = ( $n->profit_max / $n->market_price_max ) * 100;
                }

                $n->advised_profit = $n->advised_sale_price - $n->import_cost;

                $n->adivsed_profit_rate = 0;
                if($n->advised_sale_price!=0){
                    $n->adivsed_profit_rate = ( $n->advised_profit / $n->advised_sale_price ) * 100;
                }

                $n->whole_sale_price = $n->import_cost / 0.88;

                $this->getUnitAndSet($n, $unitList);
            }

        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;
        return Response::json($response);
    }

    public function getUnitAndSet($n, $units){
        $sell = InventorySellingUnit::where("inv_id", $n->inventory_id)->orderBy("id", "ASC")->get('id');
        $dUnit = DB::table('inventory_consumables as c')
            ->select(DB::raw('c.*, iu.name as unit, iu1.name as sUnit, su.qty as sQty'))
            ->leftJoin("inventory_unit as iu", "c.unit_id", "iu.unit_id")
            ->leftJoin("inventory_selling_unit as su", "c.selling_id", "su.id")
            ->leftJoin("inventory_unit as iu1", "su.unit_id", "iu1.unit_id")
            ->where("inventory_id", $n->inventory_id)->get();
        $i=0; $set = 0; $qty = []; $sellQty = []; $unit = []; $sold = 0; $soldQty = [];
        foreach ($units as $u) {
            //$qty[$u->unitId] = 0;
            //$wasted[$u->unitId] = 0;
            //$consumed[$u->unitId] = 0;
            //$rUnit[$i] = "";
            //$rWasted[$i] = "";
            //$rConsumed[$i] = "";
            $unit[$i] = $u->unit;
            $i++;
        }
        $qty = 0;
        $wasted = 0;
        $consumed = 0;
        $n->unit = implode(" / ", $unit);
        foreach ($sell as $s) {
            $sellQty[$s->id] = 0;
            $rSet[$s->id] = "";
            $soldQty[$s->id] = 0;
            $rSold[$s->id] = "";
        }
        $n->rUnit = 0;
        $n->rSet = 0;
        $n->toolTipSet = "";
        foreach ($dUnit as $p) {
            if($i==0 && $p->type == "Purchased") {
                //$qty[$p->unit_id] += $p->qty;
                $qty += $p->qty;
            }
            if($i!=0) {
                if ($p->type == "Purchased") {
                    //$qty[$p->unit_id] += $p->qty;
                    $qty += $p->qty;
                }
                if ($p->type == "Consumed" || $p->type == "Wasted" || $p->type == "Converted") {
                    //$qty[$p->unit_id] -= $p->qty;
                    $qty -= $p->qty;
                }
                if ($p->type == "Consumed") {
                    //$consumed[$p->unit_id] += $p->qty;
                    $consumed += $p->qty;
                }
                if ($p->type == "Wasted") {
                    //$wasted[$p->unit_id] += $p->qty;
                    $wasted += $p->qty;
                }
                /*
                if ($p->type == "Converted") {
                    $cQty = self::convertToSet($n->inventory_id, $p->unit_id, $p->selling_id, $p->qty);
                    $sellQty[$p->selling_id] += $cQty;
                    $set += $cQty;
                }
                if ($p->type == "Sold") {
                    $cQty = self::convertToSet($n->inventory_id, $p->unit_id, $p->selling_id, $p->qty);
                    $sellQty[$p->selling_id] -= $cQty;
                    $set -= $cQty;

                    $soldQty[$p->selling_id] += $cQty;
                    $sold += $cQty;
                }
                */
                $i++;
            }
            /*
            $j=0;
            foreach ($units as $u) {
                if($p->unit_id == $u->unitId) {
                    if($qty[$p->unit_id]>0){
                        $rUnit[$j] = $qty[$p->unit_id]." $p->unit";
                    }
                    if($wasted[$p->unit_id]>0){
                        $rWasted[$j] = $wasted[$p->unit_id]." $p->unit";
                    }
                    if($consumed[$p->unit_id]>0){
                        $rConsumed[$j] = $consumed[$p->unit_id]." $p->unit";
                    }
                }
                $j++;
            }

            foreach ($sell as $s) {
                if (($p->type == "Converted" || $p->type == "Sold")) {
                    $rSet[$p->selling_id] = $sellQty[$p->selling_id]." Set($p->sQty $p->sUnit)";
                }
                if($p->type == "Sold"){
                    $rSold[$p->selling_id] = $soldQty[$p->selling_id]." Set($p->sQty $p->sUnit)";
                }
            }
            */
            $n->rUnit = self::unitFormatting($n->inventory_id, $qty);
            $n->rWasted = self::unitFormatting($n->inventory_id, $wasted);
            $n->rConsumed = self::unitFormatting($n->inventory_id, $consumed);
            //$n->rUnit = trim(implode(" ", $rUnit));
            //$n->rWasted = trim(implode(" ", $rWasted));
            //$n->rConsumed = trim(implode(" ", $rConsumed));
            //$n->rSet = $set;
            //$n->toolTipSet = trim(implode(" ",$rSet));
            //$n->sold = $sold;
            //$n->toolTipSold = trim(implode(" ",$rSold));
            $qty = $qty;
            $wasted = $wasted;
            $consumed = $consumed;
            //$set = $set;
            /*
            if ($p->type == "Converted" || $p->type == "Sold") {
                $sellQty[$p->selling_id] = $sellQty[$p->selling_id];
            }
            if($p->type == "Sold"){
                $soldQty[$p->selling_id] = $soldQty[$p->selling_id];
                $sold = $sold;
            }
            */
        }
    }

    public function listAssigned(Request $request)
    {
        $inventory_id = intval($request->input("id"));

        $data = DB::table('inventory_assigned as a')
            ->select(DB::raw('a.*, i.type as inventory_type, CONCAT(u.first_name," ",u.last_name) as assigned_to, u.id as user_id,
                                l.location as location_site, ld.location_detail, l.id as loc_site_id, CASE WHEN a.location_id =0 THEN null ELSE a.location_id END AS loc_detail_id,
                                l1.location as storage_site, ld1.location_detail as storage_detail, l1.id as s_site_id, ld1.id as s_detail_id'))
            ->leftjoin('inventory as i', 'a.inventory_id', 'i.inventory_id')
            ->leftJoin("users as u", "a.assigned_to", "u.id")
            ->leftJoin("ref_location_detail as ld","a.location_id","ld.id")
            ->leftJoin("ref_location as l","ld.loc_id","l.id")
            ->leftJoin("ref_location_detail as ld1","a.storage_id","ld1.id")
            ->leftJoin("ref_location as l1","ld1.loc_id","l1.id")
            ->where("a.inventory_id", "=", $inventory_id)
            ->whereIn("a.status", [1,2])
            ->orderBy('a.status','DESC')
            ->orderBy('a.created_at','DESC')
            ->get();
        $x = 1;
        foreach ($data as $n) {
            if($n->status === 1) {
                $status = "Assigned";
            }else
                if($n->status === 2) {
                    $status = "Storage";
                }
            $n->status = $status;
            $n->count = $x;
            $x++;
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $data;
        return Response::json($response);
    }

    public function editAssignedItem(Request $request)
    {
        $rules = [
            'inventory_id' => 'required',
            'user_id' => 'required',
            'loc_site_id' => 'required',
            'loc_detail_id' => 'required',
            'model' => 'nullable',
            'serial' => 'nullable'
        ];
        $messages = [
            'required' => 'This field is required.'
        ];
        $validator = Validator::make($request->all(), $rules, $messages);

        $response = array();
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $user = Auth::user();

            $location_detailId = self::location($request->loc_site_id, $request->loc_detail_id);

            $inv = InventoryAssigned::find($request->id);
            $inv->assigned_to = $request->user_id;
            $inv->location_id = $location_detailId;
            if($request->model !== null) {
                $inv->model = $request->model;
            }
            if($request->serial !== null) {
                $inv->serial = $request->serial;
            }
            $inv->updated_by = $user->id;
            $inv->updated_at = strtotime("now");
            $inv->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $inv;
        }

        return Response::json($response);
    }

    public function retrieveInventory(Request $request) {
        $validator = Validator::make($request->all(), [
            'assigned_id' => 'required',
            'inventory_id' => 'required',
            'loc_site_id' => 'required',
            'loc_detail_id' => 'required'
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $now = strtotime("now");
            $items = InventoryAssigned::where("id", $request->assigned_id)->first();
            $location_detailId = self::location($request->loc_site_id, $request->loc_detail_id, 2);

            //Logs
            $user=Auth::user();
            $reason = "$user->first_name retrieved 1 $request->item_name from $request->receive_from with serial number $items->serial.";
            self::saveLogs($request->inventory_id,"Retrieved", $reason);

            $iUpd = InventoryAssigned::find($request->assigned_id);
            $iUpd->assigned_to = 0;
            $iUpd->storage_id = $location_detailId;
            $iUpd->status = 2;
            $iUpd->updated_at = $now;
            $iUpd->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = [];
        }

        return Response::json($response);
    }

    public function assignInventory(Request $request){
        $rules = [
            'inventory_id' => 'required',
            'assigned_id' => 'required',
            'user_id' => 'required',
            'loc_site_id' => 'required',
            'loc_detail_id' => 'required',
            'model' => 'nullable',
            'serial' => 'nullable'
        ];
        $messages = [
            'required' => 'This field is required.'
        ];
        $validator = Validator::make($request->all(), $rules, $messages);

        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            if(!is_numeric($request->loc_site_id)){
                $location = $request->loc_site_id;
                $location_detail = $request->loc_detail_id;
            }else{
                $location = Location::where("id", $request->loc_site_id)->first()->location;
                if(!is_numeric($request->loc_detail_id)){
                    $location_detail = $request->loc_detail_id;
                }else{
                    $location_detail = LocationDetail::where("id", $request->loc_detail_id)->first()->location_detail;
                }
            }
            $location_detailId = self::location($request->loc_site_id, $request->loc_detail_id);

            $data = InventoryAssigned::find($request->assigned_id);
            $data->inventory_id = $request->inventory_id;
            $data->assigned_to = $request->user_id;
            $data->location_id = $location_detailId;
            $data->status = 1;
            $data->model = $request->model;
            $data->serial = $request->serial;
            $data->updated_at = strtotime("now");
            $data->save();

            //Logs
            $name = User::where("id", $request->user_id)->first();
            $user = Auth::user();
            $reason = "$user->first_name assigned 1 $request->item_name to $name->first_name $name->last_name ($location, $location_detail)";
            self::saveLogs($request->inventory_id,"Assigned", $reason);

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = [];
        }
        return Response::json($response);
    }

    public function getConsumed(Request $request)
    {
        // list of employees request and department orders goes here
    }

    public function getNewlyModified()
    {
        $list = DB::table('inventory_assigned as a')
            ->select(DB::raw('i.name, a.*'))
            ->leftJoin("inventory as i", "a.inventory_id", "i.inventory_id")
            ->where("a.updated_at","!=","0")
            ->orderBy('a.updated_at','DESC')
            ->limit(10)->get();
        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;
        return Response::json($response);
    }

    public function updateImage(Request $request){
        $validator = Validator::make($request->all(), [
                'inventory_id' => 'required'
            ]
        );
        $response = array();
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $inv = Inventory::find($request->inventory_id);
            $inv->inventory_img = md5($request->imgBase64) . '.' . explode('.', $request->imgName)[1];
            $inv->save();
            $this->uploadCategoryAvatar($request,'inventories/');

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $inv;
        }

        return Response::json($response);
    }

    public function locationList(Request $request){
        $location = DB::table("inventory_assigned as a")
            ->select(DB::raw('l.location, l.id'))
            ->leftjoin("ref_location_detail as ld", "a.storage_id", "ld.id")
            ->leftjoin("ref_location as l", "ld.loc_id", "l.id")
            ->where("a.inventory_id", $request->inventory_id)
            ->groupBy('ld.loc_id')
            ->orderBy("l.location", "ASC")
            ->get();
        $total_qty = InventoryAssigned::select(DB::raw('COUNT(inventory_assigned.id) as total_qty'))
            ->where("inventory_id", $request->inventory_id)
            ->whereIn("status", [1,2])
            ->groupBy('inventory_id')
            ->pluck('total_qty');
        $total_qty = count($total_qty)>0?$total_qty[0]:0;
        $assigned = InventoryAssigned::select(DB::raw('COUNT(inventory_assigned.id) as remaining'))
            ->where([["inventory_id", $request->inventory_id],["status", 1]])
            ->groupBy('inventory_id')
            ->pluck('remaining');
        $assigned = count($assigned)>0?$assigned[0]:0;

        foreach ($location as $n) {
            $remaining = InventoryAssigned::select(DB::raw('COUNT(inventory_assigned.id) as remaining'))
                ->leftjoin("ref_location_detail as ld", "inventory_assigned.storage_id", "ld.id")
                ->where([["inventory_id", $request->inventory_id],["ld.loc_id","=",$n->id],["status", 2]])
                ->groupBy('location_id')
                ->pluck('remaining');
            $n->remaining = count($remaining)>0?$remaining[0]:0;
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = array('qty' => $total_qty, 'assigned' => $assigned, 'location' => $location);

        return Response::json($response);
    }

    public function deleteLocation(Request $request){
        $chkId = InventoryAssigned::where("location_id", $request->id)->count();
        if($chkId > 0)
        {
            $response['status'] = "Failed";
            $response['errors'] = "Access denied! Location is already in used.";
            $response['code'] = 422;
        }
        else {
            $response['status'] = "Success";
            $response['code'] = 200;
            $response['data'] = [];
        }

        if($request->isDelete && $request->isDelete === true) {
            $loc = InventoryLocation::find($request->id);
            $loc->delete();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $loc;
        }

        return Response::json($response);
    }

    public function disposedInventory(Request $request) {
        $validator = Validator::make($request->all(), [
            'assigned_id' => 'required',
            'inventory_id' => 'required'
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $now = strtotime("now");
            $items = InventoryAssigned::where("id",$request->assigned_id)->first();

            //Logs
            $user=Auth::user();
            $reason = "$user->first_name disposed 1 $request->item_name with serial number $items->serial.";
            self::saveLogs($request->inventory_id,"Disposed", $reason);

            $data = InventoryAssigned::find($request->assigned_id);
            $data->status= 3;
            $data->updated_at = $now;
            $data->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = [];
        }

        return Response::json($response);
    }

    public function transferInventory(Request $request) {
        $validator = Validator::make($request->all(), [
            'assigned_id' => 'required',
            'inventory_id' => 'required',
            'loc_site_id' => 'required',
            'loc_detail_id' => 'required'
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $now = strtotime("now");
            $loc = LocationDetail::select('l.location','ref_location_detail.location_detail')->where("ref_location_detail.id",$request->storage_detail_id)
                ->leftJoin("ref_location as l","ref_location_detail.loc_id","l.id")
                ->first();
            if(!is_numeric($request->loc_site_id)){
                $location = $request->loc_site_id;
                $location_detail = $request->loc_detail_id;
            }else{
                $location = Location::where("id", $request->loc_site_id)->first()->location;
                if(!is_numeric($request->loc_detail_id)){
                    $location_detail = $request->loc_detail_id;
                }else{
                    $location_detail = LocationDetail::where("id", $request->loc_detail_id)->first()->location_detail;
                }
            }
            $location_detailId = self::location($request->loc_site_id, $request->loc_detail_id, 2);

            //Logs
            $user=Auth::user();
            $reason = "$user->first_name transferred 1 $request->item_name from $loc->location ($loc->location_detail) to $location ($location_detail).";
            self::saveLogs($request->inventory_id,"Transferred", $reason);

            $data = InventoryAssigned::find($request->assigned_id);
            $data->storage_id = $location_detailId;
            $data->updated_at = $now;
            $data->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $loc;
        }

        return Response::json($response);
    }

    public function updateItemProfileConsumable(Request $request){
        $validator = Validator::make($request->all(), [
            'inventory_id' => 'required',
            'description' => 'required',
            'specification' => 'nullable',
        ]);
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $user = auth()->user();
            $inv = Inventory::find($request->inventory_id);
            $inv->description = $request->description;
            $inv->specification = $request->specification;
            $inv->updated_at = strtotime("now");
            $inv->save();

            $updateSetting = json_decode($request->setting);
            $invSetting = InventoryConsumablesSetting::where('inv_id',$request->inventory_id)->first();
            $invSetting->length = $updateSetting->length_m;
            $invSetting->width = $updateSetting->width_m;
            $invSetting->height = $updateSetting->height_m;
            $invSetting->imported_rmb_price = $updateSetting->imported_rmb_price;
            $invSetting->shipping_fee_per_cm = $updateSetting->shipping_fee_per_cm;
            $invSetting->rmb_rate = $updateSetting->rmb_rate;
            $invSetting->market_price_min = $updateSetting->market_price_min;
            $invSetting->market_price_max = $updateSetting->market_price_max;
            $invSetting->advised_sale_price = $updateSetting->advised_sale_price;
            $invSetting->actual_sale_price = $updateSetting->actual_sale_price;
            $invSetting->id = $updateSetting->id;
            $invSetting->expiration_date = $updateSetting->expiration_date;
            $invSetting->remarks = $updateSetting->remarks;
            $invSetting->save();

            $checkPurchase = InventoryConsumables::where('inventory_id',$request->inventory_id)->get()->count();
            if($checkPurchase === 0){
                InventoryPurchaseUnit::where('inv_id',$inv->inventory_id)->delete();
                $purchase = self::checkUnit($request->purchase, true);
                $last_unit = collect(json_decode($request->purchase));
                foreach($purchase as $k=>$v){
                    $addPurchaseUnit = new InventoryPurchaseUnit;
                    $addPurchaseUnit->inv_id = $inv->inventory_id;
                    $addPurchaseUnit->unit_id = $v->unit_id;
                    $addPurchaseUnit->qty = $v->qty;
                    if($k+1 === count($purchase)){
                        $unit = self::checkUnit($last_unit[count($last_unit)-1]->last_unit_id);
                        $addPurchaseUnit->last_unit_id = $unit[0]->unit_id;
                        $addPurchaseUnit->parent_id = $k === 0 ? $k : $purchase[$k-1]->unit_id;;
                    }elseif($k === 0){
                        $addPurchaseUnit->last_unit_id = 0;
                        $addPurchaseUnit->parent_id = 0;
                    }else{
                        $addPurchaseUnit->last_unit_id = 0;
                        $addPurchaseUnit->parent_id = $purchase[$k-1]->unit_id;
                    }
                    $addPurchaseUnit->save();
                }
            }

            $checkSelling = InventoryConsumables::where([['inventory_id',$request->inventory_id],['selling_id','!=',null]])->pluck('selling_id')->unique();
            $checkSellingId = InventorySellingUnit::where('inv_id',$request->inventory_id)->pluck('id');
            $selling = self::checkUnit($request->selling, true,true);

            $selling_id = ArrayHelper::ArrayIndexFixed(array_diff_assoc(ArrayHelper::ObjectToArray($checkSellingId),ArrayHelper::ObjectToArray(collect($selling)->pluck('id'))));
            InventorySellingUnit::whereIn('id',$selling_id)->delete();
            foreach($selling as $k=>$v){
                if(!in_array($v->id, ArrayHelper::ObjectToArray($checkSelling))){
                    $updateSellingUnit = InventorySellingUnit::find($v->id);
                    if($updateSellingUnit){
                        $updateSellingUnit->unit_id = $v->unit_id;
                        $updateSellingUnit->qty = $v->qty;
                        $updateSellingUnit->save();
                    }else{
                        $addSellingUnit = new InventorySellingUnit;
                        $addSellingUnit->inv_id = $request->inventory_id;
                        $addSellingUnit->unit_id = $v->unit_id;
                        $addSellingUnit->qty = $v->qty;
                        $addSellingUnit->save();
                    }
                }
            }

            $ilog = new InventoryLogs;
            $ilog->inventory_id = $inv->inventory_id;
            $ilog->type = 'Updated';
            $ilog->reason = $user->first_name.' updated '.$inv->name;
            $ilog->created_by = $user->id;
            $ilog->created_at = strtotime("now");
            $ilog->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = 'Update Successful!';

        }
        return Response::json($response);
    }

    public function getInventory(Request $request){
        $list = Inventory::where('inventory_id',$request->inventory_id)->get(['inventory_id','description','specification']);
        foreach ($list as $k=>$v){
            $v->setting = InventoryConsumablesSetting::where('inv_id',$v->inventory_id)->get();
            $v->purchase = InventoryPurchaseUnit::leftJoin('inventory_unit as iun','inventory_purchase_unit.unit_id','iun.unit_id')
                ->where('inventory_purchase_unit.inv_id',$v->inventory_id)->get(['iun.name','inventory_purchase_unit.qty']);
            foreach($v->purchase as $kk=>$vv){
                $last_unit_name = InventoryPurchaseUnit::leftJoin('inventory_unit as iun','inventory_purchase_unit.last_unit_id','iun.unit_id')
                    ->where('inventory_purchase_unit.inv_id',$v->inventory_id)->get(['iun.name']);
                $vv->last_unit_id = $kk === (count($v->purchase)-1) ? $last_unit_name[count($v->purchase)-1]['name'] : '';
            }
            $v->selling = InventorySellingUnit::leftJoin('inventory_unit as iun','inventory_selling_unit.unit_id','iun.unit_id')
                ->where('inv_id',$v->inventory_id)->get(['inventory_selling_unit.id','iun.name','inventory_selling_unit.qty']);
            $v->consumable = InventoryConsumables::where('inventory_id',$v->inventory_id)->get()->count();
            $v->selling_id = ArrayHelper::ArrayIndexFixed(collect(InventoryConsumables::where([['inventory_id',$v->inventory_id],['selling_id','!=',null]])->pluck('selling_id'))->unique());
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;

        return Response::json($response);
    }

    public function getUsersList() {
        $role_ids = Role::where( function ($query) {
            $query->orwhere('name', 'master')
                ->orwhere('name', 'cpanel-admin')
                ->orwhere('name', 'employee');
        })->pluck("id");

        $users = User::select('id', 'first_name', 'last_name')->where('password','!=',null)
            ->whereHas('roles', function ($query) use ($role_ids) {
                $query->where('roles.id', '=', $role_ids);
            })->get();

        $response['status'] = 'Success';
        $response['data'] = $users;
        $response['code'] = 200;
        return Response::json($response);
    }

    public function addMoreItem(Request $request){
        $validator = Validator::make($request->all(), [
            'inventory_id' => 'required'
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $user=Auth::user();
            $purchased = 0;
            $received = 0;
            $price = 0;
            foreach (json_decode($request->forms, true) as $key => $val) {
                if($val["type".$key] == "Purchased"){
                    $purchased += 1;
                    if($purchased == 1){
                        $price = $val["purchase_price".$key];
                    }
                }
                if($val["type".$key] == "Received"){
                    $received += 1;
                }
                $location_detailId = self::location($val["location".$key], $val["location_detail".$key], 2);

                $data = new InventoryAssigned;
                $data->inventory_id = $request->inventory_id;
                $data->model = $val["model".$key];
                $data->serial = $val["serial".$key];
                $data->source = $val["type".$key];
                if($val["type".$key] == "Purchased") {
                    $data->hasOR = $val["hasOR" . $key];
                    $data->purchase_price = $val["purchase_price" . $key];
                }else{
                    $data->received_id = $val["user_id" . $key];
                }
                $data->remarks = $val["remarks".$key];
                $data->storage_id = $location_detailId;
                $data->status = 2;
                $data->created_by = $user->id;
                $data->updated_by = $user->id;
                $data->created_at = strtotime("now");
                $data->updated_at = strtotime("now");
                $data->save();
            }

            //Logs
            if($purchased>0) {
                $reason = "$user->first_name purchased $purchased $request->item_name with the price of Php$price.";
                self::saveLogs($request->inventory_id,"Stored",$reason);
            }
            if($received>0) {
                $reason = "$user->first_name received $received $request->item_name";
                self::saveLogs($request->inventory_id,"Stored",$reason);
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = [];
        }
        return Response::json($response);
    }

    protected static function location($loc_site_id, $loc_detail_id, $type=1){
        if(!is_numeric($loc_site_id)){
            $loc = Location::where([["location", "=", $loc_site_id],["type", "=", $type]])->first();
            if($loc){
                $l_id = $loc->id;
            }else{
                $location = new Location;
                $location->location = $loc_site_id;
                $location->type = $type;
                $location->save();

                $l_id = $location->id;
            }

            $detail = LocationDetail::where([["loc_id",$l_id],["location_detail", "=", $loc_detail_id]])->first();
            if($detail){
                $did = $detail->id;
            }else{
                $location_detail = new LocationDetail;
                $location_detail->loc_id = $l_id;
                $location_detail->location_detail = $loc_detail_id;
                $location_detail->save();

                $did = $location_detail->id;
            }

            $location_detailId = $did;
        }else{
            if(!is_numeric($loc_detail_id)){
                $detail = LocationDetail::where([["loc_id",$loc_site_id],["location_detail", "=", $loc_detail_id]])->first();
                if($detail){
                    $did = $detail->id;
                }else{
                    $location_detail = new LocationDetail;
                    $location_detail->loc_id = $loc_site_id;
                    $location_detail->location_detail = $loc_detail_id;
                    $location_detail->save();

                    $did =  $location_detail->id;
                }

                $location_detailId = $did;
            }else{
                $location_detailId = $loc_detail_id;
            }
        }
        return $location_detailId;
    }

    protected static function saveLogs($inventory_id, $type, $reason){
        $log = new InventoryLogs;
        $log->inventory_id = $inventory_id;
        $log->type = $type;
        $log->created_by = Auth::user()->id;
        $log->created_at = strtotime("now");
        $log->reason = $reason;
        $log->save();
    }

    public function getPurchaseUnit(Request $request){
        if(count($request->all()) !== 0){
            $unit = InventoryPurchaseUnit::leftJoin('inventory_unit as iun','inventory_purchase_unit.unit_id','iun.unit_id')
                ->where('inventory_purchase_unit.inv_id',$request->inventory_id)->get();
        }else{
            $unit = InventoryUnit::orderBy('name','ASC')->get();
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $unit;

        return Response::json($response);
    }

    public function getSellingUnit(Request $request){
        $unit = InventorySellingUnit::leftJoin('inventory_unit as iun','inventory_selling_unit.unit_id','iun.unit_id')
            ->where('inventory_selling_unit.inv_id',$request->inventory_id)->get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $unit;

        return Response::json($response);
    }

    public function getActionLog(Request $request){
        $phis = InventoryLogs::where('inventory_id',$request->inventory_id)->orderBy('created_at','DESC')->get();

        foreach ($phis as $v){
            $v->created_at = Carbon::createFromTimestamp($v->created_at)->format('M. d Y');
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $phis;

        return Response::json($response);
    }

    public function addInventoryConsumable(Request $request){
        $validator = Validator::make($request->all(), [
            'inventory_id' => 'required',
            'qty' => 'required',
            'unit' => 'required',
            'price' => 'nullable',
            'location' => 'required',
            'location_detail' => 'required',
            'sup_name' => 'required',
            'sup_location' => 'required'
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $user = auth()->user();
            $locId = self::location($request->location, $request->location_detail,2);
            $supLocId = self::location($request->sup_location, $request->sup_location_detail,3);

            if(!is_numeric($request->location)){
                $location = $request->location;
            }else{
                $location = Location::where("id", $request->location)->first()->location;
            }

            if(!is_numeric($request->location_detail)){
                $locDetail = $request->location_detail;
            }else{
                $locDetail = LocationDetail::where("id", $request->location_detail)->first()->location_detail;
            }

            if(!is_numeric($request->sup_location)){
                $sLocation = $request->sup_location;
            }else{
                $sLocation = Location::where("id", $request->sup_location)->first()->location;
            }

            if(!is_numeric($request->sup_location_detail)){
                $sLocDetail = $request->sup_location_detail;
            }else{
                $sLocDetail = LocationDetail::where("id", $request->sup_location_detail)->first()->location_detail;
            }

            $qty = $request->qty;
            //Logs
            $unit = InventoryUnit::where("unit_id", $request->unit)->first()->name;
            $reason = "$user->first_name purchased $qty ".$unit." from $request->sup_name($sLocation $sLocDetail) with the price of Php$request->price
                    Stored at $location $locDetail.";

            self::saveLogs($request->inventory_id, 'Stored', $reason);

            $icon = new InventoryConsumables;
            $icon->inventory_id = $request->inventory_id;
            $formula = InventoryPurchaseUnit::where('inv_id',$request->inventory_id)->get();
            $start = false;
            foreach($formula as $k=>$v){
                if($request->unit == $v->unit_id){
                    $start = true;
                }
                if($start === true){
                    $qty *= $v->qty;
                }
            }
            $icon->qty = $qty;
            $icon->unit_id = $request->unit;
            $icon->price = $request->price;
            $icon->location_id = $locId;
            $icon->sup_name = $request->sup_name;
            $icon->sup_location_id = $supLocId;
            $icon->type = 'Purchased';
            $icon->created_by = $user->id;
            $icon->created_at = strtotime("now");
            $icon->updated_at = strtotime("now");
            $icon->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = 'Consumable has been Created!';
        }

        return Response::json($response);
    }

    public function getSupplier(){
        $data = InventoryConsumables::where('type','Purchased')
            ->groupBy('sup_name')
            ->orderBy('updated_at','DESC')
            ->get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $data;

        return Response::json($response);
    }

    public function addInventoryConsume(Request $request){
        $validator = Validator::make($request->all(), [
            'inventory_id' => 'required',
            'location_id' => 'required',
            'set' => 'required',
        ]);
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $user = auth()->user();
            $name_id = '';
            foreach (json_decode($request->set) as $k=>$v) {
                if($v->qty !== ''){
                    $icon = new InventoryConsumables;
                    $icon->inventory_id = $request->inventory_id;
                    $icon->location_id = $request->location_id;
                    $icon->qty = $v->qty;
                    $icon->unit_id = $v->unit;
                    $icon->reason = $v->reason;
                    if($k === 0){
                        $name_id = $request->user;
                        $set = 'Consumed';
                        $icon->price = $v->price;
                        $icon->assigned_to = $request->user;
                    // }elseif($k === 1){
                    //     $name_id = $user->id;
                    //     $set = 'Converted';
                    //     $icon->selling_id = $request->set_unit;
                    }elseif($k === 2){
                        $name_id = $user->id;
                        $set = 'Wasted';
                    }
                    $icon->type = $set;
                    $icon->created_by = $user->id;
                    $icon->created_at = strtotime("now");
                    $icon->updated_at = strtotime("now");
                    $icon->save();

                    $name = User::select('first_name')->where("id", $name_id)->first();
                    $purchase = InventoryConsumables::leftJoin('inventory_unit AS iun','inventory_consumables.unit_id','iun.unit_id')
                        ->where([['inventory_consumables.inventory_id',$request->inventory_id],['inventory_consumables.unit_id',$v->unit]])
                        ->get(['iun.name AS unit']);
                    if($k !== 1){
                        $reason = "$name->first_name ".$set." ".(int)$v->qty." ".$purchase[0]->unit;
                    }else{
                        $selling = InventoryConsumables::leftJoin('inventory_selling_unit AS isu','inventory_consumables.selling_id','isu.id')
                            ->leftJoin('inventory_unit AS iun','isu.unit_id','iun.unit_id')
                            ->where([['inventory_consumables.inventory_id',$request->inventory_id],['inventory_consumables.selling_id',$request->set_unit]])
                            ->get(['iun.name AS unit','isu.qty']);
                        $reason = "$name->first_name ".$set." ".(int)$v->qty." ".$purchase[0]->unit." -> ".$selling[0]->qty." ".$selling[0]->unit;
                    }
                    self::saveLogs($request->inventory_id, 'Stored', $reason);
                }
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = 'Consumable has been Created!';
            $response['request'] = $request->all();
        }

        return Response::json($response);
    }

    public function getInventoryConsumable(Request $request){
        $list = DB::table('inventory_consumables as c')
            ->select(DB::raw("c.*, l.location, ld.location_detail, l.id as storageId,
                    CONCAT(u.first_name, ' ', u.last_name) as operator, CONCAT(w.first_name, ' ', w.last_name) as who,
                    l1.location as sup_location, ld1.location_detail as sup_location_detail, iu.name as unit,
                    iu1.name as sUnit, su.qty as sQty, IFNULL(c.price, 0) as price"))
            ->where("c.inventory_id", $request->inventory_id)
            ->leftJoin("users as u", "c.created_by", "u.id")
            ->leftJoin("users as w", "c.assigned_to", "w.id")
            ->leftJoin("ref_location_detail as ld","c.location_id","ld.id")
            ->leftJoin("ref_location as l","ld.loc_id","l.id")
            ->leftJoin("ref_location_detail as ld1","c.sup_location_id","ld1.id")
            ->leftJoin("ref_location as l1","ld1.loc_id","l1.id")
            ->leftJoin("inventory_unit as iu", "c.unit_id", "iu.unit_id")
            ->leftJoin("inventory_selling_unit as su", "c.selling_id", "su.id")
            ->leftJoin("inventory_unit as iu1", "su.unit_id", "iu1.unit_id")
            ->orderBy("id", "ASC")->get();
        $set = 0; $i = 0; $totalPrice = 0; $totalSale = 0;
        $units = InventoryPurchaseUnit::where("inv_id", $request->inventory_id)->orderBy("id", "ASC")->get('unit_id as unitId');
        $sell = InventorySellingUnit::where("inv_id", $request->inventory_id)->orderBy("id", "ASC")->get('id');
        foreach ($units as $u) {
            $qty[$u->unitId] = 0;
            $rUnit[$i] = "";
            $i++;
        }
        foreach ($sell as $s) {
            $sellQty[$s->id] = 0;
            $rSet[$s->id] = "";
            $i++;
        }
        $rQty = 0;
        foreach ($list as $l){
            $tPurchase = 0;
            $tSale = 0;
            if($l->type === 'Purchased'){
                $tPurchase = $l->qty * $l->price;
            }else if($l->type === 'Consumed'){
                $tSale = $l->qty * $l->price;
            }
            $l->subTotal = $tPurchase + $tSale;
            $l->purchased = $l->qty;
            $l->created_at = gmdate("M j, Y", $l->created_at);
            $l->updated_at = gmdate("M j, Y", $l->updated_at);
            $l->location = $l->location?$l->location:'';
            $l->location_detail = $l->location_detail?$l->location_detail:'';
            $l->sup_name = $l->sup_name?$l->sup_name:'';
            $s = $l->sup_location;
            $l->sup_location = $s?$s.' ('.$l->sup_location_detail.')':'';

            //$l->qtyUnit = $l->qty." $l->unit";
            $l->qtyUnit = self::unitFormatting($request->inventory_id, $l->qty);
            //$l->qtySet = 0;

            if($i==0 && $l->type == "Purchased") {
                $qty[$l->unit_id] += $l->qty;
                $rQty += $l->qty;
            }
            if($i!=0) {
                if ($l->type == "Purchased") {
                    $qty[$l->unit_id] += $l->qty;
                    $rQty += $l->qty;
                }
                if ($l->type == "Consumed" || $l->type == "Wasted" || $l->type == "Converted") {
                    $rQty -= $l->qty;
                }

                /*
                if ($l->type == "Converted") {
                    $cQty = self::convertToSet($request->inventory_id, $l->unit_id, $l->selling_id, $l->qty);
                    $sellQty[$l->selling_id] += $cQty;
                    $set += $cQty;
                }
                if ($l->type == "Sold") {
                    $cQty = self::convertToSet($request->inventory_id, $l->unit_id, $l->selling_id, $l->qty);
                    $l->qtySet = $cQty;
                    $sellQty[$l->selling_id] -= $cQty;
                    $set -= $cQty;
                }
                */
            }

            /*
            $j=0;
            foreach ($units as $u) {
                if($l->unit_id == $u->unitId) {
                    $rUnit[$j] = self::unitFormatting($request->inventory_id, $qty[$l->unit_id]);
                }
                $j++;
            }

            foreach ($sell as $s) {
                if (($l->type == "Converted" || $l->type == "Sold")) {
                    $rSet[$l->selling_id] = $sellQty[$l->selling_id]." Set($l->sQty $l->sUnit)";
                }

                $j++;
            }*/

            $l->remainingUnit = self::unitFormatting($request->inventory_id, $rQty);
            //$l->remainingUnit = trim(implode(" ", $rUnit));
            //$l->remainingSet = $set;
            //$l->toolTipSet = trim(implode(" ",$rSet));

            $qty[$l->unit_id] = $qty[$l->unit_id];

            //$set = $set;
            //if ($l->type == "Converted" || $l->type == "Sold") {
            //    $sellQty[$l->selling_id] = $sellQty[$l->selling_id];
            //}

            $totalPrice += $l->subTotal - $tSale;
            $totalSale += $tSale;
            $l->totalSale = $totalSale;
            $i++;
        }

        $data = $list->toArray();
        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = array_reverse($data);
        $response['price'] = $totalPrice;
        $response['sale'] = $totalSale;

        return Response::json($response);
    }

    public function locationListConsumable(Request $request){
        $location = DB::table("inventory_consumables as c")->select(DB::raw('l.location, l.id as locId, c.location_id'))
            ->leftjoin("ref_location_detail as ld", "c.location_id", "ld.id")
            ->leftjoin("ref_location as l", "ld.loc_id", "l.id")
            ->where([["c.inventory_id", $request->inventory_id],["c.type","=","Purchased"]])
            ->groupBy('ld.loc_id')->orderBy("l.location", "ASC")->get();
        $i=0;
        //$units = InventoryPurchaseUnit::where("inv_id", $request->inventory_id)->orderBy("id", "ASC")->get(['unit_id as unitId']);
        $sell = InventorySellingUnit::where("inv_id", $request->inventory_id)->orderBy("id", "ASC")->get('id');
        foreach ($location as $l){
            $d_unit = DB::table('inventory_consumables as c')
                ->select(DB::raw('c.*, iu.name as unit, iu1.name as sUnit, su.qty as sQty'))
                ->leftjoin("ref_location_detail as ld", "c.location_id", "ld.id")
                ->leftjoin("ref_location as l", "ld.loc_id", "l.id")
                ->leftJoin("inventory_unit as iu", "c.unit_id", "iu.unit_id")
                ->leftJoin("inventory_selling_unit as su", "c.selling_id", "su.id")
                ->leftJoin("inventory_unit as iu1", "su.unit_id", "iu1.unit_id")
                ->where([["inventory_id", $request->inventory_id], ["l.id", $l->locId]])->get();
            /*
            foreach ($units as $u) {
                $qty[$l->locId][$u->unitId] = 0;
                $rUnit[$l->locId][$i] = "";
                $i++;
            }
            */
            foreach ($sell as $s) {
                $sellQty[$l->locId][$s->id] = 0;
                $rSet[$l->locId][$s->id] = "";
                $i++;
            }
            $set = 0;
            $qty = 0;
            $rUnit = "";
            foreach ($d_unit as $p) {
                if($i==0 && $p->type == "Purchased") {
                    //$qty[$l->locId][$p->unit_id] += $p->qty;
                    $qty += $p->qty;
                }
                if($i!=0) {
                    if ($p->type == "Purchased") {
                        //$qty[$l->locId][$p->unit_id] += $p->qty;
                        $qty += $p->qty;
                    }
                    if ($p->type == "Consumed" || $p->type == "Wasted" || $p->type == "Converted") {
                        //$qty[$l->locId][$p->unit_id] -= $p->qty;
                        $qty -= $p->qty;
                    }

                    /*
                    if ($p->type == "Converted") {
                        $cQty = self::convertToSet($request->inventory_id, $p->unit_id, $p->selling_id, $p->qty);
                        $sellQty[$l->locId][$p->selling_id] += $cQty;
                        $set += $cQty;
                    }
                    if ($p->type == "Sold") {
                        $cQty = self::convertToSet($request->inventory_id, $p->unit_id, $p->selling_id, $p->qty);
                        $p->qtySet = $cQty;
                        $sellQty[$l->locId][$p->selling_id] -= $cQty;
                        $set -= $cQty;
                    } */
                }

                /*
                $j=0;
                foreach ($units as $u) {
                    if($p->unit_id == $u->unitId) {
                        $rUnit[$l->locId][$j] = $qty[$l->locId][$p->unit_id]." $p->unit";
                    }
                    $j++;
                }

                foreach ($sell as $s) {
                    if (($p->type == "Converted" || $p->type == "Sold")) {
                        $rSet[$l->locId][$p->selling_id] = $sellQty[$l->locId][$p->selling_id]." Set($p->sQty $p->sUnit)";
                    }

                    $j++;
                }*/

                $l->rUnit = self::unitFormatting($request->inventory_id, $qty);
                //$l->rSet = $set;
                //$l->toolTipSet = trim(implode(" ",$rSet[$l->locId]));

                $qty = $qty;
                //$qty[$l->locId][$p->unit_id] = $qty[$l->locId][$p->unit_id];

                /*$set = $set;
                if ($p->type == "Converted" || $p->type == "Sold") {
                    $sellQty[$l->locId][$p->selling_id] = $sellQty[$l->locId][$p->selling_id];
                }*/
            }

        }
        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $location;

        return Response::json($response);
    }

    // Unit Formatting
    public static function unitFormatting($inventory_id, $qty){
        $array_m = [];
        $unit = InventoryPurchaseUnit::where('inv_id', $inventory_id)
            ->leftJoin('inventory_unit as iunit', 'iunit.unit_id', '=', 'inventory_purchase_unit.unit_id')
            ->orderBy('id', 'desc')
            ->get();
        $x = 0;
        foreach ($unit as $u) {
            $array_m[$x] = $u['qty'];
            $x++;
        }
        $tree = $unit->reverse();

        $j = 0;
        $name = [];
        foreach ($tree as $u) {
            $name[$j] = $u['name'];
            $j++;
        }
        $array_o = array_reverse($name);
        $units = array_combine($array_o, $array_m);

        if($qty == 0){
            return '0 '.$array_o[0];
        }
        $sections = [];
        if(count($units) == 1){
            $g = [];
            foreach ($units as $key=>$val){
                $sections[$key] = $qty;
            }

            foreach ($sections as $name => $value){
                if ($value > 0){
                    $g[] = $value. ' '.$name.($value == 1 ? '' : 's');
                }
            }

            return implode(' | ', $g);
        }

        $jjj=0;
        foreach($units as $key => $value) {
            if(0!=$jjj) {
                if(1==$jjj) {
                    $cond[$key] = $value;
                    $cond1 = $value;
                }
                if(1!=$jjj){
                    $cond[$key] = $value * $cond1;
                    $cond1 = $value * $cond1;
                }

            }
            if(0!=$jjj){
                $in[$jjj] = $key;
            }
            $jjj++;
        }

        $len = count($cond) - 1;
        if($len == 0){
            foreach (array_reverse($cond) as $key=>$val) {
                $condX[$key] = floor($qty / $val);
                $lastUnit = $qty % $val;
            }

            $condX[$array_o[0]] = ceil($lastUnit);

            $g = [];
            foreach ($condX as $key=>$val){
                $sections[$key] = (int)$val;
            }

            foreach ($sections as $name => $value){
                if ($value > 0){
                    $g[] = $value. ' '.$name.($value == 1 ? '' : 's');
                }
            }

            return implode(' | ', $g);
        }

        $js = 0;
        foreach (array_reverse($cond) as $key=>$val) {
            if($js==0) {
                $condX[$key] = floor($qty / $val);
                $cond2 = $val;
            }
            if($js==1) {
                $cond3 = $qty % $cond2;
                $condX[$key] = floor($cond3 / $val);
                $cond2 = $val;
            }
            if($js!=1 && $js!=0) {
                $cond3 = $cond3 % $cond2;
                $condX[$key] = floor($cond3 / $val);
                $cond2 = $val;
            }
            if($len==$js){
                $lastUnit = $cond3 % $cond2;
            }
            $js++;
        }

        $condX[$array_o[0]] = ceil($lastUnit);

        $g = [];
        foreach ($condX as $key=>$val){
            $sections[$key] = (int)$val;
        }

        foreach ($sections as $name => $value){
            if ($value > 0){
                $g[] = $value. ' '.$name.($value == 1 ? '' : 's');
            }
        }

        return implode(' | ', $g);

    }

    protected static function unitFormat($u, $sell, $qty){ //ie. unit=Box, sell=10, qty=22 : return 2 Sets, 2 Boxs
        if($qty == 0){
            return 0;
        }
        $unit = array();
        $unit['Set'] = floor($qty / $sell);
        $fr = $qty / $sell - floor($qty / $sell);
        $res = $fr * $sell;
        if(is_float($res)){
            $res = number_format($res, 2);
        }
        $unit[$u] = $res;
        $g = array();
        foreach ($unit as $name => $value){
            if ($value > 0){
                $g[] = $value. ' '.$name.($value == 1 ? '' : 's');
            }
        }
        return implode(', ', $g);
    }

    protected static function convertToSet($inventoryId, $pUnitId, $sId, $qty){
        $units = InventoryPurchaseUnit::where("inv_id", $inventoryId)->get();
        $sell = InventorySellingUnit::where("id", $sId)->first();
        $a=0;$b=0;
        foreach ($units as $k=>$v){
            if($v->unit_id == $pUnitId){
                $a = $k;
            }
            if($v->unit_id == $sell->unit_id){
                $b = $k;
            }
        }
        if($pUnitId == $sell->unit_id){
            return $qty/$sell->qty;
        }
        $i=0; $unit = [];
        if(($a-$b)>0){
            $units = array_reverse($units->toArray());
            foreach ($units as $k=>$u){
                $cond = $i;
                if($u['unit_id']==$pUnitId)
                    continue;
                if($cond>=$k)
                    continue;
                $qty = $qty / $u['qty'];
                $unit[$i] = $qty;

                if($u['unit_id'] == $sell->unit_id)
                    break;
                $i++;
            }
            return $unit[count($unit)-1] / $sell->qty;
        }

        foreach ($units as $k=>$u){
            $cond = $i;
            if($u->unit_id==$pUnitId){
                $qty = $qty * $u->qty;
                $unit[$i] = $qty;
            }
            if($u->unit_id==$pUnitId)
                continue;
            if($cond>=$k)
                continue;
            if($u->unit_id != $sell->unit_id) {
                $qty = $qty * $u->qty;
                $unit[$i] = $qty;
            }
            if($u->unit_id == $sell->unit_id)
                break;
            $i++;
        }
        return $unit[count($unit)-1] / $sell->qty;
    }

    public function testKo(){
        return Response::json(self::unitFormat(30, 11));
    }
}


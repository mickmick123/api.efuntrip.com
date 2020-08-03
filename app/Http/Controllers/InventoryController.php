<?php

namespace App\Http\Controllers;

use App\Inventory;
use App\InventoryLocation;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\InventoryParentCategory;
use App\InventoryCategory;
use App\InventoryAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\PageHelper;

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

    public function addInventory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
            'category_id' => 'required',
            'name' => 'required',
            'description' => 'required',
            'type' => 'required',
            'or' => 'required',
            'unit' => 'required',
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
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
            $inv->purchase_price = $request->purchase_price;
            $inv->or = $request->or;
            $inv->unit = $request->unit;
            $inv->created_at = strtotime("now");
            $inv->updated_at = strtotime("now");
            $inv->save();

            foreach (json_decode($request->location_storage, true) as $k=>$v) {
                if($v["quantity".$k] !== null && $v["location".$k] !== null){
                    $loc = new InventoryLocation;
                    $loc->inventory_id = $inv->inventory_id;
                    $loc->qty = $v["quantity".$k];
                    $loc->location = $v["location".$k];
                    $loc->created_at = strtotime("now");
                    $loc->updated_at = strtotime("now");
                    $loc->save();
                }
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = explode('.', $request->imgName);
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
            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = explode('.', $request->imgName);
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
            $inv = Inventory::find($request->inventory_id);
            $inv->delete();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $inv;
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

        $ipc = InventoryParentCategory::where('company_id', $request->company_id)
            ->where(function ($q) use ($categ, $request) {
                $q->whereIn('category_id', $categ);
                $q->orwhere('category_id', $request->category_id);
            })->delete();

        $icat = InventoryCategory::where(function ($q) use ($categ, $request) {
                $q->whereIn('category_id', $categ);
                $q->orwhere('category_id', $request->category_id);
            })->delete();

        $invId = Inventory::where('company_id', $request->company_id)
            ->where(function ($q) use ($categ, $request) {
                $q->whereIn('category_id', $categ);
                $q->orwhere('category_id', $request->category_id);
            })->pluck('inventory_id');

        $inv = Inventory::where('company_id', $request->company_id)
            ->where(function ($q) use ($categ, $request) {
                $q->whereIn('category_id', $categ);
                $q->orwhere('category_id', $request->category_id);
            })->delete();

        $loc = InventoryLocation::whereIn('inventory_id',$invId)
            ->delete();

        $ass = InventoryAssigned::whereIn('inventory_id',$invId)
            ->delete();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = 'Category and Inventory has been Deleted!';
        return Response::json($response);
    }

    public function test(Request $request){
        return Response::json($request->all());
    }

    public function uploadCategoryAvatar($data,$folder) {
        $img64 = $data->imgBase64;

        list($type, $img64) = explode(';', $img64);
        list(, $img64) = explode(',', $img64);

        if($img64!=""){ // storing image in storage/app/public Folder
            \Storage::disk('public')->put($folder . md5($data->imgBase64) . '.' . explode('.', $data->imgName)[1], base64_decode($img64));
        }
    }

    public function list(Request $request)
    {
        $name = $request->input("q", "");
        $id = intval($request->input("inventory_id", 0));
        $co_id = intval($request->input("company_id", 0));
        $ca_id = intval($request->input("category_id", 0));
        $page = intval($request->input("page", 1));
        $pageSize = intval($request->input("limit", 20));

        $any = array();
        if ($ca_id != "")
        {
            $any[] = ["category_id", $ca_id];
        }

        $filter = array();
        if ($id != 0)
        {
            $filter[] = ["inventory_id", $id];
        }
        if ($co_id != 0)
        {
            $filter[] = ["inventory.company_id", $co_id];
        }
        if($name !="") {
            $filter[] = ["inventory.name", "LIKE", "%".$name."%"];
        }

        $category_ids = InventoryCategory::where($any)->pluck('category_id');

        $cats = InventoryParentCategory::where('category_id', $ca_id)->get();

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

        $items2 = array_merge(array($ca_id), $items1);

        if(count($items1)>0){
            $item_found = $items2;
        }else{
            $item_found = $category_ids;
        }

        $page_obj = new PageHelper($page, $pageSize);
        if (empty($page_obj)) {
            return array();
        }
        $count = DB::table('inventory')->where($filter)
            ->whereIn("category_id", $item_found)->count();

        if ($count==0)
        {
            $response['status'] = 'No results found.';
            $response['code'] = 404;
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
            ->select(DB::raw('co.name as company_name, inventory.*'))
            ->leftjoin('company as co', 'inventory.company_id', 'co.company_id')
            ->where($filter)
            ->whereIn("category_id", $item_found)
            ->limit($limit)->offset(($page - 1) * $limit)->get()->toArray();
        $i=0;
        foreach($list as $n){
            $locQty[$i] = DB::table('inventory_location')->select(DB::raw('SUM(qty) AS total_stored'))
                        ->where([["inventory_id", $n->inventory_id],["status", 1]])
                        ->groupBy('inventory_id')
                        ->pluck('total_stored');
            $a = count($locQty[$i])==1?(int)$locQty[$i][0]:0;
            $assignedQty[$i] = DB::table('inventory_assigned')->select(DB::raw('SUM(assigned_qty) AS total_assigned'))
                ->where([["inventory_id", $n->inventory_id],["status", 1]])
                ->groupBy('inventory_id')
                ->pluck('total_assigned');
            $b = count($assignedQty[$i])==1?(int)$assignedQty[$i][0]:0;

            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            $n->created_at = gmdate("F j, Y", $n->created_at);
            $n->updated_at = gmdate("F j, Y", $n->updated_at);
            $n->or = (string)$n->or;
            $n->qty = $a + $b;
            $n->total_assigned = $b;
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $n->item_name = $np->name;
                $j=0;
                foreach($tree as $t){
                    $n->x[$j] = $t->name;

                    $n->asset_name = implode(" | ", $n->x);
                    $j++;
                }
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
        $response['data'] = $data;
        return Response::json($response);
    }

    public function listAssigned(Request $request)
    {
        $inventory_id = intval($request->input("id"));
        $data = DB::table('inventory_assigned as a')
            ->select(DB::raw('a.*, i.type'))
            ->leftjoin('inventory as i', 'a.inventory_id', 'i.inventory_id')
            ->where([["a.inventory_id", "=", $inventory_id],["a.status", 1]])
            ->orderBy('a.created_at','DESC')
            ->get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $data;
        return Response::json($response);
    }

    public function editAssignedItem(Request $request)
    {
        $rules = [
            'inventory_id' => 'required',
            'assigned_to' => 'required|min:3',
            'location_site' => 'required|min:3',
            'location_detail' => 'required|min:3',
            //'assigned_qty' => 'required|integer|min:1',
            'model' => 'nullable',
            'serial' => 'nullable'
        ];
        $messages = [
            'required' => 'This field is required.',
            'integer' => 'Please input numbers only.',
            'assigned_to.min' => 'Please input minimum of 3 character.',
            'location_site.min' => 'Please input minimum of 3 character.',
            'location_detail.min' => 'Please input minimum of 3 character.',
            //'assigned_qty.min' => 'Please input a valid number.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);

        $response = array();
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $inv = InventoryAssigned::find($request->id);
            $inv->assigned_to = $request->assigned_to;
            $inv->location_site = $request->location_site;
            $inv->location_detail = $request->location_detail;
            // $inv->assigned_qty = $request->assigned_qty;
            if($request->model !== null) {
                $inv->model = $request->model;
            }
            if($request->serial !== null) {
                $inv->serial = $request->serial;
            }
            $inv->updated_at = strtotime("now");
            $inv->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $inv;

        }

        return Response::json($response);
    }

    public function retrieveInventory(Request $request) {
        $rules = [
            'id' => 'required',
            'loc_id' => 'required',
            'assigned_qty' => 'required|integer|min:1'
        ];
        $messages = [
            'required' => 'This field is required.',
            'integer' => 'Please input numbers only.',
            'assigned_qty.min' => 'Please input a valid number.'
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $items = InventoryAssigned::where("id", $request->id)->get();

            $now = strtotime("now");
            if((int)$request->assigned_qty < (int)$items[0]['assigned_qty']){
                $inv = new InventoryAssigned;
                $inv->inventory_id = (int)$items[0]['inventory_id'];
                $inv->location_id = $items[0]['location_id'];
                $inv->assigned_to = $items[0]['assigned_to'];
                $inv->location_site = $items[0]['location_site'];
                $inv->location_detail = $items[0]['location_detail'];
                $inv->assigned_qty = (int)$items[0]['assigned_qty'] - $request->assigned_qty;
                $inv->model = $items[0]['model'];
                $inv->serial = $items[0]['serial'];
                $inv->created_at = (int)$items[0]['created_at'];
                $inv->updated_at = $now;
                $inv->save();
            }

            $iUpd = InventoryAssigned::find($request->id);
            $iUpd->stored_to = $request->loc_id;
            $iUpd->assigned_qty = $request->assigned_qty;
            $iUpd->status = 2;
            $iUpd->updated_at = $now;
            $iUpd->save();

            $locUpd = InventoryLocation::find($request->loc_id);
            $locUpd->qty = InventoryLocation::where("id", $request->loc_id)->pluck('qty')[0] + $request->assigned_qty;
            $locUpd->updated_at = $now;
            $locUpd->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = [];
        }

        return Response::json($response);
    }

    public function assignInventory(Request $request){
        $rules = [
            'inventory_id' => 'required',
            'assigned_to' => 'required',
            'location_site' => 'required',
            'location_detail' => 'required',
            'assigned_items' => 'required',
            'model' => 'nullable',
            'serial' => 'nullable'
        ];
        $messages = [
            'required' => 'This field is required.',
            'integer' => 'Please input numbers only.',
            'assigned_qty.min' => 'Please input a valid number.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);

        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            foreach (json_decode($request->assigned_items, true) as $row)
            {
                $iData = new InventoryAssigned;
                $iData->inventory_id = $request->inventory_id;
                $iData->location_id = $row["id"];
                $iData->assigned_to = $request->assigned_to;
                $iData->location_site = $request->location_site;
                $iData->location_detail = $request->location_detail;
                $iData->assigned_qty = $row["assigned_qty"];
                if($request->model !== null) {
                    $iData->model = $request->model;
                }
                if($request->serial !== null) {
                    $iData->serial = $request->serial;
                }
                $iData->created_at = strtotime("now");
                $iData->save();

                $locQty = InventoryLocation::where("id", $row['id'])->pluck('qty');
                $uData = InventoryLocation::find($row["id"]);
                $uData->qty = (int)$locQty[0] - (int)$row["assigned_qty"];
                $uData->updated_at = strtotime("now");
                $uData->save();

            }

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = [];

        }
        return Response::json($response);
    }

    public function getNewlyAdded()
    {
        $newlyAdded = DB::table('inventory')
            ->select(DB::raw('co.name as company, category_id, inventory.company_id, inventory.name, inventory.inventory_id'))
            ->leftjoin('company as co', 'inventory.company_id', 'co.company_id')
            ->orderBy('inventory_id','DESC')
            ->limit(10)
            ->get();
        $i=0;
        foreach($newlyAdded as $n){
            $locQty[$i] = DB::table('inventory_location')->select(DB::raw('SUM(qty) AS total_stored'))
                ->where([["inventory_id", $n->inventory_id],["status", 1]])
                ->groupBy('inventory_id')
                ->pluck('total_stored');
            $a = count($locQty[$i])==1?(int)$locQty[$i][0]:0;
            $assignedQty[$i] = DB::table('inventory_assigned')->select(DB::raw('SUM(assigned_qty) AS total_assigned'))
                ->where([["inventory_id", $n->inventory_id],["status", 1]])
                ->groupBy('inventory_id')
                ->pluck('total_assigned');
            $b = count($assignedQty[$i])==1?(int)$assignedQty[$i][0]:0;

            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            $n->item_name = $n->name;
            $n->total_asset = $a + $b;
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $j=0;
                foreach($tree as $t){
                    $x[$j] = $t->name;
                    $n->asset_name = implode(" ", $x);
                    $j++;
                }
            }
            $i++;
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $newlyAdded;
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
        $inventory_id = $request->input("inventory_id");
        $list = InventoryLocation::where([["inventory_id", $inventory_id],["status", 1]])
            ->orderBy('id', 'ASC')
            ->get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;

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
            'id' => 'required',
            'qty' => 'required|integer|min:1'
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $item = InventoryLocation::where("id", $request->id)->first();

            $i = new InventoryLocation;
            $i->inventory_id = $item->inventory_id;
            $i->qty = $request->qty;
            $i->location = $item->location;
            $i->status = 2;
            $i->created_at = strtotime("now");
            $i->updated_at = 0;
            $i->save();

            $u = InventoryLocation::find($request->id);
            $u->qty = $item->qty - $request->qty;
            $u->updated_at = strtotime("now");
            $u->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = [];
        }

        return Response::json($response);
    }

    public function transferInventory(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'newLoc' => 'required',
            'qty' => 'required|integer|min:1'
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $location = InventoryLocation::where("id", $request->id)->first();
            if(is_numeric($request->newLoc)){
                $newLoc = InventoryLocation::where("id", $request->newLoc)->first();
                $n = InventoryLocation::find($request->newLoc);
                $n->qty = $request->qty + $newLoc->qty;
                $n->updated_at = strtotime("now");
                $n->save();
            }else{
                $n = new InventoryLocation;
                $n->inventory_id = $location->inventory_id;
                $n->qty = $request->qty;
                $n->location = $request->newLoc;
                $n->status = 1;
                $n->created_at = strtotime("now");
                $n->updated_at = 0;
                $n->save();
            }
            $u = InventoryLocation::find($request->id);
            $u->qty = $location->qty - $request->qty;
            $u->updated_at = strtotime("now");
            $u->save();

            $response['status'] = 'Failed';
            $response['code'] = 200;
            $response['data'] = [];
        }

        return Response::json($response);
    }

    public function editInventory(Request $request){
        $validator = Validator::make($request->all(), [
            'inventory_id' => 'required',
            'description' => 'required',
            'specification' => 'nullable',
            'type' => 'required',
            'unit' => 'required',
            'or' => 'required'

        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $inv = Inventory::find($request->inventory_id);
            $inv->type = $request->type;
            $inv->unit = $request->unit;
            $inv->description = $request->description;
            $inv->or = $request->or;

            if($request->purchase_price !== null) {
                $inv->purchase_price = $request->purchase_price;
            }
            if($request->specification !== null) {
                $inv->specification = $request->specification;
            }
            $inv->updated_at = strtotime("now");
            $inv->save();

            foreach (json_decode($request->location, true) as $k => $v) {
                if($v["id" . $k] != "") {
                    $locUpd = InventoryLocation::find($v["id" . $k]);
                    $locUpd->qty = $v["assigned_qty" . $k];
                    $locUpd->location = $v["location" . $k];
                    $locUpd->updated_at = strtotime("now");
                    $locUpd->save();
                }
                else
                {
                    $locIns = new InventoryLocation;
                    $locIns->inventory_id = $request->inventory_id;
                    $locIns->qty = $v["assigned_qty".$k];
                    $locIns->location = $v["location".$k];
                    $locIns->created_at = strtotime("now");
                    $locIns->updated_at = 0;
                    $locIns->save();
                }
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $inv;

        }
        return Response::json($response);
    }
}


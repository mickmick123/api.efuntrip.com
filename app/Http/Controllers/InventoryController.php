<?php

namespace App\Http\Controllers;

use App\Inventory;
use App\Company;
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
            'qty' => 'required',
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
            $inv->qty = $request->qty;
            $inv->unit = $request->unit;
            $inv->created_at = strtotime("now");
            $inv->updated_at = strtotime("now");
            $inv->save();

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

        $inv = Inventory::where('company_id', $request->company_id)
            ->where(function ($q) use ($categ, $request) {
                $q->whereIn('category_id', $categ);
                $q->orwhere('category_id', $request->category_id);
            })->delete();

        $list = [$icat];

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = 'Category and Inventory has been Deleted!';
        $response['data1'] = $list;
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
            ->select(DB::raw('co.name as company_name, inventory.*, (SELECT SUM(assigned_qty) from inventory_assigned a WHERE a.inventory_id = inventory.inventory_id GROUP BY a.inventory_id) as total_assigned'))
            ->leftjoin('company as co', 'inventory.company_id', 'co.company_id')
            ->where($filter)
            ->whereIn("category_id", $item_found)
            ->limit($limit)->offset(($page - 1) * $limit)->get()->toArray();

        foreach($list as $n){
            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            $n->created_at = gmdate("F j, Y", $n->created_at);
            $n->updated_at = gmdate("F j, Y", $n->updated_at);
            $n->or = (string)$n->or;
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $n->item_name = $np->name;
                $j=0;
                foreach($tree as $t){
                    $n->x[$j] = $t->name;
                    $glue = " ";
                    if ($id != 0) {
                        $glue = " | ";
                    }
                    $n->asset_name = implode($glue, $n->x);
                    $j++;
                }
            }
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
        $inventory_id = intval($request->input("id", 0));

        $a = DB::table('inventory as i')
            ->select(
                DB::raw('i.qty - CASE
                                          WHEN (SELECT SUM(assigned_qty) from inventory_assigned a WHERE a.inventory_id = i.inventory_id GROUP BY a.inventory_id) IS NULL THEN 0
                                          ELSE (SELECT SUM(assigned_qty) from inventory_assigned a WHERE a.inventory_id = i.inventory_id GROUP BY a.inventory_id)
                                        END AS assigned_qty'))
            ->where("inventory_id", "=", $inventory_id)
            ->get();

        foreach ($a as $n) {
            $n->assigned_to = "NA";
            $n->location_site = "NA";
            $n->location_detail = "NA";
            $n->model = "NA";
            $n->serial = "NA";
            $quantity = (int)$n->assigned_qty;
        }
        if ($quantity == 0) {
            $a = [];
        }

        $b = InventoryAssigned::where("inventory_id", "=", $inventory_id)->get();

        $list = array_merge(array($b), array($a));

        $data = [];
        foreach($list as $i){
            foreach($i as $j){
                $data[] = $j;
            }
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
            'assigned_to' => 'required|min:3',
            'location_site' => 'required|min:3',
            'location_detail' => 'required|min:3',
            'assigned_qty' => 'required|integer|min:1',
            'model' => 'nullable',
            'serial' => 'nullable'
        ];
        $messages = [
            'required' => 'This field is required.',
            'integer' => 'Please input numbers only.',
            'assigned_to.min' => 'Please input minimum of 3 character.',
            'location_site.min' => 'Please input minimum of 3 character.',
            'location_detail.min' => 'Please input minimum of 3 character.',
            'assigned_qty.min' => 'Please input a valid number.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);

        $response = array();
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $remaining = DB::table('inventory as i')
                ->select(
                    DB::raw('i.qty - CASE
                                          WHEN (SELECT SUM(assigned_qty) from inventory_assigned a WHERE a.inventory_id = i.inventory_id GROUP BY a.inventory_id) IS NULL THEN 0
                                          ELSE (SELECT SUM(assigned_qty) from inventory_assigned a WHERE a.inventory_id = i.inventory_id GROUP BY a.inventory_id)
                                        END AS remaining'))
                ->where("inventory_id", $request->inventory_id)
                ->pluck("remaining");
            $item = DB::table('inventory_assigned')->select(DB::raw('assigned_qty'))->where("id", $request->id)->pluck('assigned_qty');
            if(($request->assigned_qty > (int)$item[0]) && ($request->assigned_qty - (int)$item[0]) > (int)$remaining[0]) {
                $response['status'] = 'Failed';
                $response['errors'] = array('assigned_qty' => array("Invalid quantity."));
                $response['code'] = 422;
            }
            else
            {
                $inv = InventoryAssigned::find($request->id);
                $inv->assigned_to = $request->assigned_to;
                $inv->location_site = $request->location_site;
                $inv->location_detail = $request->location_detail;
                $inv->assigned_qty = $request->assigned_qty;
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
        }

        return Response::json($response);
    }

    public function assignInventory(Request $request){
        $rules = [
            'inventory_id' => 'required',
            'assigned_to' => 'required|min:3',
            'location_site' => 'required|min:3',
            'location_detail' => 'required|min:3',
            'assigned_qty' => 'required|integer|min:1',
            'model' => 'nullable',
            'serial' => 'nullable'
        ];
        $messages = [
            'required' => 'This field is required.',
            'integer' => 'Please input numbers only.',
            'assigned_to.min' => 'Please input minimum of 3 character.',
            'location_site.min' => 'Please input minimum of 3 character.',
            'location_detail.min' => 'Please input minimum of 3 character.',
            'assigned_qty.min' => 'Please input a valid number.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);

        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $remaining = DB::table('inventory as i')
                ->select(
                    DB::raw('i.qty - CASE
                                          WHEN (SELECT SUM(assigned_qty) from inventory_assigned a WHERE a.inventory_id = i.inventory_id GROUP BY a.inventory_id) IS NULL THEN 0
                                          ELSE (SELECT SUM(assigned_qty) from inventory_assigned a WHERE a.inventory_id = i.inventory_id GROUP BY a.inventory_id)
                                        END AS remaining'))
                ->where("inventory_id", $request->inventory_id)
                ->pluck("remaining");
            if($request->assigned_qty > (int)$remaining[0]) {
                $response['status'] = 'Failed';
                $response['errors'] = array('assigned_qty' => array("Invalid quantity."));
                $response['code'] = 422;
            }
            else
            {
                $inv = new InventoryAssigned;
                $inv->inventory_id = $request->inventory_id;
                $inv->assigned_to = $request->assigned_to;
                $inv->location_site = $request->location_site;
                $inv->location_detail = $request->location_detail;
                $inv->assigned_qty = $request->assigned_qty;
                if($request->model !== null) {
                    $inv->model = $request->model;
                }
                if($request->serial !== null) {
                    $inv->serial = $request->serial;
                }
                $inv->created_at = strtotime("now");

                $inv->save();

                $response['status'] = 'Success';
                $response['code'] = 200;
                $response['data'] = $inv;
            }
        }
        return Response::json($response);
    }

    public function getNewlyAdded()
    {
        $newlyAdded = DB::table('inventory')
            ->select(DB::raw('co.name as company, category_id, inventory.company_id, inventory.name, inventory.qty as total_asset'))
            ->leftjoin('company as co', 'inventory.company_id', 'co.company_id')
            ->orderBy('inventory_id','DESC')
            ->limit(10)
            ->get();
        foreach($newlyAdded as $n){
            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            $n->item_name = $n->name;
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $j=0;
                foreach($tree as $t){
                    $x[$j] = $t->name;
                    $n->asset_name = implode(" ", $x);
                    $j++;
                }
            }
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

    public function editInventory(Request $request){
        $validator = Validator::make($request->all(), [
            'inventory_id' => 'required',
            'description' => 'nullable',
            'specification' => 'nullable',
            'type' => 'required',
            'qty' => 'required|integer|min:1',
            'unit' => 'required',
            'purchase_price' => 'required|numeric',
            'or' => 'required'

        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $item = DB::table('inventory_assigned')->select(DB::raw('SUM(assigned_qty) as total_assigned'))
                ->where("inventory_id", $request->inventory_id)
                ->groupBy("inventory_id")
                ->pluck('total_assigned');
            if($request->qty < (int)$item[0])
            {
                $response['status'] = 'Failed';
                $response['errors'] = array("qty" => array("Invalid quantity"));
                $response['code'] = 422;
            }
            else {
                $inv = Inventory::find($request->inventory_id);
                $inv->type = $request->type;
                $inv->qty = $request->qty;
                $inv->unit = $request->unit;
                $inv->purchase_price = $request->purchase_price;
                $inv->or = $request->or;

                if($request->description !== null) {
                    $inv->description = $request->description;
                }
                if($request->specification !== null) {
                    $inv->specification = $request->specification;
                }
                $inv->updated_at = strtotime("now");
                $inv->save();

                $response['status'] = 'Success';
                $response['code'] = 200;
                $response['data'] = $inv;
            }

        }
        return Response::json($response);
    }
}


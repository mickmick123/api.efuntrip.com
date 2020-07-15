<?php

namespace App\Http\Controllers;

use App\Inventory;
use App\Company;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\InventoryParentCategory;
use App\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\PageHelper;

class InventoryController extends Controller
{
    public function getAllInventoryCategories(){
        $list = Company::all();
        foreach($list as $l){
            $l->categories = [];
            $l->categories = InventoryParentCategory::with(['subCategories' => function($q) use($l) {
                    $q->where('company_id', '=', $l->company_id)
                    ->with(['subCategories' => function($q) use($l) {
                        $q->where('company_id', '=', $l->company_id)
                        ->with(['subCategories' => function($q) use($l) {
                            $q->where('company_id', '=', $l->company_id)
                            ->with(['subCategories' => function($q) use($l) {
                                $q->where('company_id', '=', $l->company_id)
                                ->with(['subCategories' => function($q) use($l) {
                                    $q->where('company_id', '=', $l->company_id);
                                }]);
                            }]);
                        }]);
                    }]);
                }])
                ->where('company_id',$l->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')
                ->where('parent_id', '0')
                ->orderBy('inventory_category.name')
                ->get();
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = ['company'=>$list];
        return Response::json($response);
    }

    //Start-------------------------------------------

    public function list(Request $request)
    {
        $name = $request->input("q", "");
        $id = intval($request->input("inventory_id", 0));
        $co_id = intval($request->input("company_id", 0));
        $ca_id = intval($request->input("category_id", 0));
        $page = intval($request->input("page", 1));
        $pageSize = intval($request->input("limit", 20));

        $any = array();
        if ($name != "")
        {
            $any[] = ["name", "LIKE", "%$name%"];
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

        if ($ca_id != 0)
        {
            $category_ids = array($ca_id);
        }else {
            $category_ids = InventoryCategory::where($any)->pluck('category_id');
        }

        if (count($category_ids)==0)
        {
            $response['status'] = 'No results found.';
            $response['code'] = 404;
            $response['data'] = '';
            return Response::json($response);
        }

        $cats = InventoryParentCategory::whereIn('category_id', $category_ids)->get();

        $items = [];
        foreach($cats as $c){
            $items[] = InventoryParentCategory::where('category_id', $c->category_id)->where('company_id', $c->company_id)->first()->getAllChildren()->pluck('category_id');
        }

        $items2 = [];
        $test = null;
        foreach($items as $i){
            foreach($i as $j){
                $items2[] = $j;
            }
        }

        $item_found = [];
        if(count($items2)>0){
            $item_found = $items2;
        }
        else{
            $item_found = $category_ids;
        }

        $page_obj = new PageHelper($page, $pageSize);
        if (empty($page_obj)) {
            return array();
        }
        $count = DB::table('inventory')->where($filter)
            ->wherein('category_id',$item_found)->count();
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
            ->whereIn('category_id',$item_found)
            ->limit($limit)->offset(($page - 1) * $limit)->get()->toArray();

        foreach($list as $n){
            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            $n->date_purchased = gmdate("F j, Y", $n->date_purchased);
            $n->created_at = gmdate("F j, Y", $n->created_at);
            $n->updated_at = gmdate("F j, Y", $n->updated_at);
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $n->item_name = $np->name;
                $j=0;
                foreach($tree as $t){
                    $x[$j] = $t->name;
                    $n->asset_name = implode(" ", $x);
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

    public function getNewlyAdded()
    {
        $newlyAdded = DB::table('inventory')
            ->select(DB::raw('COUNT(inventory.category_id) as total_asset, co.name as company, category_id, inventory.company_id'))
            ->leftjoin('company as co', 'inventory.company_id', 'co.company_id')
            ->groupBy('category_id','inventory.company_id')
            ->orderBy('inventory_id','DESC')
            ->limit(10)
            ->get();
        foreach($newlyAdded as $n){
            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $n->item_name = $np->name;
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
        $page = intval($request->input("page", 1));
        $pageSize = intval($request->input("limit", 5));

        $page_obj = new PageHelper($page, $pageSize);
        if (empty($page_obj)) {
            return array();
        }
        $count = DB::table('inventory')->where("is_assigned", "!=", 0)->count();

        if ($count === 0)
        {
            $response['status'] = 'success';
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

        $ln = DB::table('inventory')
            ->where("is_assigned", "!=", 0)
            ->orderBy('inventory_id','DESC')
            ->limit($limit)->offset(($page - 1) * $limit)->get()->toArray();
        foreach($ln as $n){
            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            $n->datePurchased = gmdate("F j, Y", $n->date_purchased);
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $n->itemName = $np->name;
                $i=0;
                foreach($tree as $t){
                    $x[$i] = $t->name;
                    $n->categoryName = implode(" ", $x);
                    $i++;
                }
            }
        }

        $j=0; $list = [];
        foreach ($ln as $d)
        {
            $list[$j]['inventory_id'] = $d->inventory_id;
            $list[$j]['itemName'] = $d->itemName;
            $list[$j]['type'] = $d->type;
            $list[$j]['model'] = $d->model;
            $list[$j]['qty'] = $d->qty;
            $list[$j]['unit'] = $d->unit;
            $list[$j]['date_purchased'] = gmdate("F j, Y", $d->date_purchased);
            $list[$j]['assigned_to'] = $d->assigned_to;
            $j++;
        }
        $data = array(
            "totalNum" => $page_obj->total_num,
            "currPage" => $page_obj->curr_page,
            "list" => $list,
            "pageSize" => $page_obj->page_size,
            "totalPage" => $page_obj->total_page,
        );
        $response['status'] = 'success';
        $response['code'] = 200;
        $response['data'] = $data;

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
            'notes' => 'nullable',
            'model' => 'required',
            'type' => 'required',
            'qty' => 'required',
            'unit' => 'required',
            'location_site' => 'required',
            'location_detail' => 'required',
            'purchase_price' => 'required|numeric',
            'or' => 'required',
            'assigned_to' => 'nullable',

            // 'serial_no' => 'required',
            // 'date_purchased' => 'required',
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $filter = array(
                '', 'NA', 'N/A', 'N A', 'Not Applicable', 'Not Yet Consumed'
            );

            $inv = Inventory::find($request->inventory_id);
            $inv->model = $request->model;
            $inv->type = $request->type;
            $inv->qty = $request->qty;
            $inv->unit = $request->unit;
            $inv->location_site = $request->location_site;
            $inv->location_detail = $request->location_detail;
            $inv->purchase_price = $request->purchase_price;
            $inv->or = $request->or;

            if($request->notes !== null) {
                $inv->notes = $request->notes;
            }
            if($request->assigned_to !== null) {
                $assigned_to = trim(preg_replace('/\s+/', ' ', $request->assigned_to));
                $inv->assigned_to = $assigned_to;
            }
            if (!in_array($assigned_to, $filter))
            {
                $inv->is_assigned = 1;
            }
            else
            {
                $inv->is_assigned = 0;
            }
            $inv->updated_at = strtotime("now");
            $inv->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $inv;

        }
        return Response::json($response);
    }

    public function assignInventory(Request $request){
        $validator = Validator::make($request->all(), [
                'inventory_id' => 'required',
                'assigned_to' => 'required'
            ]
        );

        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $filter = array(
                '', 'NA', 'N/A', 'N A', 'Not Applicable', 'Not Yet Consumed'
            );
            $assigned_to = trim(preg_replace('/\s+/', ' ', $request->assigned_to));

            $inv = Inventory::find($request->inventory_id);
            $inv->assigned_to = $assigned_to;
            if (!in_array($assigned_to, $filter))
            {
                $inv->is_assigned = 1;
            }
            else
            {
                $inv->is_assigned = 0;
            }

            $inv->updated_at = strtotime("now");
            $inv->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $inv;
        }

        return Response::json($response);
    }

    // End---------------------------------------------

    public function addInventory(Request $request){
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
            'category_id' => 'required',
            'serial_no' => 'required',
            'model' => 'required',
            'date_purchased' => 'required',
            'status' => 'required',
            'note' => 'nullable',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $inv = new Inventory;
            $inv->company_id = $request->company_id;
            $inv->category_id = $request->category_id;
            $inv->serial_no = $request->serial_no;
            $inv->model = $request->model;
            $inv->date_purchased = strtotime($request->date_purchased);
            $inv->inventory_img = md5($request->imgBase64) . '.' . explode('.', $request->imgName)[1];
            $inv->status = $request->status;
            $inv->notes = $request->notes;
            $inv->assigned_to = 0;
            $inv->created_at = strtotime("now");
            $inv->updated_at = strtotime("now");
            $inv->save();
            $this->uploadCategoryAvatar($request,'inventories/');

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
            'name_chinese' => 'required',
            'description' => 'nullable',
            'status' => 'nullable',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $catExist = InventoryCategory::where('name','=',$request->name)->get();

            if(count($catExist) !== 0){
                $categ = InventoryCategory::find($catExist[0]->category_id);
            }else{
                $categ = new InventoryCategory();
                $categ->name = $request->name;
                $categ->name_chinese = $request->name_chinese;
                $categ->description = $request->description;
                $categ->category_img = md5($request->imgBase64) . '.' . explode('.', $request->imgName)[1];
                $this->uploadCategoryAvatar($request,'inventories/categories/');
                $categ->status = 1;
                $categ->save();
            }
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
            'id' => 'required',
            'category_id' => 'required',
            'company_id' => 'required',
            'parent_id' => 'required',
            'name' => 'required|unique:inventory_category',
            'name_chinese' => 'required',
            'description' => 'nullable'
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $categ = InventoryCategory::find($request->category_id);
            $categ->name = $request->name;
            $categ->name_chinese = $request->name_chinese;
            $categ->description = $request->description;

            if($request->imgBase64 !== null) {
                $categ->category_img = str_replace(' ', '_', $request->name) . date('Ymd_His') . '.' . explode('.', $request->imgName)[1];
                $this->uploadCategoryAvatar($request, 'inventories/categories/');
            }
            if($request->status !== null) {
                $categ->status = $request->status;
            }
            $categ->save();

            $parentCateg = InventoryParentCategory::find($request->id);
            $parentCateg->company_id = $request->company_id;
            $parentCateg->category_id = $request->category_id;
            $parentCateg->parent_id = $request->parent_id;
            $parentCateg->save();

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

    public function deleteInventoryCategory(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:inventory_category',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $categ = InventoryCategory::find($request->category_id);
            $categ->delete();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $categ;
        }
        return Response::json($response);
    }

    public function test()
    {
        $cats = InventoryParentCategory::with('subCategories')
            ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')
//            ->where('parent_id', '0')
            ->where('inventory_category.status', '1')
            ->orderBy('inventory_category.name', 'asc')
            ->get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $cats;

        return Response::json($response);
//        $list = DB::table('inventory_parent_category AS ipcat')
//            ->leftJoin('company AS com','ipcat.company_id','=','com.company_id')
//            ->leftJoin('inventory_category AS icatpa','ipcat.parent_id','=','icatpa.category_id')
//            ->leftJoin('inventory_category AS icat','ipcat.category_id','=','icat.category_id')
//            ->leftJoin('inventory AS inv', function($join){
//                $join->on('com.company_id', '=', 'inv.company_id');
//                $join->on('icat.category_id','=','inv.category_id');
//            })
////            ->where('com.name','=','Mart')
//            ->where('icatpa.name','=','Computer')
//            ->get(['inv.inventory_id AS InventoryID',
//                'com.name AS CompanyName',
//                'icatpa.name AS Tree',
//                'icat.name AS ItemName',
//                'inv.date_purchased AS DatePurchased',
//                'inv.status AS Status',
//                'inv.assigned_to AS AssignedTo']);
//
//
//        $response['status'] = 'Success';
//        $response['code'] = 200;
//        $response['data'] = $list;
//        return Response::json($response);
    }

    public function uploadCategoryAvatar($data,$folder) {
        $img64 = $data->imgBase64;

        list($type, $img64) = explode(';', $img64);
        list(, $img64) = explode(',', $img64);

        if($img64!=""){ // storing image in storage/app/public Folder
            \Storage::disk('public')->put($folder . md5($data->imgBase64) . '.' . explode('.', $data->imgName)[1], base64_decode($img64));
        }
    }
}


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

    public function list(Request $request)
    {
        $name = $request->input("q", "");
        $id = intval($request->input("inventory_id", 0));
        $page = intval($request->input("page", 1));
        $pageSize = intval($request->input("limit", 3));

        $any = array();
        if ($name != "")
        {
            $any[] = ["name", "LIKE", "%$name%"];
        }
        $inventoryId = array();
        if ($id != 0)
        {
            $inventoryId[] = ["inventory_id", $id];
        }

        $category_ids = InventoryCategory::where($any)->pluck('category_id');

//        $company_ids = Company::where("name", "LIKE", "%Mart%")->pluck('company_id');

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
        $count = DB::table('inventory')->wherein('category_id',$item_found)->count();
        $page_obj->set_count($count);
        if (empty($count)) {
            return array();
        }
        $limit = $page_obj->page_size;
        $page = $page_obj->curr_page;

        $list = DB::table('inventory')
            ->select(DB::raw('inventory_id, co.name as companyName,
                    CASE WHEN assigned_to = 0 THEN "Not Yet Consumed"
                    ELSE CONCAT(u.first_name, " ",  u.last_name)
                    END AS assignedTo,
                    category_id, inventory.company_id, serial_no, model, date_purchased, inventory_img as inventoryImg, notes,
                    CASE
                        WHEN status = 1 THEN "Brand New"
                        WHEN status = 2 THEN "Second Hand"
                    END as status
                '))
            ->leftjoin('company as co', 'inventory.company_id', 'co.company_id')
            ->leftJoin('users as u', 'inventory.assigned_to', '=', 'u.id')
            ->where($inventoryId)
            ->whereIn('category_id',$item_found)
            ->orderBy('inventory_id','DESC')
            ->limit($limit)->offset(($page - 1) * $limit)->get()->toArray();

        foreach($list as $n){
            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            $n->itemName = '';
            $n->datePurchased = gmdate("F j, Y", $n->date_purchased);
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $n->itemName = $np->name;
                $n->categoryName = '';
                foreach($tree as $key => $val){
                    if($key === 0) continue;
                    $n->categoryName = $val->name;
                }
                $j=1;
                foreach ($tree as $key => $val){
                    $x[$j] = $val->name;
                    if($key === 1) continue;
                    $n->assetName = implode(" ", $x);
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
                $x = [];
                foreach($tree as $t){
                    $x[] = $t->name;
                    $n->asset_name = implode(" ", $x)." ".$np->name;
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
        $count = DB::table('inventory')->where("assigned_to", "!=", 0)->count();

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

        $list = DB::table('inventory')
            ->select(DB::raw('inventory_id, co.name as companyName,
                    CASE WHEN assigned_to = 0 THEN "Not Yet Consumed"
                    ELSE CONCAT(u.first_name, " ",  u.last_name)
                    END AS assignedTo,
                    category_id, inventory.company_id, serial_no, model, date_purchased, inventory_img as inventoryImg, notes,
                    CASE
                        WHEN status = 1 THEN "Brand New"
                        WHEN status = 2 THEN "Second Hand"
                    END as status
                '))
            ->leftjoin('company as co', 'inventory.company_id', 'co.company_id')
            ->leftJoin('users as u', 'inventory.assigned_to', '=', 'u.id')
            ->where("assigned_to", "!=", 0)
            ->orderBy('inventory_id','DESC')
            ->limit($limit)->offset(($page - 1) * $limit)->get()->toArray();
        foreach($list as $n){
            $nparent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
            $n->datePurchased = gmdate("F j, Y", $n->date_purchased);
            $n->itemName = '';
            foreach($nparent as $np){
                $tree = $np->parents->reverse();
                $x = [];
                $n->itemName = $np->name;
                $n->categoryName = '';
                foreach($tree as $key => $val){
                    if($key === 0) continue;
                    $n->categoryName = $val->name;
                }
                $j=1;
                foreach ($tree as $key => $val){
                    $x[$j] = $val->name;
                    if($key === 1) continue;
                    $n->assetName = implode(" ", $x);
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


    public function editInventory(Request $request){
        $validator = Validator::make($request->all(), [
            'inventory_id' => 'required',
            'company_id' => 'required',
            'category_id' => 'required',
            'serial_no' => 'required',
            'model' => 'required',
            'date_purchased' => 'required',
            'status' => 'nullable',
            'assigned_to' => 'nullable',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $inv = Inventory::find($request->inventory_id);
            $inv->company_id = $request->company_id;
            $inv->category_id = $request->category_id;
            $inv->serial_no = $request->serial_no;
            $inv->model = $request->model;
            $inv->date_purchased = $request->date_purchased;

            if($request->imgBase64 !== null) {
                $inv->inventory_img = str_replace(' ', '_', $request->serial_no) . date('Ymd_His') . '.' . explode('.', $request->imgName)[1];
                $this->uploadCategoryAvatar($request,'inventories/');
            }
            if($request->status !== null) {
                $inv->status = $request->status;
            }
            if($request->assigned_to !== null) {
                $inv->assigned_to = $request->assigned_to;
            }
            $inv->updated_at = strtotime("now");
            $inv->save();

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


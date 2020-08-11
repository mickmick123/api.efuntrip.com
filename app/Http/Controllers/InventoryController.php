<?php

namespace App\Http\Controllers;

use App\Company;
use App\Inventory;
use App\InventoryLogs;
use App\InventoryLocation;
use App\User;
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
            $inv->unit = $request->unit;
            $inv->created_at = strtotime("now");
            $inv->updated_at = strtotime("now");
            $inv->save();

            $qty = 0;
            foreach (json_decode($request->location_storage, true) as $k=>$v) {
                if($v["quantity".$k] !== null && $v["location".$k] !== null){
                    $qty = $qty + $v["quantity".$k];
                    for($i = 0; $i < $v["quantity".$k]; $i++){
                        $ass = new InventoryAssigned;
                        $ass->inventory_id = $inv->inventory_id;
                        $ass->model = $request->model;
                        $ass->serial = $request->serial_no;
                        $ass->type = 'Purchased';
                        $ass->hasOR = $request->or;
                        $ass->purchase_price = $request->purchase_price;
                        $ass->location_site = $v["location".$k];
                        $ass->created_by = $user->id;
                        $ass->updated_by = $user->id;
                        $ass->created_at = strtotime("now");
                        $ass->updated_at = strtotime("now");
                        $ass->save();
                    }
                }
            }
            $ilog = new InventoryLogs;
            $ilog->inventory_id = $inv->inventory_id;
            $ilog->type = 'Stored';
            $ilog->reason = $qty;
            $ilog->created_by = $user->id;
            $ilog->created_at = strtotime("now");
            $ilog->save();

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

        if($co_id !== 0) {
            $arr = array();
            if($ca_id !== 0 ){
                $arr[] = ['pc.parent_id', '=', $ca_id];
            }
            if($name == ""){
                $arr[] = ['pc.parent_id', '=', $ca_id];
            }
            if($name !==""){
                $arr[] = ['c.name','LIKE', $name];
            }
            $category = DB::table('inventory_parent_category AS pc')
                ->leftJoin('company AS com', 'pc.company_id', '=', 'com.company_id')
                ->leftJoin('inventory_category AS c', 'pc.category_id', '=', 'c.category_id')
                ->where('pc.company_id', '=', $co_id)
                ->where($arr)
                ->orderBy('c.name')
                ->get();
        }else{
            $category = Company::all();
        }

        $list = DB::table('inventory')
            ->select(DB::raw('
                co.name as company_name, inventory.*,
                (SELECT COUNT(id) from inventory_assigned a WHERE a.inventory_id = inventory.inventory_id AND a.status !=3) as qty
            '))
            ->leftjoin('company as co', 'inventory.company_id', 'co.company_id')
            ->where($filter)
            ->whereIn("category_id", $item_found)
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
                $j=0;
                foreach($tree as $t){
                    $n->x[$j] = $t->name;
                    $n->asset_name = implode(" | ", $n->x);
                    $n->path = implode(" | ", $n->x)." | ".$n->item_name;
                    $j++;
                }
            }


            $i++;
        }

        $data = array(
            "category" => $category,
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
            ->select(DB::raw('a.*, i.type as inventory_type'))
            ->leftjoin('inventory as i', 'a.inventory_id', 'i.inventory_id')
            ->where("a.inventory_id", "=", $inventory_id)
            ->whereIn("status", [1,2])
            ->orderBy('a.status','DESC')
            ->orderBy('a.created_at','DESC')
            ->get();
        foreach ($data as $n) {
            if($n->status === 1) {
                $status = "Assigned";
            }else
                if($n->status === 2) {
                $status = "Storage";
            }
            $n->assigned_to = $n->assigned_to?$n->assigned_to:'NA';
            $n->status = $status;
            $n->assigned_qty = 1;
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
            'model' => 'nullable',
            'serial' => 'nullable'
        ];
        $messages = [
            'required' => 'This field is required.',
            'integer' => 'Please input numbers only.',
            'assigned_to.min' => 'Please input minimum of 3 character.',
            'location_site.min' => 'Please input minimum of 3 character.',
            'location_detail.min' => 'Please input minimum of 3 character.'
        ];
        $validator = Validator::make($request->all(), $rules, $messages);

        $response = array();
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $user = Auth::user();
            $inv = InventoryAssigned::find($request->id);
            $inv->assigned_to = $request->assigned_to;
            $inv->location_site = $request->location_site;
            $inv->location_detail = $request->location_detail;
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
            'location' => 'required',
            'item_name' => 'required'
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $now = strtotime("now");
            $items = InventoryAssigned::where("id", $request->assigned_id)->first();

            //Logs
            $user=Auth::user();
            $log = new InventoryLogs;
            $log->inventory_id = $request->inventory_id;
            $log->type = "Retrieved";
            $log->reason = "1 $request->item_name has been retrieved from $items->assigned_to with serial number $items->serial.";
            $log->created_by = $user->id;
            $log->created_at = $now;
            $log->save();

            $iUpd = InventoryAssigned::find($request->assigned_id);
            $iUpd->assigned_to = "NA";
            $iUpd->location_site = $request->location;
            $iUpd->location_detail = "";
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
            'assigned_to' => 'required',
            'location_site' => 'required',
            'location_detail' => 'required',
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
            $data = InventoryAssigned::find($request->assigned_id);
            $data->inventory_id = $request->inventory_id;
            $data->assigned_to = $request->assigned_to;
            $data->location_site = $request->location_site;
            $data->location_detail = $request->location_detail;
            $data->status = 1;
            $data->model = $request->model;
            $data->serial = $request->serial;
            $data->updated_at = strtotime("now");
            $data->save();

            //Logs
            $user = Auth::user();
            $log = new InventoryLogs;
            $log->inventory_id = $request->inventory_id;
            $log->type = "Assigned";
            $log->reason = "1 $request->item_name has been assigned to $request->assigned_to.";
            $log->created_by = $user->id;
            $log->created_at = strtotime("now");
            $log->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = [];
        }
        return Response::json($response);
    }

    public function getNewlyAdded(Request $request, $perPage = 10)
    {
        $sort = $request->sort;
        $search = $request->search;

        $newlyAdded = Inventory::select(['inventory.*',
            DB::raw("(inventory.name) AS name,
                (co.name) AS company"),
                'datetime' => function ($query) {
                    $query->select(DB::raw("FROM_UNIXTIME(updated_at, '%m/%d/%Y %h:%i:%s %p') AS datatime"))
                        ->from('inventory_assigned')
                        ->whereColumn('inventory_id', 'inventory.inventory_id')
                        ->orderBy('updated_at','DESC')
                        ->limit(1);
                },
                'total_asset' => function ($query) {
                    $query->select(DB::raw("count('inventory_id')"))
                        ->from('inventory_assigned')
                        ->whereColumn('inventory_id', 'inventory.inventory_id')
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
                        ->from('inventory_assigned AS iass')
                        ->leftJoin('users','iass.created_by','users.id')
                        ->whereColumn('iass.inventory_id', 'inventory.inventory_id')
                        ->orderBy('iass.updated_at','DESC')
                        ->limit(1);
                },
            ])
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
        $location = InventoryAssigned::select(DB::raw('location_site as location'))
                ->where("inventory_id", $request->inventory_id)
                ->groupBy('location_site')
                ->orderBy('id', 'ASC')
                ->get();

        foreach ($location as $n) {
            $total_qty = InventoryAssigned::select(DB::raw('COUNT(id) as total_qty'))
                ->where([["inventory_id", $request->inventory_id],["location_site","=",$n->location]])
                ->whereIn("status", [1,2])
                ->groupBy('location_site')
                ->pluck('total_qty');
            $remaining = InventoryAssigned::select(DB::raw('COUNT(id) as remaining'))
                ->where([["inventory_id", $request->inventory_id],["location_site","=",$n->location],["status", 2]])
                ->groupBy('location_site')
                ->pluck('remaining');
            $assigned = InventoryAssigned::select(DB::raw('COUNT(id) as remaining'))
                ->where([["inventory_id", $request->inventory_id],["location_site","=",$n->location],["status", 1]])
                ->groupBy('location_site')
                ->pluck('remaining');

            $n->qty = count($total_qty)>0?$total_qty[0]:0;
            $n->remaining = count($remaining)>0?$remaining[0]:0;
            $n->assigned = count($assigned)>0?$assigned[0]:0;
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $location;

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
            $now = strtotime("now");;
            $items = InventoryAssigned::where("id",$request->assigned_id)->first();
            //Logs
            $user=Auth::user();
            $log = new InventoryLogs;
            $log->inventory_id = $request->inventory_id;
            $log->type = "Disposed";
            $log->reason = "1 $request->item_name with serial number $items->serial has been disposed.";
            $log->created_by = $user->id;
            $log->created_at = $now;
            $log->save();

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
            'location' => 'required'
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $now = strtotime("now");;
            $items = InventoryAssigned::where("id",$request->assigned_id)->first();

            //Logs
            $user=Auth::user();
            $log = new InventoryLogs;
            $log->inventory_id = $request->inventory_id;
            $log->type = "Transferred";
            $log->reason = "1 $request->item_name has been transferred from $items->location_site to $request->location";
            $log->created_by = $user->id;
            $log->created_at = $now;
            $log->save();

            $data = InventoryAssigned::find($request->assigned_id);
            $data->location_site = $request->location;
            $data->updated_at = $now;
            $data->save();

            $response['status'] = 'Success';
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
            'unit' => 'required'
        ]);
        $response = [];
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $inv = Inventory::find($request->inventory_id);
            $inv->description = $request->description;
            $inv->type = $request->type;
            $inv->unit = $request->unit;

            if($request->specification !== null) {
                $inv->specification = $request->specification;
            }
            $inv->updated_at = strtotime("now");
            $inv->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $inv;

        }
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
            //Logs
            $user=Auth::user();
            $count = count(json_decode($request->forms, true));
            $log = new InventoryLogs;
            $log->inventory_id = $request->inventory_id;
            $log->type = "Stored";
            $log->reason = "$count item(s) has been added.";
            $log->created_by = $user->id;
            $log->created_at = strtotime("now");
            $log->save();

            foreach (json_decode($request->forms, true) as $key => $val) {
                $data = new InventoryAssigned;
                $data->inventory_id = $request->inventory_id;
                $data->model = $val["model".$key];
                $data->serial = $val["serial".$key];
                $data->hasOR = $val["hasOR".$key];
                $data->type = $val["type".$key];
                $data->name = $val["name".$key];
                $data->purchase_price = $val["purchase_price".$key];
                $data->remarks = $val["remarks".$key];
                $data->location_site = $val["location".$key];
                $data->status = 2;
                $data->created_by = $user->id;
                $data->updated_by = $user->id;
                $data->created_at = strtotime("now");
                $data->updated_at = strtotime("now");
                $data->save();
            }
            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = [];
        }
        return Response::json($response);
    }

    public function getPurchaseHistory(Request $request){
        $phis = InventoryAssigned::where('inventory_id',$request->inventory_id)->get();

        foreach ($phis as $v){
            $v->date_purchase = Carbon::createFromTimestamp($v->created_at)->format('m/d/Y g:i:s A');
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $phis;

        return Response::json($response);
    }
}


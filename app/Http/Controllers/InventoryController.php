<?php

namespace App\Http\Controllers;

use App\Inventory;
use App\Company;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\InventoryParentCategory;

class InventoryController extends Controller
{
    public function getAllCategories() {
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
    }

    public function getAllCompanies()
    {
        $list = Company::all();
        foreach($list as $l){
            $l->categories = [];
            $l->categories = InventoryParentCategory::with(['subCategories' => function($q) use($l) {
                    $q->where('company_id', '=', $l->company_id); 
                }])
                ->where('company_id',$l->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')
                ->where('parent_id', '0')
                // ->where('inventory_category.status', '1')
                // ->orderBy('inventory_category.name', 'asc')
                ->get();
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = ['company'=>$list];
        return Response::json($response);
    }

    public function test()
    {
        $list = Inventory::limit(10)->orderBy('inventory_id','DESC')->get();
        foreach($list as $n){
            $n->parent = [];
            $n->parent = InventoryParentCategory::where('inventory_parent_category.category_id',$n->category_id)
                ->where('inventory_parent_category.company_id',$n->company_id)
                ->leftJoin('inventory_category', 'inventory_category.category_id', '=', 'inventory_parent_category.category_id')->get();
        }

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;
        return Response::json($response);
    }
}


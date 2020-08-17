<?php

namespace App\Http\Controllers;

use App\Company;
use App\Inventory;
use App\InventoryAssigned;
use App\InventoryCategory;
use App\InventoryConsumables;
use App\InventoryLocation;
use App\InventoryParentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function getCompany(){
        $com = Company::all();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $com;

        return Response::json($response);
    }

    public function addCompany(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $com = new Company();
            $com->name = $request->name;
            $com->name_chinese = $request->name_chinese;
            $com->created_at = strtotime("now");
            $com->updated_at = strtotime("now");
            $com->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $com;
        }
        return Response::json($response);
    }

    public function editCompany(Request $request){
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
            'name' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $com = Company::find($request->company_id);
            $com->name = $request->name;
            $com->name_chinese = $request->name_chinese;
            $com->updated_at = strtotime("now");
            $com->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $com;
        }
        return Response::json($response);
    }


    public function deleteCompany(Request $request){
        Company::where('company_id',$request->company_id)->delete();

        DB::table('inventory_category AS icat')
            ->leftJoin('inventory_parent_category AS ipcat','icat.category_id','=','ipcat.category_id')
            ->where('ipcat.company_id',$request->company_id)
            ->delete();

        InventoryParentCategory::where('company_id',$request->company_id)->delete();

        $invId = Inventory::where('company_id',$request->company_id)->pluck('inventory_id');

        Inventory::where('company_id',$request->company_id)->delete();

        InventoryAssigned::whereIn('inventory_id',$invId)->delete();

        InventoryConsumables::whereIn('inventory_id',$invId)->delete();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = 'Company, Category and Inventory has been Deleted!';

        return Response::json($response);
    }

}

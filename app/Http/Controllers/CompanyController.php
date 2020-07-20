<?php

namespace App\Http\Controllers;

use App\Company;
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
            'name_chinese' => 'required',
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
            'name_chinese' => 'required',
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
        $com = DB::table('company')
            ->where('company_id',$request->company_id)
            ->delete();

        $ipcat = DB::table('inventory_parent_category')
            ->where('company_id',$request->company_id)
            ->delete();

        $icat = DB::table('inventory')
            ->where('company_id',$request->company_id)
            ->delete();

        $list = ['COM'=>$com,'IPCAT'=>$ipcat,'ICAT'=>$icat];

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;

        return Response::json($response);
    }

}

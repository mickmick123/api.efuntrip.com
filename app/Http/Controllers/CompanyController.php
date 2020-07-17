<?php

namespace App\Http\Controllers;

use App\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

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

}

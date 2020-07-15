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
            'description' => 'nullable',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $com = new Company();
            $com->name = $request->name;
            $com->name_chinese = $request->name_chinese;
            $com->description = $request->name_chinese;
            if($request->imgBase64 !== null && $request->imgBase64 !== 'undefined') {
                $com->company_img = md5($request->imgBase64) . '.' . explode('.', $request->imgName)[1];
                $this->uploadCategoryAvatar($request,'companies/');
            }
            $com->created_at = strtotime("now");
            $com->updated_at = strtotime("now");
            $com->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $com;
        }
        return Response::json($response);
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

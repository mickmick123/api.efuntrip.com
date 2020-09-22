<?php

namespace App\Http\Controllers;

use App\Role;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index(){
		$roles = Role::select('id', 'label')->get();

        return $roles;
	}

	public function addRole(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = 'Role has been Added '.str_slug($request->name);
        }
        return Response::json($response);
    }

    public function getRole(Request $request, $perPage = 10) {
        $sort = $request->sort;
        $search = $request->search;

        $list = Role::where('name','LIKE',$search)
            ->when($sort != '', function ($q) use($sort){
                $sort = explode('-' , $sort);
                return $q->orderBy($sort[0], $sort[1]);
            })->paginate($perPage);

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $list;
        return Response::json($response);
    }
}

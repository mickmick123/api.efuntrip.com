<?php

namespace App\Http\Controllers;

use App\Department;

use Response;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    //
    public function getAllDepartments(){
    	$dept = Department::all();

    	$response['status'] = 'Success';
	 	$response['data'] = $dept;
        $response['code'] = 200;
	 	return Response::json($response);
    }
}

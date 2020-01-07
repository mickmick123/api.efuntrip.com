<?php

namespace App\Http\Controllers;

use App\User;

use App\Role;

use App\Branch;

use DB, Auth, Response;

use Illuminate\Http\Request;

class AccountsController extends Controller
{
	//get branch of current user
	public function getBranchAuth(){
		$branch = DB::table('branch_user')->where('user_id', Auth::User()->id)
                ->pluck('branch_id')[0];
        return $branch;
	}
    //get all Cpanel Users
    public function getCpanelUsers() {
	 	$role_id = Role::where('name', 'cpanel-admin')->pluck("id");
	 	$both_branch = Branch::where('name', 'Both')->pluck("id")[0];
    	$auth_branch =  $this->getBranchAuth();
	 	$users = User::select('id', 'email', 'first_name', 'last_name')
	 				->with(array('roles' => function($query){
	 					$query->select('roles.id', 'roles.label');
	 				}))->whereHas('roles', function ($query) use ($role_id) {
                		$query->where('roles.id', '=', $role_id);
	 	 	 		})->whereHas('branches', function ($query) use ($auth_branch, $both_branch) {
                		$query->where('branches.id', '=', $auth_branch)->orWhere('branches.id', '=', $both_branch);
	 	 	 		})->get();

	 	$response['status'] = 'Success';
	 	$response['data'] = $users;
        $response['code'] = 200;
	 	return Response::json($response);
	}
}

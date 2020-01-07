<?php

namespace App\Http\Controllers;

use App\User;

use App\ClientService;

use App\Role;

use App\Branch;

use Carbon\Carbon;

use DB, Auth, Response;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
	//get branch of current user
	public function getBranchAuth(){
		$branch = DB::table('branch_user')->where('user_id', Auth::User()->id)
                ->pluck('branch_id')[0];
        return $branch;
	}

    public function statistics() {
    	$role_id = Role::where('name', 'visa-client')->pluck("id");
    	$both_branch = Branch::where('name', 'Both')->pluck("id")[0];
    	$auth_branch =  $this->getBranchAuth();
        $totals = [];

	 	$totals['total_clients'] = User::whereHas('roles', function ($query) use ($role_id) {
                $query->where('roles.id', '=', $role_id);
	 	 	 	})->whereHas('branches', function ($query) use ($auth_branch, $both_branch) {
                $query->where('branches.id', '=', $auth_branch)->orWhere('branches.id', '=', $both_branch);
	 	 	 	})->count();

	 	$totals['total_services'] = ClientService::whereHas('client.branches', function ($query) use ($auth_branch, $both_branch) {
                $query->where('branches.id', '=', $auth_branch)->orWhere('branches.id', '=', $both_branch);
	 	 	 	})->where('active',1)->count();

	 	$totals['total_services_today'] = ClientService::whereHas('client.branches', function ($query) use ($auth_branch, $both_branch) {
                $query->where('branches.id', '=', $auth_branch)->orWhere('branches.id', '=', $both_branch);
	 	 	 	})->whereDate('created_at', Carbon::today())->where('active',1)->count();

	 	$totals['total_services_yesterday'] = ClientService::whereHas('client.branches', function ($query) use ($auth_branch, $both_branch) {
                $query->where('branches.id', '=', $auth_branch)->orWhere('branches.id', '=', $both_branch);
	 	 	 	})->whereDate('created_at', Carbon::yesterday())->where('active',1)->count();
	
    	$response['status'] = 'Success';
        $response['data'] = $totals;
        $response['code'] = 200;

        return Response::json($response);
	}
}

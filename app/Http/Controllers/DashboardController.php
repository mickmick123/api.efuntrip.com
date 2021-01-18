<?php

namespace App\Http\Controllers;

use App\User;

use App\ClientService;
use App\ClientTransaction;

use App\Role;

use App\Branch;
use App\BranchUser;

use Carbon\Carbon;

use DB, Auth, Response;

use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
	//get branch of current user
	public function getBranchAuth(){
		$branch = BranchUser::where('user_id', Auth::User()->id)
                ->pluck("branch_id");
        // \Log::info($branch);
        return $branch;
	}

    public function statistics() {
    	$role_id = Role::where('name', 'visa-client')->pluck("id");
    	// $both_branch = Branch::where('name', 'Both')->pluck("id")[0];
    	$auth_branch =  $this->getBranchAuth();
        $totals = [];
        $total_cost_today = 0;
        $total_cost_yesterday = 0;
        $today_services_discount = 0;
        $yesterday_services_discount = 0;
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();


        //determine services dated today and yesterday
        $today_services = ClientService::whereDate('created_at', $today)->where('active',1)
                          ->whereHas('client.branches', function ($query) use ($auth_branch) {
                                $query->whereIn('branches.id', $auth_branch);
                          });

        $yesterday_services = ClientService::whereDate('created_at', $yesterday)->where('active',1)
                          ->whereHas('client.branches', function ($query) use ($auth_branch) {
                                $query->whereIn('branches.id', $auth_branch);
                          });

        //get today and yesterday services
        $get_today_services = $today_services->get();
        $get_yesterday_services = $yesterday_services->get();

        //compute for service cost today and yesterday
        foreach ($get_today_services as $serv) {
            $today_services_discount = DB::table('client_transactions')
                ->join('client_services', 'client_transactions.client_service_id', 'client_services.id')
                ->where('client_transactions.type', 'Discount')
                ->where('client_services.id', $serv->id)
                ->sum('client_transactions.amount');
            $total_cost_today += ($serv->cost + $serv->charge + $serv->tip);
        }
         $total_cost_today = $total_cost_today - $today_services_discount;

        foreach ($get_yesterday_services as $serv) {
            $yesterday_services_discount = DB::table('client_transactions')
                ->join('client_services', 'client_transactions.client_service_id', 'client_services.id')
                ->where('client_transactions.type', 'Discount')
                ->where('client_services.id', $serv->id)
                ->sum('client_transactions.amount');
            $total_cost_yesterday += ($serv->cost + $serv->charge + $serv->tip);
        }

        $total_cost_yesterday = $total_cost_yesterday - $yesterday_services_discount;

        //output
	 	$totals['total_clients'] = User::whereHas('roles', function ($query) use ($role_id) {
                $query->where('roles.id', '=', $role_id);
	 	 	 	})->whereHas('branches', function ($query) use ($auth_branch) {
                $query->whereIn('branches.id', $auth_branch);
	 	 	 	})->count();
        $clients = User::whereHas('branches', function ($query) use ($auth_branch) {
                $query->whereIn('branches.id', $auth_branch);
                })->pluck('id');
	 	$totals['total_services'] = ClientService::whereIn('client_id',$clients)->where('active',1)->count();
	 	$totals['total_services_today'] = $today_services->count();
	 	$totals['total_services_yesterday'] = $yesterday_services->count();
        $totals['total_services_cost_today'] = $total_cost_today;
        $totals['total_services_cost_yesterday'] = $total_cost_yesterday;

    	$response['status'] = 'Success';
        $response['data'] = $totals;
        $response['code'] = 200;

        return Response::json($response);
	}


    public function getMonthSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required',
        ]);
        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $data = [];
            $tempData = [];
            $index = 1;
            if($request['month'] > 0){
                $month =  $request['month'];
                $year =  $request['year'];

                $charge = ClientService::whereYear('created_at', '=', $year)
                              ->whereMonth('created_at', '=', $month)
                              ->where(function ($query) {
                                    $query->orwhere('status','complete')
                                          ->orwhere('status','released');
                                })
                              ->where('active',1)
                              ->sum('charge');
                $cost_spent = ClientService::whereYear('created_at', '=', $year)
                              ->whereMonth('created_at', '=', $month)
                              ->where(function ($query) {
                                    $query->orwhere('status','complete')
                                          ->orwhere('status','released');
                                })
                              ->where('active',1)
                              ->value(DB::raw("SUM(cost + tip)"));

                        $stringJson['charge'] = ($charge == null ? 0 : $charge);
                        $stringJson['cost_spent'] = ($cost_spent == null ? 0 : $cost_spent);
            }
            else{
                $stringJson[0]['name'] = 'Total Charge';
                $stringJson[1]['name'] = 'Cost Spent';
                for ($i = 1; $i <= 12; $i++) {
                    for ($ii = 1; $ii <= 2; $ii++) {

                            $month =  str_pad($i, 2, '0', STR_PAD_LEFT);
                            $year =  $request['year'];

                        $charge = ClientService::whereYear('created_at', '=', $year)
                                      ->whereMonth('created_at', '=', $month)
                                      ->where(function ($query) {
                                            $query->orwhere('status','complete')
                                                  ->orwhere('status','released');
                                        })
                                      ->where('active',1)
                                      ->sum('charge');
                        $cost_spent = ClientService::whereYear('created_at', '=', $year)
                                      ->whereMonth('created_at', '=', $month)
                                      ->where(function ($query) {
                                            $query->orwhere('status','complete')
                                                  ->orwhere('status','released');
                                        })
                                      ->where('active',1)
                                      ->value(DB::raw("SUM(cost + tip)"));

                                $stringJson[0]['result'. $i] = ($charge == null ? 0 : $charge);
                                $stringJson[1]['result'. $i] = ($cost_spent == null ? 0 : $cost_spent);
                        $index++;
                    }
                }
            }
            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $stringJson;
        }

        return Response::json($response);
    }

}

<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\ClientService;

use App\ClientTransaction;
use App\ClientEWallet;

use App\ContactNumber;

use App\Group;

use App\User;

use App\Log;

use App\GroupUser;

use App\Financing;
use App\Package;

use App\Branch;
use App\BranchGroup;

use App\Service;
use App\ServiceProfileCost;
use App\ServiceBranchCost;
use App\ServiceProfile;


use Auth, DB, Response, Validator;
//Excel
use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;
//Macoy

use App\Exports\ByServiceExport;
use App\Exports\ByMemberExport;
use App\Exports\ByBatchExport;
use PDF;

use DateTime;


class GroupController extends Controller
{

    private function generateGroupTracking() {
    	$numeric = '0123456789';

    	do {
    		$tracking = 'GL';
	        for($i=0; $i<7; $i++) {
	            $tracking .= $numeric[rand(0, strlen($numeric) - 1)];
	        }
    	} while( Group::where('tracking', $tracking)->count() > 0 );

        return $tracking;
    }


    private function generateServiceTracking() {
         Repack:
         $packId = 'G' . $this->generateRandomString();
         $checkPackage = $this->checkPackage($packId);
         if($checkPackage > 0) :
             goto Repack;
         endif;
         return $packId;
    }

    private function generateRandomString($length = 7) {
      $characters = '0123456789';
      $charactersLength = strlen($characters);
      $randomString = '';
      for ($i=0; $i<$length; $i++) {
          $randomString .= $characters[rand(0, $charactersLength - 1)];
      }
      return $randomString;
    }

    private function checkPackage($packId){
        return Package::where('tracking', $packId)->count();
    }


    public function getGroupEwallet($id) {
        $depo = ClientEWallet::where('group_id', $id)->where('type', 'Deposit')->sum('amount');

        $withdraw = ClientEWallet::where('group_id', $id)->where('type', 'Refund')->sum('amount');

        $groupActiveServices = ClientService::where('active', 1)->where('group_id', null)
                                    ->where(function ($query) {
                                                $query->where('status','!=', 'cancelled');
                                            })->pluck('id');

        $payment = ClientTransaction::where('group_id', $id)
                    ->where('type', 'Payment')
                    ->where(function ($q) {
                        $q->where('client_service_id','!=',null);
                    })
                    ->whereIn('client_service_id', $groupActiveServices)
                    ->sum('amount');

        return $depo - ($withdraw + $payment);
    }

    private function getGroupDeposit($id) {
        return ClientTransaction::where('group_id', $id)->where('type', 'Deposit')->sum('amount');
    }

    private function getGroupPayment($id) {
        $clientActiveServices = ClientService::where('active', 1)->where('group_id', $id)
                                    ->where(function ($query) {
                                                $query->where('status','!=', 'cancelled');
                                            })->pluck('id');

        return ClientTransaction::where('group_id', $id)
                    ->where(function ($q) use($clientActiveServices){
                        $q->whereIn('client_service_id', $clientActiveServices);
                        $q->orwhere('client_service_id',null);
                    })
                    ->where('type', 'Payment')
                    ->sum('amount');
        // return ClientTransaction::where('group_id', $id)->where('type', 'Payment')->sum('amount');
    }

    private function getGroupTotalDiscount($id) {
        return ClientTransaction::where('group_id', $id)->where('client_service_id', null)->where('type', 'Discount')->sum('amount'); //if client null
        //discount per service
    }

    private function getGroupTotalRefund($id) {
        return ClientTransaction::where('group_id', $id)->where('type', 'Refund')->sum('amount');
    }

    private function getGroupTotalCost($id) {
        $groupTotalCost = ClientService::where('active', 1)->where('group_id', $id)
                            ->where('status','!=','cancelled');
        $clids = $groupTotalCost->pluck('id');
        $groupTotalCost =   $groupTotalCost->value(DB::raw("SUM(cost + charge + tip + com_agent + com_client)"));

        $discount =  ClientTransaction::where('group_id', $id)->where('type', 'Discount')
                    ->where('client_service_id','!=',null)->whereIn('client_service_id', $clids)->sum('amount');

        $discount = ($discount) ? $discount : 0;

        return (($groupTotalCost) ? $groupTotalCost : 0) - $discount;
    }


    private function groupCompleteBalance($group_id){
        $balance = ((
                        $this->getGroupDeposit($group_id)
                        + $this->getGroupPayment($group_id)
                        + $this->getGroupDiscount($group_id)
                    )-(
                        $this->getGroupRefund($group_id)
                        + $this->getCompleteGroupCost($group_id)
                    ));
        return $balance;
    }


    private function getGroupTotalCompleteServiceCost($id) {

        $g = ClientService::where('active', 1)->where('group_id', $id)
                                            ->where(function ($query) {
                                                        $query->where('status', 'complete')
                                                             ->orwhere('status', 'released');
                                                    });
        $clids = $g->pluck('id');
        $gCost = $g->value(DB::raw("SUM(cost + charge + tip + com_agent + com_client)"));

        $discount =  ClientTransaction::where('group_id', $id)->where('type', 'Discount')
                    ->where('client_service_id','!=',null)->whereIn('client_service_id', $clids)->sum('amount');

        $discount = ($discount) ? $discount : 0;

        return (($gCost) ? $gCost : 0) - $discount;
    }

    public function getGroupTotalBalance($id) {
        return  (
                    (
                        $this->getGroupDeposit($id)
                        + $this->getGroupPayment($id)
                        +$this->getGroupTotalDiscount($id)
                    )
                    -
                    (
                        $this->getGroupTotalRefund($id)
                        + $this->getGroupTotalCost($id)
                    )
                );
    }

    public function getGroupTotalCollectables($id) {
        return  (
                    (
                        $this->getGroupDeposit($id)
                        + $this->getGroupPayment($id)
                        + $this->getGroupTotalDiscount($id)
                    )
                    -
                    (
                        $this->getGroupTotalRefund($id)
                        + $this->getGroupTotalCompleteServiceCost($id)
                    )
                );
    }

	public function manageGroups() {
		$groups = DB::table('groups as g')
			->select(DB::raw('g.id, g.name, CONCAT(u.first_name, " ", u.last_name) as leader, g.balance, g.collectables, p.latest_package as latest_package, srv.latest_service as latest_service'))
            ->leftjoin(DB::raw('(select * from users) as u'),'u.id','=','g.leader_id')
            ->leftjoin(DB::raw('
                    (
                        Select date_format(max(x.dates),"%M %e, %Y, %l:%i %p") as latest_package, x.group_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as dates,
                            group_id, status
                            FROM packages
                            ORDER BY dates desc
                        ) as x
                        group by x.group_id) as p'),
                    'p.group_id', '=', 'g.id')
            ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%M %e, %Y") as latest_service,cs.client_id,cs.group_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d") as servdates,
                            group_id, active,client_id
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.group_id) as srv'),
                    'srv.group_id', '=', 'g.id')
            ->orderBy('g.id', 'desc')
            ->get();

		$response['status'] = 'Success';
		$response['data'] = [
		    'groups' => $groups
		];
		$response['code'] = 200;

		return Response::json($response);
	}

    public function manageGroupsPaginate(Request $request, $perPage = 20) {
        $sort = $request->input('sort');
        $search = $request->input('search');

        $groups = DB::table('groups as g')
            ->select(DB::raw('g.id, g.name, CONCAT(u.first_name, " ", u.last_name) as leader, g.risk, g.balance, g.collectables, p.latest_package, srv.latest_service, p.latest_package2, srv.latest_service2'))
            ->leftjoin(DB::raw('(select * from users) as u'),'u.id','=','g.leader_id')
            ->leftjoin(DB::raw('
                    (
                        Select date_format(max(x.dates),"%M %e, %Y, %l:%i %p") as latest_package, date_format(max(x.dates),"%Y%m%d") as latest_package2, x.group_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as dates,
                            group_id, status
                            FROM packages
                            ORDER BY dates desc
                        ) as x
                        group by x.group_id) as p'),
                    'p.group_id', '=', 'g.id')
            ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%M %e, %Y") as latest_service, date_format(max(cs.servdates),"%Y%m%d") as latest_service2 ,cs.client_id,cs.group_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d") as servdates,
                            group_id, active,client_id, status
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1 and cs.status != "cancelled"
                        group by cs.group_id) as srv'),
                    'srv.group_id', '=', 'g.id')
            ->when($search != '', function ($q) use($search){
                return $q->where('g.id','LIKE', '%'.$search.'%')->orwhere('g.name','LIKE', '%'.$search.'%');
            })
            ->when($sort != '', function ($q) use($sort){
                $sort = explode('-' , $sort);
                if($sort[0] == 'latest_package' || $sort[0] == 'latest_service'){
                    $sort[0] = $sort[0].'2';
                }
                return $q->orderBy($sort[0], $sort[1]);
            })
            ->paginate($request->input('perPage'));

        $response = $groups;


        return Response::json($response);
    }

    public function groupSearch(Request $request){
        $keyword = $request->input('search');
        $users = '';
        if(preg_match('/\s/',$keyword)){
            $q = explode(" ", $keyword);
            $q1 = '';
            $q2 = '';
            $spaces = substr_count($keyword, ' ');
            if($spaces == 2){
                $q1 = $q[0]." ".$q[1];
                $q2 = $q[2];
            }
            if($spaces == 1){
                $q1 = $q[0];
                $q2 = $q[1];
            }
            $users = DB::connection()
            ->table('users as a')
            ->select(DB::raw('
                a.id,a.first_name,a.last_name,a.created_at'))
                ->orwhere(function ($query) use($q1,$q2) {
                        $query->where('first_name', '=', $q1)
                              ->Where('last_name', '=', $q2);
                    })->orwhere(function ($query) use($q1,$q2) {
                        $query->where('last_name', '=', $q1)
                              ->Where('first_name', '=', $q2);
                    })
                ->pluck('id');
        }
         else{
            $users = DB::connection()
            ->table('users as a')
            ->select(DB::raw('
                a.id,a.first_name,a.last_name,a.created_at'))
                ->orwhere('first_name','=',$keyword)
                ->orwhere('last_name','=',$keyword)
                ->pluck('id');
        }

        $getLeaderIds = Group::pluck('leader_id');
        $cids = ContactNumber::whereIn('user_id',$getLeaderIds)->where("number",'LIKE', '%' . $keyword .'%')->pluck('user_id');
            $grps = DB::connection()
            ->table('groups as a')
            ->select(DB::raw('
                a.id,a.name'))
                //->orwhere('a.id','LIKE', '%' . $keyword .'%')
                ->orwhere('name','LIKE', '%' . $keyword .'%')
                ->orwhereIn('leader_id',$cids)
                ->orwhereIn('leader_id',$users)
                ->get();


        $json = [];
        foreach($grps as $p){
            $br = 1;
            $branch = DB::connection()->table('branch_group as a')->where('group_id',$p->id)->first()->branch_id;
            if($branch){
                $br = $branch;
            }
          $br = Branch::where('id',$br)->first()->name;
          $json[] = array(
              'id' => $p->id,
              'name' => $p->name." [".$br."]",
          );
        }
        $response['status'] = 'Success';
        $response['data'] =  $json;
        $response['code'] = 200;

        return Response::json($response);
    }

    public function assignRole(Request $request) {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'client_id' => 'required',
            'role' => 'required'
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            if( $request->role == 'leader' ) {
                $group = Group::findOrFail($request->group_id);

                $oldLeaderId = $group->leader_id;
                $oldLeader = User::where('id',$oldLeaderId)->first();
                $oldLeaderLabel = '['.$oldLeaderId.'] '.$oldLeader->first_name.' '.$oldLeader->last_name;

                $group->update(['leader_id' => $request->client_id]);

                $newLeader = User::where('id',$request->client_id)->first();
                $newLeaderLabel = '['.$request->client_id.'] '.$newLeader->first_name.' '.$newLeader->last_name;

                DB::table('group_user')->where('group_id', $request->group_id)
                    ->whereIn('user_id', [$oldLeaderId, $request->client_id])
                    ->update(['is_vice_leader' => 0]);

                //save action logs
                $detail = 'Change new group main leader from <strong> ' .$oldLeaderLabel. '</strong> to <strong>' . $newLeaderLabel .'</strong>.';
                $detail_cn = '创建了新的服务包 '. $group->tracking.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => null,
                    'group_id' => $request->group_id,
                    'log_type' => 'Action',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                );
                LogController::save($log_data);

            } elseif( $request->role == 'vice-leader' ) {
                DB::table('group_user')->where('group_id', $request->group_id)->where('user_id', $request->client_id)
                    ->update(['is_vice_leader' => 1]);
            } elseif( $request->role == 'member' ) {
                DB::table('group_user')->where('group_id', $request->group_id)->where('user_id', $request->client_id)
                    ->update(['is_vice_leader' => 0]);
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }

	public function store(Request $request) {
		$validator = Validator::make($request->all(), [
            'group_name' => 'required|unique:groups,name',
            'branches' => 'required|array',
            'leader' => 'required'
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
        	$group = Group::create([
        		'name' => $request->group_name,
        		'leader_id' => $request->leader,
        		'tracking' => $this->generateGroupTracking(),
        		'address' => User::findOrFail($request->leader)->address
        	]);

            //save action logs
            $detail = 'Created new group ->'.$request->group_name.'.';
            $detail_cn = '建立新群组 '.$request->group_name.'.';
            $log_data = array(
                'client_service_id' => null,
                'client_id' => null,
                'group_id' => $group->id,
                'log_type' => 'Action',
                'detail'=> $detail,
                'detail_cn'=> $detail_cn,
            );
            LogController::save($log_data);

        	foreach($request->branches as $branch) {
        		$group->branches()->attach($branch);
        	}

        	$group->clients()->attach($request->leader);

        	$response['status'] = 'Success';
        	$response['code'] = 200;
        }

        return Response::json($response);
	}

    public function show($id) {
        $group = Group::with('branches', 'contactNumbers', 'serviceProfile')
            ->select(array('id', 'name', 'leader_id', 'tracking', 'address','client_com_id', 'agent_com_id', 'service_profile_id'))
            ->find($id);

        if( $group ) {
            $group->leader = DB::table('users')->where('id', $group->leader_id)
                ->select(array('first_name', 'last_name'))->first();

            // $group->contact = DB::table('contact_numbers')->where('group_id', $id)
            //         ->select(array('number'))->first(); //here

            $group->contact = DB::table('contact_numbers')->where('user_id', $group->leader_id)
                    ->select(array('number'))->first(); //here


            $group->total_complete_service_cost = $this->getGroupTotalCompleteServiceCost($id);
            $group->total_cost = $this->getGroupTotalCost($id);
            //$group->total_payment = $this->getGroupDeposit($id) + $this->getGroupPayment($id);
            $group->total_payment = $this->getGroupPayment($id);
            $group->total_discount = $this->getGroupTotalDiscount($id);
            $group->total_refund = $this->getGroupTotalRefund($id);
            $group->total_balance = $this->getGroupTotalBalance($id);
            $group->total_collectables = $this->getGroupTotalCollectables($id);
            $group->total_deposit = $this->getGroupDeposit($id);
            $group->total_ewallet = $this->getGroupEwallet($id);

            $response['status'] = 'Success';
            $response['data'] = [
                'group' => $group
            ];
            $response['code'] = 200;
        } else {
            $response['status'] = 'Failed';
            $response['errors'] = 'No query results.';
            $response['code'] = 404;
        }

        return Response::json($response);
    }


    public function updateGroupCommission(Request $request, $id){

      $validator = Validator::make($request->all(), [
          'com_type' => 'required'
      ]);

      if($validator->fails()) {
          $response['status'] = 'Failed';
          $response['errors'] = $validator->errors();
          $response['code'] = 422;
      } else {

        $group = Group::find($id);

        if($group) {
          if($request->com_type == 'agent'){
             $group->update(['agent_com_id' => $request->com_id]);
          }else{
             $group->update(['client_com_id' => $request->com_id]);
          }
          $response['status'] = 'Success';
          $response['code'] = 200;
        }else {
            $response['status'] = 'Failed';
            $response['errors'] = 'No query results.';
            $response['code'] = 404;
        }

      }

      return Response::json($response);
    }


    public function updateRisk(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'risk' => 'required'
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $group = Group::find($id);

            if( $group ) {
                $group->update(['risk' => $request->risk]);

                $response['status'] = 'Success';
                $response['code'] = 200;
            } else {
                $response['status'] = 'Failed';
                $response['errors'] = 'No query results.';
                $response['code'] = 404;
            }
        }

        return Response::json($response);
    }

	public function update(Request $request, $id) {
		$validator = Validator::make($request->all(), [
            'group_name' => 'required|unique:groups,name,'.$id,
            'contact_number' => 'required',
            'address' => 'required'
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
        	$group = Group::find($id);

        	if( $group ) {
                $oldName = $group->name;
                $oldAddress = $group->address;
                $leader_id = $group->leader_id;
                $oldNumber = '';
                $checkNum = ContactNumber::where('user_id', $leader_id)->where('is_primary',1)->first();
                if($checkNum){
                    $oldNumber = $checkNum->number;
                }

        		$group->name = $request->group_name;
        		$group->address = $request->address;
        		$group->save();

        		ContactNumber::updateOrCreate(
        			['user_id' => $leader_id],
        			[
        				'number' => $request->contact_number['number'],
        				'is_primary' => 1,
        				'is_mobile' => $request->contact_number['is_mobile']
        			]
        		);

                $detail = '';
                if($oldName != $request->group_name) {
                    $detail .= ' Change group name from ' . $oldName . ' to ' . $request->group_name .'.';
                }
                if($oldAddress != $request->address) {
                    $detail .= ' Change group address from ' . $oldAddress . ' to ' . $request->address .'.';
                }
                if($oldNumber != $request->contact_number['number']) {
                    $detail.= ' Change group contact number from ' . $oldNumber . ' to ' . $request->contact_number['number'] .'.';
                }

                if($detail!=''){
                    //save action logs
                    $detail_cn = $detail;
                    $log_data = array(
                        'client_service_id' => null,
                        'client_id' => null,
                        'group_id' => $id,
                        'log_type' => 'Action',
                        'detail'=> $detail,
                        'detail_cn'=> $detail_cn,
                    );
                    LogController::save($log_data);
                }

        		$response['status'] = 'Success';
        		$response['code'] = 200;
        	} else {
        		$response['status'] = 'Failed';
        		$response['errors'] = 'No query results.';
				    $response['code'] = 404;
        	}
        }

        return Response::json($response);
	}


  public function deleteMember(Request $request) {

    $leader = Group::where('id', $request->group_id)
        ->where('leader_id', $request->member_id)
        ->count();

    $success = false;

    if($leader != 0) {
        $success = false;
        $message = 'Cannot delete leader of the group';
    } else {
        $services = ClientService::where('group_id', $request->group_id)
            ->where('client_id', $request->member_id)
            ->where('active', 1)
            ->count();

        if($services != 0) {
            $success = false;
            $message = 'Cannot delete member/s that has registered service/s.';
        } else {
            GroupUser::where('group_id', $request->group_id)->where('user_id', $request->member_id)->delete();

            // Action log here


            $success = true;
            $message = 'Member successfully deleted';
        }
    }

    if($success){
      $response['status'] = 'Success';
      $response['code'] = 200;
      $response['message'] = $message;
    }else{
      $response['status'] = 'Failed';
      $response['code'] = 422;
      $response['message'] = $message;
    }

    return Response::json($response);
  }


  public function addMembers(Request $request) {

      $validator = Validator::make($request->all(), [
          'id' => 'required'
      ]);


      if($validator){
        foreach($request->clientIds as $clientId) {

          //addMembers
           $isMemberExist =  GroupUser::where('user_id', $clientId)
                      ->where('group_id', $request->id)->first();

           if(!$isMemberExist){

             GroupUser::create([
                 'group_id' => $request->id,
                 'user_id' => $clientId,
                 'is_vice_leader' => 0,
                 'total_service_cost' => 0
             ]);

            // save action logs
            $member = User::where('id',$clientId)->first();
            $memberLabel = '[' . $clientId . '] ' . $member->first_name.' '.$member->last_name;
            $detail = 'Added ' . $memberLabel . ' as new member of the group.';
            $detail_cn = 'Added ' . $memberLabel . ' as new member of the group.';
            $log_data = array(
                'client_id' => null,
                'group_id' => $request->id,
                'log_type' => 'Action',
                'detail'=> $detail,
                'detail_cn'=> $detail_cn,
                'amount'=> 0,
            );
            LogController::save($log_data);

             $response['status'] = 'Success';
             $response['code'] = 200;
             $response['data'] = $isMemberExist;


           }else{
             $response['status'] = 'Error';
             $response['code'] = 404;
             $response['msg'] = 'Client Not Available!';
           }
        }

      }else{
        $response['status'] = 'Error';
        $response['code'] = 404;
      }

      return Response::json($response);
  }


public function members(Request $request, $id, $page = 20) {

    $sort = $request->input('sort');
    $search = $request->input('search');


    $search_id = 0;
    $q1 = '';  $q2 = ''; $spaces = 0;
    if (preg_match("/^\d+$/", $search)) {
        $search_id = 1;
    }

    if(preg_match('/\s/',$search)){
        $q = explode(" ", $search);
        $spaces = substr_count($search, ' ');
        if($spaces == 2){
            $q1 = $q[0]." ".$q[1];
            $q2 = $q[2];
        }
        if($spaces == 1){
            $q1 = $q[0];
            $q2 = $q[1];
        }
    }

    $mode = '';
    if($search_id == 1 && $spaces == 0){
        $mode = 'id';
    }
    else if($search_id == 0 && $spaces == 0 && $search != ''){
        $mode = 'name';
    }
    else if($spaces >0){
        $mode = 'fullname';
    }

    $ids = $request->input('ids');


    if($ids != ''){
      $gids = $services = explode(',', $request->input('ids'));
    }else{
      $mems = DB::table('group_user as g_u')
                  ->where('g_u.group_id', $id)
                  ->get();

      $gids = $mems->pluck('user_id');
    }


    // \Log::info($gids);

    // $groups = DB::table('group_user as g_u')
    //     ->select(DB::raw('g_u.id,g_u.group_id, CONCAT(u.first_name, " ", u.last_name) as name, g_u.user_id,  g_u.is_vice_leader, g_u.total_service_cost, u.id'))
    //     ->leftjoin(DB::raw('(select * from users) as u'),'u.id','=','g_u.user_id')
    //     ->whereIn('g_u.user_id', $gids)
    //     ->when($mode == 'fullname', function ($query) use($q1,$q2){
    //             return $query->where(function ($query2) use($q1,$q2) {
    //                         $query2->where('u.first_name', '=', $q1)
    //                               ->Where('u.last_name', '=', $q2);
    //                     })->orwhere(function ($query2) use($q1,$q2) {
    //                         $query2->where('u.last_name', '=', $q1)
    //                               ->Where('u.first_name', '=', $q2);
    //                     });
    //     })
    //     ->when($mode == 'id', function ($query) use($search){
    //             return $query->where('u.id','LIKE','%'.$search.'%');
    //     })
    //     ->when($mode == 'name', function ($query) use($search){
    //             return $query->where('first_name' ,'=', $search)
    //                          ->orwhere('last_name' ,'=', $search);
    //     })
    //     ->when($sort != '', function ($q) use($sort){
    //         $sort = explode('-' , $sort);
    //         return $q->orderBy($sort[0], $sort[1]);
    //     })
    //     ->orderBy('g_u.id', 'desc')
    //     ->paginate($page);

    $groups = DB::table('users as u')->select(DB::raw('u.id, CONCAT(u.first_name, " ", u.last_name) as name, g_u.is_vice_leader, g_u.total_service_cost, g_u.id as guid'))
                    ->leftjoin(DB::raw('(select * from group_user) as g_u'),'g_u.user_id','=','u.id')
                    ->whereIn('u.id', $gids)
                    ->where('g_u.group_id', $id)
                    ->when($mode == 'fullname', function ($query) use($q1,$q2){
                        return $query->where(function ($query1) use($q1,$q2) {
                            return $query1->where(function ($query2) use($q1,$q2) {
                                        $query2->where('u.first_name', '=', $q1)
                                              ->Where('u.last_name', '=', $q2);
                                    })->orwhere(function ($query2) use($q1,$q2) {
                                        $query2->where('u.last_name', '=', $q1)
                                              ->Where('u.first_name', '=', $q2);
                                    });
                        });
                    })
                    ->when($mode == 'id', function ($query) use($search){
                            return $query->where('u.id','LIKE','%'.$search.'%');
                    })
                    ->when($mode == 'name', function ($query) use($search){
                        return $query->where(function ($query2) use($search) {
                            $query2->where('u.first_name' ,'=', $search)
                                         ->orwhere('u.last_name' ,'=', $search);
                        });
                    })
                    ->when($sort != '', function ($q) use($sort){
                        $sort = explode('-' , $sort);
                        return $q->orderBy($sort[0], $sort[1]);
                    })
                    ->paginate($page);

        // \Log::info($groups->pluck('id'));

        $response = $groups;

      $ctr=0;
      $temp = [];

      foreach($groups as $g){
         $packs = DB::table('packages as p')->select(DB::raw('p.*,g.name as group_name'))
                    ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                     ->where('client_id', $g->id)
                     ->where('group_id', $id)
                    ->orderBy('id', 'desc')
                    ->get();

        $totalServiceCost = 0;
        if(count($packs) > 0){

          foreach($packs as $p){

              $services = DB::table('client_services as cs')
                  ->select(DB::raw('cs.*'))
                  ->where('client_id',$p->client_id)
                  ->where('group_id',$id)
                  ->orderBy('id', 'desc')
                  ->get();

            //  $tempService = [];
              $ctr2 = 0;
                  foreach($services as $s){
                    $s->package_cost = $s->cost+ $s->charge + $s->tip + $s->com_agent + $s->com_client;
                    $s->detail =  $s->detail;
                    $s->discount =  ClientTransaction::where('client_service_id', $s->id)->where('type', 'Discount')->sum('amount');
                    if($s->active !== 0){
                        $totalServiceCost += ($s->package_cost - $s->discount);
                    }
                  //  $tempService[$ctr2] = $s;
                  //  $ctr2 ++;
                  }
                  $packs = $services;
          }
          $temp['packages'] = $packs;
        }else{
          $temp['packages'] = [];
        }


        $temp['id'] = $g->guid;
        $temp['name'] = $g->name;
        $temp['is_vice_leader'] = $g->is_vice_leader;
        $temp['user_id'] = $g->id;
        $temp['total_service_cost'] = $totalServiceCost;
        $response[$ctr] =  $temp;
        $ctr++;
      }

      return Response::json($response);
}


public function getFunds($group_id, $page = 20){

      $funds = DB::table('client_transactions as ct')->select(DB::raw('ct.*,cs.detail as service_name, cs.client_id, u.first_name, u.last_name'))
                  ->leftjoin(DB::raw('(select * from client_services) as cs'),'cs.id','=','ct.client_service_id')
                  ->leftjoin(DB::raw('(select * from users) as u'),'u.id','=','cs.client_id')
                  ->where([['ct.group_id', '=', $group_id]])
                  ->where('type','!=','Discount')
                  ->orderBy('id', 'desc')
                  ->paginate($page);

      $response['status'] = 'Success';
      $response['data'] = $funds;
      $response['code'] = 200;

      return Response::json($response);

}

public function addFunds(Request $request) {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $tracking = $request->get('tracking');
            $group_id = $request->get('group_id');
            $type = $request->get('type');
            $storage = $request->get('storage');
            $amount = $request->get('amount');
            $reason = $request->get('reason');
            $branch_id = $request->get('branch_id');
            $bank = $request->get('bank');
            $alipay_reference = $request->get('alipay_reference');
            $selected_client = $request->get('selected_client');
            $selected_group = $request->get('selected_group');
            $transfer_to = $request->get('transfer_to');

            $client_id = Group::where('id',$group_id)->first()->leader_id;
            $gname = Group::where('id',$group_id)->first()->name;

            if ($type == "Deposit") {
                $depo = new ClientTransaction;
                $depo->client_id = $client_id;
                $depo->group_id = $group_id;
                $depo->tracking = $tracking;
                $depo->type = 'Deposit';
                if($storage=='Bank'){
                    $depo->storage_type = $bank;
                }
                if($storage=='Alipay'){
                    $amount = $amount-($amount*0.0175);
                    $depo->storage_type = $bank;
                    $depo->alipay_reference = $alipay_reference;
                }
                $depo->amount = $amount;
                $depo->save();

                //save financing
                $finance = new Financing;
                $finance->user_sn = Auth::user()->id;
                $finance->type = "deposit";
                $finance->record_id = $depo->id;
                $finance->cat_type = "process";
                $finance->cat_storage = $storage;
                $finance->branch_id = $branch_id;
                $finance->storage_type = $bank;
                $finance->trans_desc = Auth::user()->first_name.' received deposit from group '.$gname;
                if($storage=='Alipay'){
                    $finance->trans_desc = Auth::user()->first_name.' received deposit from group '.$gname.' with Alipay reference: '.$alipay_reference;
                }
                ((strcasecmp($storage,'Cash')==0) ? $finance->cash_client_depo_payment = $amount : $finance->bank_client_depo_payment = $amount);
                $finance->save();

                // save transaction logs
                $detail = 'Deposited an amount of Php'.$amount.'.';
                $detail_cn = '预存了款项 Php'.$amount.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => null,
                    'group_id' => $group_id,
                    'log_type' => 'Transaction',
                    'log_group' => 'deposit',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> $amount,
                );
                 LogController::save($log_data);
            }

            else if($type == "Payment") {
                $payment = new ClientTransaction;
                $payment->client_id = $client_id;
                $payment->tracking = $tracking;
                $payment->type = 'Payment';
                $payment->group_id = $group_id;
                if($storage=='Bank'){
                    $payment->storage_type = $bank;
                }
                if($storage=='Alipay'){
                    $payment->storage_type = $bank;
                    $payment->alipay_reference = $alipay_reference;
                    $amount = $amount-($amount*0.0175);
                }
                $payment->amount = $amount;
                $payment->save();

                //for financing
                $finance = new Financing;
                $finance->user_sn = Auth::user()->id;
                $finance->type = "payment";
                $finance->record_id = $payment->id;
                $finance->cat_type = "process";
                $finance->cat_storage = $storage;
                $finance->branch_id = $branch_id;
                $finance->storage_type = $bank;
                $finance->trans_desc = Auth::user()->first_name.' received payment from group '.$gname;
                if($storage=='Alipay'){
                    $finance->trans_desc = Auth::user()->first_name.' received payment from group '.$gname.' with Alipay reference: '.$alipay_reference;
                }
                ((strcasecmp($storage,'Cash')==0) ? $finance->cash_client_depo_payment = $amount : $finance->bank_client_depo_payment = $amount);
                $finance->save();

                // save transaction logs
                $detail = 'Paid an amount of Php'.$amount.'.';
                $detail_cn = '已支付 Php'.$amount.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => null,
                    'group_id' => $group_id,
                    'log_type' => 'Transaction',
                    'log_group' => 'payment',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> $amount,
                );
                LogController::save($log_data);
            }

            else if($type == "Refund") {
                    $refund = new ClientTransaction;
                    $refund->client_id = $client_id;
                    $refund->tracking = $tracking;
                    $refund->type = 'Refund';
                    $refund->amount = $amount;
                    $refund->group_id = $group_id;
                    $refund->reason = $reason;
                    if($storage=='Bank'){
                        $refund->storage_type = $bank;
                    }
                    $refund->save();

                    //for financing
                    $finance = new Financing;
                    $finance->user_sn = Auth::user()->id;
                    $finance->type = "refund";
                    $finance->record_id = $refund->id;
                    $finance->cat_type = "process";
                    $finance->cat_storage = $storage;
                    $finance->cash_client_refund = $amount;
                    $finance->branch_id = $branch_id;
                    $finance->trans_desc = Auth::user()->first_name.' refund to group '.$gname.' with the reason of '.$reason;
                    $finance->storage_type = ($storage!='Cash') ? $bank : null;
                    $finance->save();

                    // save transaction logs
                    $detail = 'Refunded an amount of Php'.$amount.' with the reason of <i>"'.$reason.'"</i>.';
                    $detail_cn = '退款了 Php'.$amount.' 因为 "'.$reason.'".';
                    $log_data = array(
                        'client_service_id' => null,
                        'client_id' => null,
                        'group_id' => $group_id,
                        'log_type' => 'Transaction',
                        'log_group' => 'refund',
                        'detail'=> $detail,
                        'detail_cn'=> $detail_cn,
                        'amount'=> '-'.$amount,
                    );
                    LogController::save($log_data);
            }

            else if($type == "Discount"){
                $discount = new ClientTransaction;
                $discount->client_id = $client_id;
                $discount->tracking = $tracking;
                $discount->type = 'Discount';
                $discount->amount = $amount;
                $discount->group_id = $group_id;
                $discount->reason = $reason;
                if($storage=='bank'){
                    $discount->storage_type = $bank_type;
                }
                $discount->save();

                // save transaction logs
                $detail = 'Discounted an amount of Php'.$amount.' with the reason of <i>"'.$reason.'"</i>.';
                $detail_cn = '给于折扣 Php'.$amount.' 因为"'.$reason.'".';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => null,
                    'group_id' => $group_id,
                    'log_type' => 'Transaction',
                    'log_group' => 'discount',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> $amount,
                );
                LogController::save($log_data);
            }

            else if($type == "Balance Transfer"){
                // Refund amount to client
                $refund = new ClientTransaction;
                $refund->client_id = $client_id;
                $refund->tracking = $tracking;
                $refund->type = 'Refund';
                $refund->amount = $amount;
                $refund->group_id = $group_id;
                $refund->reason = $reason;
                $refund->save();

                if($request->transfer_to == 'Group'){
                    $transferred = Group::where('id',$selected_group)->first()->name;
                    $leaderId = Group::where('id',$selected_group)->first()->leader_id;
                }
                if($request->transfer_to == 'Client'){
                    $cl_usr = User::where('id',$selected_client)->select('id','first_name','last_name')->first();
                    $transferred = $cl_usr->first_name.' '.$cl_usr->last_name;
                }

                // save transaction logs
                $detail = 'Refunded an amount of Php'.$amount.', transferred to '.$request->transfer_to.' '.$transferred;
                $detail_cn = '退款 Php'.$amount.', 转移到了客户 '.$transferred;
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => null,
                    'group_id' => $group_id,
                    'log_type' => 'Transaction',
                    'log_group' => 'refund',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> '-'.$amount,
                );
                LogController::save($log_data);

                $transTo = $selected_client;
                $grid = null;
                if($request->transfer_to == 'Group'){
                    $transTo = Group::where('id',$selected_group)->first()->leader_id;
                    $grid = $selected_group;
                }

                // Deposit amount to client or group selected
                $depo = new ClientTransaction;
                $depo->client_id = $transTo;
                $depo->type = 'Deposit';
                $depo->amount = $amount;
                $depo->group_id = $grid;
                $depo->tracking = null;
                $depo->save();

                //for financing
                $finance = new Financing;
                $finance->user_sn = Auth::user()->id;
                $finance->type = "transfer";
                $finance->record_id = $depo->id;
                $finance->cat_type = "process";
                $finance->cat_storage = $storage;
                $finance->branch_id = $branch_id;
                ((strcasecmp($storage,'Cash')==0) ? $finance->cash_client_depo_payment = $amount : $finance->bank_client_depo_payment = $amount);
                ((strcasecmp($storage,'Cash')==0) ? $finance->cash_client_refund = $amount : $finance->bank_cost = $amount);
                $finance->trans_desc = Auth::user()->first_name.' transffered funds from group '.$gname.' to '.$request->transfer_to.' '.$transferred.'.';
                $finance->save();

                 // save transaction logs
                $detail = 'Deposited an amount of Php'.$amount.' from group '.$gname.'.';
                $detail_cn = '预存了款项 Php'.$amount.' 从 团体 '.$gname.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => null,
                    'group_id' => $grid,
                    'log_type' => 'Transaction',
                    'log_group' => 'deposit',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> $amount,
                );
                 LogController::save($log_data);
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }

public function getClientPackagesByGroup($client_id, $group_id){

      $packs = DB::table('packages as p')->select(DB::raw('p.*,g.name as group_name'))
                  ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                   ->where([['client_id', '=' , $client_id], ['p.group_id', '=', $group_id]])
                  ->orderBy('id', 'desc')
                  ->get();

      foreach($packs as $p){
          $package_cost = ClientService::where('tracking', $p->tracking)
                          ->where('active', 1)
                          ->value(DB::raw("SUM(cost + charge + tip + com_agent + com_client)"));
          $p->package_cost = ($package_cost > 0 ? $package_cost : 0);

      }

      $response['status'] = 'Success';
      $response['data'] = $packs;
      $response['code'] = 200;

      return Response::json($response);

}



  public function getClientPackagesByService(Request $request, $groupId, $page = 20){

    $sort = $request->input('sort');
    $search = $request->input('search');

    $clientServices = DB::table('client_services')
      ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, detail, created_at'))
      ->where('group_id',$groupId)
      ->groupBy('service_id')
      ->orderBy('created_at','DESC')

      ->when($sort != '', function ($q) use($sort){
          $sort = explode('-' , $sort);
          return $q->orderBy($sort[0], $sort[1]);
      })
      ->when($search != '', function ($q) use($search){
          return $q->where('detail','LIKE','%'.$search.'%');
      })
      ->paginate($page);

    $ctr = 0;
    $temp = [];
    $response = $clientServices;

    foreach($clientServices->items() as $s){

        $query = ClientService::where('created_at', $s->created_at)->where('service_id',$s->service_id)->where('group_id', $groupId)->where('active', 1);
        $queryx = ClientService::where('service_id',$s->service_id)->where('group_id', $groupId)->where('active', 1);

        $servicesByDate = DB::table('client_services')
          ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, detail, created_at, client_id'))
          ->where('group_id',$groupId)
          ->where('service_id',$s->service_id)
          ->groupBy(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'))
          ->orderBy('created_at','DESC')
          ->get();

        $temp['detail'] = $s->detail;
        $temp['service_date'] = $s->sdate;
        $temp['sdate'] = $s->sdate;
        $temp['group_id'] = $groupId;

        $discountCtr = 0;
        $totalServiceCount = 0;
        foreach($servicesByDate as $sd){

          $queryClients = ClientService::where('service_id', $sd->service_id)->where('created_at', $sd->created_at)->where('group_id', $groupId)->orderBy('created_at','DESC')->orderBy('client_id')->groupBy('client_id')->get();

          $memberByDate = [];
          $ctr2 = 0;

          foreach($queryClients as $m){

            $clientServices = [];
            $tmpCtr = 0;

            $m->discount = ClientTransaction::where('client_service_id', $m->id)->where('type', 'Discount')->sum('amount');
            $discountCtr += $m->discount;

            $memberByDate[$ctr2] = User::where('id',$m->client_id)->select('first_name','last_name')->first();
            $memberByDate[$ctr2]['tcost'] = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$sd->sdate)->where('group_id', $groupId)->where('client_id',$m->client_id)->value(DB::raw("SUM(cost + charge + tip +com_client + com_agent)"));
            $memberByDate[$ctr2]['service'] = $m;
            $memberByDate[$ctr2]['created_at'] = $m->created_at;
            $memberByDate[$ctr2]['client_id'] = $m->client_id;

            $ctr2++;

            if($m->active && $m->status != "cancelled")
              $totalServiceCount++;
         }

         $sd->members = $memberByDate;
      }


        $temp['total_service_cost'] = ($queryx->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)"))) - $discountCtr;
        $temp['total_service'] = ($queryx->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)")));
        $temp['service_count'] = $totalServiceCount;


        $temp['bydates'] = $servicesByDate;
        $response[$ctr] = $temp;
        $ctr++;
    }

    return Response::json($response);

  }




   public function getUnpaidServices(Request $request, $groupId, $isAutoGenerated, $page = 5){

     $sort = $request->input('sort');
     $search = $request->input('search');

     $clientServices = DB::table('client_services')
       ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, detail, created_at'))
       ->where('group_id',$groupId)
       ->where('active', 1)
       ->where('is_full_payment', 0)
       ->groupBy('service_id')
       ->orderBy('detail','DESC')

       ->when($sort != '', function ($q) use($sort){
           $sort = explode('-' , $sort);
           return $q->orderBy($sort[0], $sort[1]);
        })
        ->when($search != '', function ($q) use($search){
           return $q->where('detail','LIKE','%'.$search.'%');
        })
        //->get();
        ->paginate($page);

      $ctr = 0;
      $temp = [];


       $response = $clientServices;

       $totalAvailableBalance =  ClientService::where('group_id', $groupId)->sum('payment_amount');


       foreach($clientServices->items() as $s){

           $query = ClientService::where('service_id', $s->service_id)->where('group_id', $groupId)->where('is_full_payment', 0);

           $temp['detail'] = $s->detail;
           $temp['service_date'] = $s->sdate;
           $temp['sdate'] = $s->sdate;
           $temp['group_id'] = $groupId;
           $temp['total_balance'] = $this->getGroupDeposit($groupId);
           $temp['total_available_balance'] = $totalAvailableBalance;

           $queryClients = ClientService::where('service_id', $s->service_id)->where('group_id', $groupId)->where('is_full_payment', 0)->orderBy('created_at','DESC')->orderBy('client_id')->groupBy('client_id')->get();

           $ctr2 = 0;
           $members = [];
           $discountCtr = 0;

          if($queryClients){

           foreach($queryClients as $m){

               $ss =  ClientService::where('service_id', $s->service_id)->where('group_id', $groupId)->where('is_full_payment', 0)->where('client_id',$m->client_id)->get();

               $clientServices = [];
               $tmpCtr = 0;

               if($ss){
                   foreach($ss as $cs){
                     $cs->discount =  ClientTransaction::where('client_service_id', $cs->id)->where('type', 'Discount')->sum('amount');
                     $discountCtr += $cs->discount;
                     $cs->payment = (((($cs->cost + $cs->charge + $cs->tip + $cs->com_client + $cs->com_agent) - $cs->discount)) - $cs->payment_amount);
                     $clientServices[$tmpCtr] = $cs;
                     $tmpCtr++;
                   }
                   $members[$ctr2] = User::where('id',$m->client_id)->select('first_name','last_name')->first();
                   //$members[$ctr2]['tcost'] = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->where('is_full_payment', 0)->where('client_id',$m->client_id)->value(DB::raw("SUM(cost + charge + tip +com_client + com_agent)"));
                   $members[$ctr2]['services'] = $clientServices;
                   $ctr2++;
               }

           }

           $temp['total_service_cost'] = ($query->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)"))) - $discountCtr;

           $temp['members'] = $members;
           $response[$ctr] = $temp;
           $ctr++;
         }
       }

      // $response['total_available_balance'] =  $totalAvailableBalance;

      return Response::json($response);
   }


   public function addServicePayment(Request $request){

       if($request->group_id !== null){

        for($i=0; $i<count($request->services); $i++) {

           $getServ = ClientService::where('id', $request->services[$i]['id'])->first();

           $getServ->is_full_payment = $request->services[$i]['is_full_payment'];
           $getServ->payment_amount = $request->services[$i]['payment_amount'];
           $getServ->save();
        }

         $response['status'] = 'Success';
         $response['code'] = 200;
         $response['data']  = "UPDATED";

       }else{
         $response['error'] = 'Error';
         $response['code'] = 401;
         $response['message']  = "Invalid Group Id";
       }

       return Response::json($response);
   }



   public function getMembersPackages(Request $request, $groupId, $perPage = 10) {

     $sort = $request->input('sort');
     $search = $request->input('search');

     $arr = [];
          //$group_members = GroupUser::where('group_id', $groupId)->get();
          $group_members = GroupUser::where('group_id', $groupId)
          ->when($sort != '', function ($q) use($sort){
              $sort = explode('-' , $sort);
              return $q->orderBy($sort[0], $sort[1]);
          })
          ->when($search != '', function ($q) use($search){
              return $q->where('user_id','LIKE','%'.$search.'%');
          })
          ->paginate($perPage);

          $response = $group_members;

          $ctr=0;
          //foreach($group_members->items() as $gm){
          foreach($group_members as $gm){
              $usr =  User::where('id',$gm->user_id)->select('id','first_name','last_name')->limit(1)->get();
              if($usr){
                  $arr[$ctr] = $gm;
                  $arr[$ctr]['client'] =$usr[0];
                //  $arr[$ctr]['client']['packages'] = Package::where('client_id',$gm->user_id)->where('group_id',$gm->group_id)->get();
                  $arr[$ctr]['client']['packages'] = Package::where('client_id',$gm->user_id)->where('group_id',$gm->group_id)->get();

                $ctr++;
              }
          }
          $group_members->data = $arr;

          return Response::json($response);

  }

  //$groupId, $page = 20
  public function getClientPackagesByBatch(Request $request, $groupId, $perPage = 10){


    $sort = $request->input('sort');
    $search = $request->input('search');


        $clientServices = DB::table('client_services')
          ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, id, detail, created_at'))
          ->where('active',1)->where('group_id',$groupId)

          ->when($sort != '', function ($q) use($sort){
              $sort = explode('-' , $sort);
              return $q->orderBy($sort[0], $sort[1]);
          })
          ->when($search != '', function ($q) use($search){
              return $q->where('created_at','LIKE','%'.$search.'%');
          })

          ->groupBy(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'))
          ->orderBy('id','DESC')
          ->paginate($perPage);

        $ctr = 0;
        $temp = [];
        $response = $clientServices;

        foreach($clientServices->items() as $s){

          $query = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId);


          $temp['detail'] = $s->detail;
          $temp['service_date'] = $s->sdate;
          $temp['sdate'] = $s->sdate;
          $temp['group_id'] = $groupId;

          $queryMembers = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->orderBy('created_at','DESC')->orderBy('client_id')->groupBy('client_id')->get();

          $ctr2 = 0;
          $members = [];
          $discountCtr = 0;
          $totalCost = 0;

          foreach($queryMembers as $m){
                $ss =  ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->where('client_id',$m->client_id)->get();

                $clientServices = [];
                $tmpCtr = 0;

                foreach($ss as $cs){
                  $cs->discount =  ClientTransaction::where('client_service_id', $cs->id)->where('type', 'Discount')->sum('amount');
                  if($cs->active !== 0 && $cs->status != 'cancelled'){
                    $discountCtr += $cs->discount;
                    $totalCost += (($cs->cost + $cs->charge + $cs->tip + $cs->com_client + $cs->com_agent)) - $cs->discount;
                  }

                  $clientServices[$tmpCtr] = $cs;
                  $tmpCtr++;
                }

                $members[$ctr2] = User::where('id',$m->client_id)->select('first_name','last_name')->first();
                $members[$ctr2]['tcost'] = $query->where('client_id',$m->client_id)->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)"));
                $members[$ctr2]['services'] = $clientServices;
                $members[$ctr2]['client_id'] = $m->client_id;


              $ctr2++;
          }
          $temp['total_service_cost'] = $totalCost;
          //$temp['total_service_cost'] = ($query->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)")));
          $temp['members'] = $members;
          //$temp['query'] = $query;
          $response[$ctr] = $temp;
          $ctr++;
        }

        return Response::json($response);
  }





   public function addServices(Request $request) {

      $g = Group::findOrFail($request->group_id);
      $level = $g->service_profile_id;

      $trackingArray = [];
      $oldNewArray = [];
      $oldNewArrayChinese = [];
      $clientServicesIdArray = [];

      for($i=0; $i<count($request->clients); $i++) {

          $clientId = $request->clients[$i];

          if($request->packages[$i] === 0) { // Generate new package
              $tracking = $this->generateServiceTracking();

              Package::create([
                  'client_id' => $clientId,
                  'group_id' => $request->group_id,
                  'tracking' => $tracking,
                  'status' => 'pending'
              ]);

              $oldnew = "new";
              $oldnew_cn = "新的";
          } else {
              $tracking = $request->packages[$i];
              $oldnew = "old";
              $oldnew_cn = "旧的";

              //Update package status
              $package = Package::find($tracking);
              if( $package ) {
                  $package->update(['status' => 'pending']);
              }
          }

          $trackingArray[] = $tracking;
          $oldNewArray[] = $oldnew;
          $oldNewArrayChinese[] = $oldnew_cn;
      }

      $ctr = 0;
      $msg = [];
      $collect_services = '';
      $collect_services_cn = '';
      $service_status = 'on process';

      for($j=0; $j<count($request->services); $j++) {

          $translated = Service::where('id',$request->services[$j])->first();

          $cnserv =$translated->detail;

          if($translated){
              $cnserv = ($translated->detail_cn!='' ? $translated->detail_cn : $translated->detail);
          }

          $collect_services .= $translated->detail.', ';
          $collect_services_cn .= $cnserv.', ';
          $cls = '';

          for($i=0; $i<count($request->clients); $i++) {

              $clientId = $request->clients[$i];

              $service_status = 'pending';


              $serviceId =  $request->services[$j];
              $service = Service::findOrFail($serviceId);

            //  $dt = Carbon::now();
            //  $dt = $dt->toDateString();

              //$author = $request->note.' - '. Auth::user()->first_name.' <small>('.$dt.')</small>'; //to follow
              $author = $request->note;
              if($request->note==''){
                  $author = '';
              }

              //service cost depends on cost level of group
              $scharge = $request->charge;
              $scost = $request->cost;
              $stip = $request->tip;
              $client_com_id = $request->client_com_id;
              $agent_com_id = $request->agent_com_id;


              if($request->branch_id > 1){
                  $bcost = ServiceBranchCost::where('branch_id',$request->branch_id)->where('service_id',$serviceId)->first();
                  $scost = $bcost->cost;
                  $stip = $bcost->tip;
                  $scharge = $bcost->charge;
                  $com_client = $bcost->com_client;
                  $com_agent = $bcost->com_agent;
              }
              else{
                  $scost = $service->cost;
                  $stip = $service->tip;
                  $scharge = $service->charge;
                  $com_client = $service->com_client;
                  $com_agent = $service->com_agent;
              }

              //has profile id
              if($level > 0 && $level != null){
                  $newcost = ServiceProfileCost::where('profile_id',$level)
                                ->where('branch_id',$request->branch_id)
                                ->where('service_id',$serviceId)
                                ->first();
                  if($newcost){
                      $scharge = $newcost->charge;
                      $scost = $newcost->cost;
                      $stip = $newcost->tip;
                      $com_client = $newcost->com_client;
                      $com_agent = $newcost->com_agent;
                  }
              }

              $scharge = ($scharge > 0 ? $scharge : $service->charge);
              $scost = ($scost > 0 ? $scost : $service->cost);
              $stip = ($stip > 0 ? $stip : $service->tip);


              $clientService = ClientService::create([
                  'client_id' => $clientId,
                  'service_id' => $serviceId,
                  'detail' => $service->detail,
                  'cost' => $scost,
                  'charge' => $scharge,
                  'tip' => $stip,
                  'status' => $service_status,
                  'com_client' => $com_client,
                  'com_agent' => $com_agent,
                  'agent_com_id' => $agent_com_id,
                  'client_com_id' => $client_com_id,
                  'remarks' => $author,
                  'group_id' => $request->group_id,
                  'tracking' => $trackingArray[$i],
                  'active' => 1,
                  'extend' => null
              ]);

                // save transaction logs for group
                $detail = 'Added a service. Service status is pending.';
                $detail_cn = '已添加服务. 服务状态为 待办。';
               $log_data = array(
                    'client_service_id' => $clientService->id,
                    'client_id' => null,
                    'group_id' => $request->group_id,
                    'log_type' => 'Transaction',
                    'log_group' => 'service',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> 0,
                );
                 LogController::save($log_data);

              $clientServicesIdArray[] = $clientService;
              $ctr++;
          }

      }

      return Response::json(['success' => true,
        'message' => 'Service/s successfully added to group',
        'clientsId' => $request->clients,
        'clientServicesId' => $clientServicesIdArray,
        'trackings' => $trackingArray]);

   }

   //
   public function getClientServices(Request $request) {

        $group_id = $request->group_id;
        $client_id = $request->client_id;

        $result['services'] = DB::table('client_services')
                 ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, tracking, status, detail, created_at'))
                ->where('group_id', ($request->option !== 'client-to-group') ? $group_id : NULL)
                ->where('client_id', $client_id)
                ->orderBy('id', 'desc')
                ->get();


        if($request->option === 'client-to-group'){
          $result['packages'] = Package::where('group_id', $group_id)->where('client_id', $client_id)->orderBy('id', 'desc')->get();
        }
        else{
          $result['packages'] = Package::where('client_id', $client_id)->where('group_id', NULL)->orderBy('id', 'desc')->get();
        }


        $response['status'] = 'Success';
        $response['data'] = $result;
        $response['code'] = 200;

        return Response::json($response);

   }

   public function transferMember(Request $request){

          $response = [];

          if($request->group_id == $request->current_group_id){
              $response['status'] = 'Error';
              $response['code'] = 401;
              $response['msg'] = 'Saving failed. You selected same group!';
          }else{

              $currentGroup = DB::table('users as u')->select(DB::raw('u.id, CONCAT(u.first_name, " ", u.last_name) as name, g.name as group_name, g_u.total_service_cost, g_u.id as guid'))
                            ->leftjoin(DB::raw('(select * from group_user) as g_u'),'g_u.user_id','=','u.id')
                            ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=', 'g_u.group_id')
                            ->where('u.id', $request->member_id)
                            ->where('g_u.group_id', $request->current_group_id)
                            ->first();


              if($currentGroup){

                $data = array('group_id' => $request->group_id);

                //check if current member
                 $group = GroupUser::where('group_id', $request->group_id)
                          ->where('user_id', $request->member_id)
                          ->first();

                 if(!$group){
                    DB::table('group_user')
                          ->where('id', $currentGroup->guid)
                          ->update($data);
                 }else{
                    $previousGroup = GroupUser::where('group_id', $request->current_group_id)
                             ->where('user_id', $request->member_id)
                             ->first();
                    $previousGroup->forceDelete();
                 }

                 $details = 'Transfer member ' . $currentGroup->name . ' from Group <strong>'. $currentGroup->group_name .'</strong> to Group<strong>' . $request->group_name .'</strong> with Total Service Cost of ' . $currentGroup->total_service_cost;
                 $details_cn = '转会会员 ' . $currentGroup->name .' 来自组 '. $currentGroup->group_name.' 分组 ' . $request->group_name .' 以及总服务费 Php' . $currentGroup->total_service_cost;

                  // save transaction logs
                  $log_data = array(
                      'client_id' => $request->member_id,
                      'group_id' => $request->group_id,
                      'log_type' => 'Transaction',
                      'log_group' => 'service',
                      'detail'=> $details,
                      'detail_cn'=> $details_cn,
                      'amount'=> 0.00,
                  );
                  LogController::save($log_data);

                $response = $this->transferService($request);

              }else{
                $response['status'] = 'Error';
                $response['code'] = 401;
                $response['msg'] = 'No group data';
              }

          }

         return Response::json($response);
   }

   public function transferService($request){


     if($request->option == 'client-to-group') {
         $groupId = 0;
         $newGroupId = $request->group_id;
     } elseif($request->option == 'group-to-client') {
         $groupId = $request->group_id;
         $newGroupId = null;
     }

     $gentracking = null;
     for($i=0; $i<count($request->services); $i++) {

         if($request->packages[$i] == 0)   { //New package
             if($gentracking == null){
                 $type = ($request->option == 'client-to-group') ? 'group' : 'individual';
                 $tracking = $this->generateTracking($type);
                 $gentracking = $tracking;

                 Package::create([
                     'client_id' => $request->member_id,
                     'group_id' => $newGroupId,
                     'log_date' => Carbon::now()->format('F j, Y, g:i a'),
                     'tracking' => $tracking,
                     'status' => '0'
                 ]);

             }
             else{
                 $tracking = $gentracking;
             }
         } else {
             $tracking = $request->packages[$i];

         }

         $oldtrack = null;
         $getServ = ClientService::where('id', $request->services[$i])
           //  ->where('client_id', $request->member_id)
           //  ->where('group_id', $groupId)
             ->first();

         if($getServ){
             $oldtrack = $getServ->tracking;
             $getServ->group_id = $newGroupId;
             $getServ->tracking = $tracking;
             $getServ->save();

             $disc = ClientTransaction::where('client_service_id',$getServ->id)->where('type','Discount')->first();
             $discount = 0;
             $group_leader = null;
             if($disc){
                 $discount = $disc->amount;
                 $disc->group_id = $newGroupId;
                 $disc->client_id = $request->member_id;
                 $disc->tracking = $tracking;
                 if($newGroupId != null){
                     $group_leader = Group::where('id', $newGroupId)->first()->leader_id;
                     $disc->client_id = $group_leader;
                 }
                 $disc->save();
             }

             $response['status'] = 'Success';
             $response['code'] = 200;

             //Logs here
             $translated = Service::where('id',$getServ->service_id)->first();
             $cnserv =$getServ->detail;
             if($translated){
                 $cnserv = ($translated->detail_cn!='' ? $translated->detail_cn : $translated->detail);
             }

             $cost = ($getServ->cost + $getServ->charge + $getServ->tip + $getServ->com_client + $getServ->com_agent) - $discount;

             if($request->option == 'client-to-group') {
                 $details = 'Transfer service ' . $getServ->detail . ' to Group Package #' . $tracking .' with <strong>Total Service Cost of ' . $cost;
                 $details_cn = '转移了服务 ' . $cnserv . '到服务包(团体)#' . $tracking .'以及总服务费 Php' . $cost;

                 $_groupId = $newGroupId;

             }
             elseif($request->option == 'group-to-client') {
                 $details = 'Transfer service ' . $getServ->detail . ' to Package #<strong>' . $tracking .' with Total Service Cost of ' . $cost;
                 $details_cn = '转移了服务 ' . $cnserv . '到服务包#' . $tracking .'以及总服务费 Php' . $cost;

                 $_groupId = $groupId;
             }

             // save transaction logs
             $log_data = array(
                 'client_service_id' => $getServ->id,
                 'client_id' => $request->member_id,
                 'group_id' => null,
                 'log_type' => 'Transaction',
                 'log_group' => 'service',
                 'detail'=> $details,
                 'detail_cn'=> $details_cn,
                 'amount'=> $cost,
             );
             LogController::save($log_data);

             // $log_data['group_id'] = $_groupId;
             // $log_data['client_id'] = null;
             // LogController::save($log_data);

         }

         $this->updatePackageStatus($tracking);
         $this->updatePackageStatus($oldtrack);


     }

     $response['status'] = 'Success';
     $response['code'] = 200;
     $response['data']  = $getServ;

     return $response;
   }

   public function transfer(Request $request) {
        $response = $this->transferService($request);
        return Response::json($response);
    }


    private function updatePackageStatus($tracking){
        $status = null; // empty

        $countCancelledServices = DB::table('client_services')
            ->select('*')
            ->where('tracking', $tracking)
            ->where('active', 0)
            ->where('status', 'cancelled')
            ->count();

        $countReleasedServices = DB::table('client_services')
            ->select('*')
            ->where('tracking', $tracking)
            ->where('active', 1)
            ->where('status', 'released')
            ->count();

        $countCompleteServices = DB::table('client_services')
            ->select('*')
            ->where('tracking', $tracking)
            ->where('active', 1)
            ->where('status', 'complete')
            ->count();

        $countOnProcessServices = DB::table('client_services')
            ->select('*')
            ->where('tracking', $tracking)
            ->where('active', 1)
            ->where('status', 'on process')
            ->count();

        $countPendingServices = DB::table('client_services')
            ->select('*')
            ->where('tracking', $tracking)
            ->where('active', 1)
            ->where('status', 'pending')
            ->count();

        if($countCancelledServices > 0){
            $status = "cancelled";
        }
        if($countReleasedServices > 0){
            $status = "released";
        }
        if($countCompleteServices > 0){
            $status = "complete";
        }
        if($countOnProcessServices > 0){
            $status = "on process";
        }
        if($countPendingServices > 0){
            $status = "pending";
        }

        $data = array('status' => $status);

        DB::table('packages')
            ->where('tracking', $tracking)
            ->update($data);
    }

    private function generateTracking($option) {
        Repack:
            $tracking = ($option == 'group')
                ? $this->generateGroupTracking()
                : $this->generateRandomString(7);
            $check_package = Package::where('tracking', $tracking)->count();
        if($check_package > 0) :
            goto Repack;
        endif;

        return $tracking;
    }




   public function editServices(Request $request) {


     $collection = ClientService::whereIn('id', $request->services);
     $oldCollect = $collection;
     $clientServices = $collection->get();

     $getGroup = Group::findOrFail($request->group_id);


     foreach($clientServices as $clientService) {

          $dt = Carbon::now();
          $dt = $dt->toDateString();

          $author = $request->note.' - '. Auth::user()->first_name.' <small>('.$dt.')</small>';
          if($request->note==''){
              $author = '';
          }

          $note = $clientService->remarks;
          if($note!=''){
              if($request->note!=''){
                  $note = $note.'</br>'.$author;
              }
          }
          else{
              $note = $author;
          }


          $srv = ClientService::findOrFail($clientService->id);
          $oldstatus = $srv->status;
          $oldactive = $srv->active;
          //For translation
          $translated = Service::where('id',$srv->service_id)->first();
          $cnserv =$srv->detail;
          if($translated){
              $cnserv = ($translated->detail_cn!='' ? $translated->detail_cn : $translated->detail);
          }
          $cdetail = $cnserv;

          $oldDiscount = 0;
          $newDiscount = 0;
          $discnotes = '';
          $discnotes_cn = '';
          $translog = '';
          $translog_cn = '';
          $transtat = '';
          $transtat_cn = '';
          $newVal = 0;
          $oldVal = 0;

            // check changes active/inactive
            if ($srv->active != $request->active) {
                if($request->active == 1) { // Enabled
                    $transtat = 'Service was enabled.';
                    $transtat_cn = '服务被标记为已启用.';
                    $translog = 'Total service charge from Php0 to ' . 'Php'. ($srv->cost + $srv->charge + $srv->tip + $srv->com_client + $srv->com_agent);
                    $translog_cn = '总服务费从 Php0 到 ' . 'Php'. ($srv->cost + $srv->charge + $srv->tip + $srv->com_client + $srv->com_agent);
                } elseif($request->active == 0) { // Disabled
                    $transtat = 'Service was disabled.';
                    $transtat_cn = '服务被标记为失效.';
                    $translog = 'Total service charge from Php'. ($srv->cost + $srv->charge + $srv->tip + $srv->com_client + $srv->com_agent).' to Php'.'0';
                    $translog_cn = '总服务费从 Php'. ($srv->cost + $srv->charge + $srv->tip + $srv->com_client + $srv->com_agent).' 到 Php'.'0';
                }

                $newVal +=0;
                $oldVal +=($srv->cost + $srv->charge + $srv->tip + $srv->com_client + $srv->com_agent);
            }

            // discount
          if($request->discount > 0) {

          $__oldDiscount = null;

          //Saving Client Transaction
          $dc = ClientTransaction::where("client_service_id",$srv->id)->where('type','Discount')->withTrashed()->first();

              if($dc){
                  $__oldDiscount = $dc->amount;
                  $oldDiscount = $dc->amount;

                  if($dc->amount != $request->discount){

                      $newDiscount = $request->discount;
                      $dc->amount =  $request->get('discount');
                      $dc->reason =  $request->get('reason');
                      $dc->deleted_at = null;
                      $dc->save();
                  }else{
                      if($dc->reason != $request->reason){
                          $dc->reason =  $request->get('reason');
                          $dc->save();
                      }
                  }
              }

              else{

                  $newDiscount = $request->discount;
                  ClientTransaction::create([
                      'client_id' => $getGroup->leader_id,
                      'type' => 'Discount',
                      'amount' => $request->get('discount'),
                      'group_id' => $getGroup->id,
                      'client_service_id' => $srv->id,
                      'reason' => $request->get('reason'),
                      'tracking' => $srv->tracking,
                      'log_date' => Carbon::now()->format('m/d/Y h:i:s A')
                  ]);

              }

            // Update discount
            if($__oldDiscount != null && $__oldDiscount != $request->get('discount')) {
                $discnotes = ' updated discount from Php' . $__oldDiscount . ' to Php' . $request->get('discount').', ';
                $discnotes_cn = ' 已更新折扣 ' . $__oldDiscount . ' 到 ' . $request->get('discount') .', ';
                $oldDiscount = $__oldDiscount;
                $newDiscount = $request->get('discount');
            }

            if($__oldDiscount == $request->get('discount')){
                $oldDiscount = $__oldDiscount;
                $newDiscount = $request->get('discount');
            }

            // New Discount
            if($__oldDiscount == null) {
                $discnotes = ' discounted an amount of Php'.$request->get('discount').', ';
                $discnotes_cn = ' 已折扣额度 Php'.$request->get('discount').', ';
                $newDiscount = $request->get('discount');
            }

      }
      else {
          $discountExist = ClientTransaction::where('client_service_id', $srv->id)->where('type','Discount')->first();
          if($discountExist && $request->active != 0){
              $oldDiscount = $discountExist->amount;
              $newDiscount = 0;


              ClientTransaction::where('client_id', $srv->client_id)
                  ->where('group_id', $srv->group_id)
                  ->where('client_service_id', $srv->id)
                  ->where('tracking', $srv->tracking)
                  ->forceDelete();

                // When user removed discount
                $discnotes = ' removed discount of Php ' . $discountExist->amount . ', ';
                $discnotes_cn = ' 移除折扣 ' . $discountExist->amount.', ';

          }
      }

      $old_total_charge = $srv->cost + $srv->tip + $srv->charge + $srv->com_client + $srv->com_agent;
      $new_total_charge = ($request->cost != null ? $request->cost : $srv->cost) +
                          ($request->tip != null ? $request->tip : $srv->tip) +
                          ($request->charge != null ? $request->charge : $srv->charge)
                          + $srv->com_client + $srv->com_agent;

      if($newDiscount > 0 || $oldDiscount > 0 ){
          $old_total_charge -= $oldDiscount;
          $new_total_charge -= $newDiscount;
      }
      $service_status = $request->status;

        if($request->get('active') == 1) { // Enabled
            $toAmount = $new_total_charge;
        } elseif($request->get('active') == 0) { // Disabled
            $toAmount = 0;
        }

        if ($old_total_charge != $new_total_charge || $service_status == 'complete') {
            if($service_status == 'complete' && $service_status != $srv->status){
                $translog = 'Total service charge is Php' . $toAmount;
                $translog_cn = '总服务费 Php' . $toAmount;
            }
            else if($service_status == 'complete' && $service_status == $srv->status){
                 $translog = 'Total service charge from Php' . ($old_total_charge) . ' to Php' . $toAmount;
                 $translog_cn = '总服务费从 Php' . ($old_total_charge) . ' 到 Php' . $toAmount;
            }
            else{
                $translog = 'Total service charge from Php' . ($old_total_charge) . ' to Php' . $toAmount;
                $translog_cn = '总服务费从 Php' . ($old_total_charge) . ' 到 Php' . $toAmount;
            }

            $newVal +=$new_total_charge;
            $oldVal +=$old_total_charge;
        }



        if ($old_total_charge == $new_total_charge && $translog == '' && $srv->status != $service_status) {
            $translog = 'Service status change from '.$srv->status.' to '.$service_status;
            $translog_cn = '';
        }

      if($request->status!=null) {
          $srv->status = $request->status;
      }
      if($request->active!=null) {
          $srv->active = $request->active;
      }

      $srv->cost = (isset($request->cost) ? $request->cost : $srv->cost);
      $srv->tip = (isset($request->tip) ? $request->tip : $srv->tip);

      //$srv->cost = $request->cost;
      //$srv->tip = $request->tip;

      $srv->remarks = $note;
      $srv->save();

          // Check package updates
          ClientService::where('group_id', $clientService->group_id)
              ->where('client_id', $clientService->client_id)
              ->where('service_id', $clientService->service_id)
              ->where('tracking', $clientService->tracking)
              ->select('tracking')
              ->get()
              ->map(function($d) {
                  $this->updatePackageStatus($d->tracking);
              });

        //save transaction logs
        $log =  ' : '.$discnotes .$translog.'. ' . $transtat;
        $log_cn =  ' : '.$discnotes_cn . $translog_cn. '. ' . $transtat_cn;
        if($translog != '' || $transtat != '' || $discnotes != ''){
            $newVal = $oldVal - $newVal;
            //$user = Auth::user();
            if($oldactive == 0 && $request->active == 1){
                $newVal = '-'.$newVal;
            }


            $log_data = array(
                'client_service_id' => $srv->id,
                'client_id' => $srv->client_id,
                'group_id' => $srv->group_id,
                'log_type' => 'Transaction',
                'log_group' => 'service',
                'detail'=> $log,
                'detail_cn'=> $log_cn,
                'amount'=> $newVal,
            );

            if($oldstatus != $service_status && $service_status == 'complete'){
                $log_data['detail'] = 'Completed Service '.$log;
                $log_data['detail_cn'] = '完成的服务 '.$log_cn;
                $log_data['amount'] = '-'.$new_total_charge;
            }
            else{
                if(($srv->status != 'complete')){
                    $log_data['amount'] = 0;
                }
                else{
                    $log_data['amount'] = '-'.$new_total_charge;
                }

                $log_data['detail'] = 'Updated Service '.$log;
                $log_data['detail_cn'] = '服务更新 '.$log_cn;
            }

            LogController::save($log_data);
        }
     }

     $response['status'] = 'Success';
     $response['code'] = 200;
     $response['data']  = $clientServices;

 		 return Response::json($response);
 	}




  public function switchBranch($groupId, $branchId) {

    if($branchId !== 0){

        $group = Group::where('id',$groupId)->first();
        if($group){
            $branchGroup = BranchGroup::where('group_id',$groupId)->first();
            $branchGroup->branch_id = $branchId;
            $branchGroup->save();
        }


        $level = $group->service_profile_id;

        $members = GroupUser::where('group_id',$groupId)->pluck('user_id');

        $member_services = ClientService::whereIn('client_id',$members)->where('group_id',$groupId)->get();


        foreach($member_services as $ms){
            $getService = Service::where('id',$ms->service_id)->first();
            if($getService){
                $charge = 0;
                $cost = 0;
                $tip = 0;
                if($branchId > 1){
                    $amounts = ServiceBranchCost::where('branch_id',$branchId)->where('service_id',$getService->id)->first();
                    $charge = $amounts->charge;
                    $cost = $amounts->cost;
                    $tip = $amounts->tip;
                }

                if($level > 0){ //service profile
                    $pcost = ServiceProfileCost::where('profile_id',$level)->where('branch_id',$branchId)->where('service_id',$getService->id)->first();

                    $charge = $pcost->charge;
                    $cost = $pcost->cost;
                    $tip = $pcost->tip;

                    $charge = ($charge > 0 ? $charge : $getService->charge);
                    $cost = ($cost > 0 ? $cost : $getService->cost);
                    $tip = ($tip > 0 ? $tip : $getService->tip);
                }

                if($level == 0 && $branchId == 1){
                    $charge = $getService->charge;
                    $cost = $getService->cost;
                    $tip = $getService->tip;
                }

                $serv = ClientService::find($ms->id);
                $serv->charge = $charge;
                $serv->tip = $tip;
                $serv->save();
            }
        }

        $response['status'] = 'Success';
        $response['code'] = 200;

      }else{
        $response['status'] = 'Error';
        $response['code'] = 401;
      }

    	return Response::json($response);

    }


    //ServiceProfile
    public function switchCostLevel($groupId, $level) {

    if($groupId !== 0){

        $group = Group::where('id',$groupId)->first();
        $branchGroup = BranchGroup::where('group_id',$groupId)->first();
        if($group){
            $group->service_profile_id = $level;
            $group->save();
        }

        $members = GroupUser::where('group_id',$groupId)->pluck('user_id');

        $member_services = ClientService::whereIn('client_id',$members)->where('group_id',$groupId)
                            ->where(function ($query) {
                                $query->where('status', '!=', 'complete')
                                     ->where('status', '!=', 'released');
                            })
                            ->get();

        foreach($member_services as $ms){
            $getService = Service::where('id',$ms->service_id)->first();
            if($getService){
                $cost = 0;
                $charge = 0;
                $tip = 0;
                $client = 0 ;
                $agent = 0 ;

                if($group->branch_id > 1){
                    $amounts = ServiceBranchCost::where('branch_id',$branchGroup->branch_id)->where('service_id',$getService->id)->first();
                    $charge = $amounts->charge;
                    $cost = $amounts->cost;
                    $tip = $amounts->tip;
                }

                if($level > 0){
                    $pcost = ServiceProfileCost::where('profile_id',$level)->where('service_id',$getService->id)->where('branch_id',$branchGroup->branch_id)->first();
                    $charge = $pcost->charge;
                    $cost = $pcost->cost;
                    $tip = $pcost->tip;
                    $client = $pcost->com_client;
                    $agent = $pcost->com_agent;

                    $charge = ($charge > 0 ? $charge : $getService->charge);
                    $cost = ($cost > 0 ? $cost : $getService->cost);
                    $tip = ($tip > 0 ? $tip : $getService->tip);
                }

                if($level == 0 && $branchGroup->branch_id == 1){
                    $charge = $getService->charge;
                    $cost = $getService->cost;
                    $tip = $getService->tip;
                }


                $serv = ClientService::find($ms->id);
                if($charge > 0){
                    $serv->charge = $charge;
                }
                //$serv->cost = $cost;
                $serv->com_client = $client;
                $serv->com_agent = $agent;
                if($tip > 0){
                    $serv->tip = $tip;
                }
                $serv->save();
            }
        }

        $response['status'] = 'Success';
        $response['code'] = 200;

      }else{
        $response['status'] = 'Error';
        $response['code'] = 401;
      }

      return Response::json($response);

    }


    public static function createOrDeleteCommission($model, $original) {

        //DELETE
        //if user change service status from complete to on process or pending, delete commission from that service
        if(($original['status'] == 'complete' && $original['status'] != $model->status) || ($original['active'] != $model->active && $model->active == 0)){

            $check =  Log::where('log_type', 'Commission')->where('client_service_id', $model->id)
                            ->where('client_id',$model->client_com_id)
                            ->first();

            if($check){
                $commissionClient = ClientTransaction::where('client_id',$model->client_com_id)->where('tracking', $check->id)->where('type','Deposit')->where('is_commission',1)->first();
                $check->delete();
                if($commissionClient){
                    // Delete from client_transactions
                    $commissionClient->forceDelete();
                    Log::where('transaction_id',$commissionClient->id)->where('amount',$commissionClient->amount)->delete();
                }
            }

            $check =  Log::where('log_type', 'Commission')->where('client_service_id', $model->id)
                            ->where('client_id',$model->agent_com_id)
                            ->first();

            if($check){
                $commissionAgent = ClientTransaction::where('client_id',$model->agent_com_id)->where('tracking', $check->id)->where('type','Deposit')->where('is_commission',1)->first();
                $check->delete();
                if($commissionAgent){
                    // Delete from client_transactions
                    $commissionAgent->forceDelete();
                    Log::where('transaction_id',$commissionAgent->id)->where('amount',$commissionAgent->amount)->delete();
                }
            }
        }


        //CREATE
        //if user change service status from on process or pending to complete create commission to client and agent
        if($model->status=='complete' && $original['status'] != $model->status && $model->active == 1 && $model->group_id > 0){

            $user = Auth::user();
            date_default_timezone_set("Asia/Manila");
            $date = date('m/d/Y h:i:s A');

            $detail_cn = DB::table('services')->select('detail_cn')->where('id',$model->service_id)->first()->detail_cn;
            $group_name = DB::table('groups')->select('name')->where('id',$model->group_id)->first()->name;


            $d = Carbon::now()->format('Ymd');
            $year =  substr($d,0,4);
            $month =  substr($d,4,2);
            $day =  substr($d,6,2);
            if((int)$day >= 1 && (int)$day <= 10){
                $day = 10;
            }
            else if((int)$day >= 11 && (int)$day <= 20){
                $day = 20;
            }
            else{
                $day = 31;
            }
            //Client Commissions
            if($model->client_com_id != '' && $model->client_com_id>0){
                $client = User::where('id',$model->client_com_id)->select('first_name','last_name')->first();
                $log = "Completed Service ".$model->detail.". Commission is ".$model->com_client." for ".$client->first_name.' '.$client->last_name.".";
                $log_cn = "完成的服务 ".$detail_cn.". 对于 ".$client->first_name.' '.$client->last_name." 佣金是 ".$model->com_client.".";

                $savelog = new Log;
                $savelog->client_service_id = $model->id;
                $savelog->client_id = $model->client_com_id;
                $savelog->group_id = $model->group_id;
                $savelog->processor_id = Auth::user()->id;
                $savelog->log_date = date('Y-m-d');
                $savelog->log_type = 'Commission';
                $savelog->log_group = 'client';
                $savelog->detail = $log;
                $savelog->detail_cn = $log_cn;
                $savelog->amount = $model->com_client;
                $savelog->save();


                $depo = new ClientTransaction;
                $depo->client_id = $model->client_com_id;
                $depo->type = 'Deposit';
                $depo->group_id = null;
                $depo->tracking = $savelog->id;
                $depo->amount = $model->com_client;
                $depo->is_commission = 1;
                $depo->save();

                //save transaction logs
                $detail = 'Received commission Php'.$model->com_client.' from group '.$group_name.'.';
                $detail_cn = $detail;
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $model->client_com_id,
                    'group_id' => null,
                    'processor_id' => Auth::user()->id,
                    'log_date' => date('Y-m-d'),
                    'log_type' => 'Transaction',
                    'log_group' => 'deposit',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> $model->com_client,
                );
                 LogController::save($log_data);

            }

            //Agent Commissions
            if($model->agent_com_id != '' && $model->agent_com_id>0){
                $agent = User::where('id',$model->agent_com_id)->select('first_name','last_name')->first();

                $log = "Completed Service ".$model->detail.". Commission is ".$model->com_agent." for ".$agent->first_name.' '.$agent->last_name.".";
                $log_cn = "完成的服务 ".$detail_cn.". 对于 ".$agent->first_name.' '.$agent->last_name." 佣金是 ".$model->com_agent.".";
                $savelog = new Log;
                $savelog->client_service_id = $model->id;
                $savelog->client_id = $model->agent_com_id;
                $savelog->group_id = $model->group_id;
                $savelog->processor_id = Auth::user()->id;
                $savelog->log_date = date('Y-m-d');
                $savelog->log_type = 'Commission';
                $savelog->log_group = 'agent';
                $savelog->detail = $log;
                $savelog->detail_cn = $log_cn;
                $savelog->amount = $model->com_agent;
                $savelog->save();

                $depo = new ClientTransaction;
                $depo->client_id = $model->agent_com_id;
                $depo->type = 'Deposit';
                $depo->group_id = null;
                $depo->tracking = $savelog->id;
                $depo->amount = $model->com_agent;
                $depo->is_commission = 1;
                $depo->save();

                //save transaction logs
                $detail = 'Received commission Php'.$model->com_agent.' from group '.$group_name.'.';
                $detail_cn = $detail;
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $model->agent_com_id,
                    'group_id' => null,
                    'processor_id' => Auth::user()->id,
                    'log_date' => date('Y-m-d'),
                    'log_type' => 'Transaction',
                    'log_group' => 'deposit',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> $model->com_agent,
                );
                 LogController::save($log_data);
            }
        }

    }


    // EXPORT EXCEL
    public function showServiceDates($group_id){

            $dates = DB::table('client_services as cs')
            ->select(DB::raw('cs.*, date_format(STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s"),"%Y-%m-%d") as sdate'))
            ->where('active',1)->where('group_id',$group_id)
            ->orderBy('id','DESC')
            ->groupBy('sdate')
            ->pluck('sdate');

            return $dates;
    }

    public function showServiceAdded(Request $request, $group_id, $date = 0){


            $from = $request->input('from_date');
            $to = $request->input('to_date');

            $groupServices = ClientService::where('group_id',$group_id)
                                ->groupBy('service_id')
                                ->where(function($q) use($from, $to, $date){
                                  if($to != ''){
                                      $q->whereBetween('created_at', [date($from), date($to)])->get();
                                  }else{
                                      $q->where('created_at','LIKE', '%'.$from.'%');
                                  }
                                })
                                ->pluck('service_id');

            $profileServices = Service::select('id','detail')->whereIn('id',$groupServices)->get();
            return $profileServices;

    }

    private function statusChinese($status){
        $s = strtolower(trim($status," "));
        $stat = '';
        if($s == 'complete'){
            $stat = '已完成';
        }
        if($s == 'on process'){
            $stat = '办理中';
        }
        if($s == 'pending'){
            $stat=  '待办';
        }
        return $stat;
    }

    private function DateChinese($date){
        $d = explode(" ",strtolower($date));
        switch($d[0]){
            case "jan":
                return "一月"." ".$d[1];
                break;
            case "feb":
                return "二月"." ".$d[1];
                break;
            case "mar":
                return "三月"." ".$d[1];
                break;
            case "apr":
                return "四月"." ".$d[1];
                break;
            case "may":
                return "五月"." ".$d[1];
                break;
            case "jun":
                return "六月"." ".$d[1];
                break;
            case "jul":
                return "七月"." ".$d[1];
                break;
            case "aug":
                return "八月"." ".$d[1];
                break;
            case "sep":
                return "九月"." ".$d[1];
                break;
            case "oct":
                return "十月"." ".$d[1];
                break;
            case "nov":
                return "十一月"." ".$d[1];
                break;
            case "dec":
                return "十二月"." ".$d[1];
                break;
            default:
                return $date;
        }

    }


    public function getGroupSummary(Request $request){


      $filename = Carbon::now();

      $groupInfo = [];
      $groupInfo['total_complete_service_cost'] = number_format($this->getGroupTotalCompleteServiceCost($request->id),2);
      $groupInfo['total_cost'] = number_format($this->getGroupTotalCost($request->id),2);
      $groupInfo['total_payment'] = number_format($this->getGroupPayment($request->id),2);
      $groupInfo['total_discount'] = number_format($this->getGroupTotalDiscount($request->id),2);
      $groupInfo['total_refund'] = number_format($this->getGroupTotalRefund($request->id),2);
      $groupInfo['total_balance'] = number_format($this->getGroupTotalBalance($request->id),2);
      $groupInfo['total_collectables'] = number_format($this->getGroupTotalCollectables($request->id),2);
      $groupInfo['total_deposit'] = number_format($this->getGroupDeposit($request->id),2);

      $export = null;
      switch($request->type){

        case 'by-service':
              $export = new ByServiceExport($request->id, $request->lang, $request->data, $groupInfo, $request);
        break;

        case 'by-members':
              $export = new ByMemberExport($request->id, $request->lang, $request->data, $groupInfo);
        break;

        case 'by-batch':
              $export = new ByBatchExport($request->id, $request->lang, $request->data, $groupInfo, $request);
        break;
      }

      if($request->export_type =='excel'){
          return Excel::download($export, 'xxxx.xlsx');
      }
      else if($request->export_type =='preview'){

      }
      else{
        PDF::setOptions(['dpi' => 150, 'defaultFont' => 'simhei']);

        switch($request->type){

          case 'by-service':
                 $export = $this->getExportedByService($request);
                 $pdf = PDF::loadView('export.service_pdf', $export);
          break;

          case 'by-members':
                 $export = $this->getExportedByMember($request);
                 $pdf = PDF::loadView('export.member_pdf', $export);
          break;

          case 'by-batch':
                $export = $this->getExportedByBatch($request);
                $pdf = PDF::loadView('export.batch_pdf', $export);
          break;
        }

         return $pdf->download('xxxx.pdf');
      }

 }

 public function previewReport(Request $request){

     $response = $this->getExportedByService($request);
    return $response;
 }


 public function getExportedByBatch($request){
   $temp = [];
   $ctr = 0;
   $response = [];

   foreach($request->data as $data){

      $datetime = new DateTime($data['sdate']);
      $getdate = $datetime->format('M d,Y');

       $temp['sdate'] = strtotime($data['sdate']);
       $temp['total_service_cost'] = $data['total_service_cost'];
       $temp['group_id'] = $data['group_id'];
       $temp['detail'] = $data['detail'];
       $temp['service_date'] = $data['service_date'];

       if($request->lang === 'EN'){
           $temp['service_date'] = $getdate;
       }
       else{
           $temp['service_date'] = $this->DateChinese($getdate);
       }

       $temPackage = [];
       $j = 0;
       $members = [];
      foreach($data['members'] as $p){
         if(isset($p['first_name'])){
           $p['name'] = $p['first_name']. " " . $p['last_name'];
         }else{
           $p['name'] = "";
         }
         $members[$j] =  $p;
         $j++;
       }

       $temp['members'] =  $members;
       $response[$ctr] =  $temp;
       $ctr = $ctr + 1;

   }

    $result2 = collect($response)->sortBy('sdate')->reverse()->toArray();

    usort($response, function($a, $b)
    {
             if ($a["sdate"] == $b["sdate"])
               return (0);
             return (($a["sdate"] > $b["sdate"]) ? -1 : 1);
    });

    $ctr = 0;
    $totalBal = 0;
    $totalPre = 0;

    foreach($result2 as $r){
      $members = [];
      $j = 0;

      foreach($r['members'] as $member){
          $services = [];
          $i = 0;

          foreach($member['services'] as $s){
              if($s["active"] == -1){
                 $totalBal = ((float) $totalBal) - ((float) $s["total_charge"]);
              }else{
                if($s["active"] == 1 && (strtolower($s['status']) == 'complete' || strtolower($s['status']) == 'released') ){
                  $totalBal = ((float) $totalBal) - ((float) $s["total_charge"] - (float) $s["discount"]);
                }
              }

            $s["total_service_cost"] = $totalBal;

            if($request->lang === 'EN'){
                $s['status'] = ucfirst($s['status']);
            }else{
                $s['status'] = $this->statusChinese($s['status']);
            }

            $totalPre = $totalBal;

            $services[$i] = $s;
            $i++;
          }
          $member['services'] = $services;
          $members[$j] = $member;
          $j++;
      }

      $result2[$ctr]['members'] = $members;
      $ctr++;
    }


    $lang = [];

    if($request->lang === 'EN'){
        $lang['_date'] = 'Date';
        $lang['_service'] = 'Service';
        $lang['_charge'] = 'Charge';

        $lang['_total_deposit'] = 'Total Deposit : ';
        $lang['_total_cost'] = 'Total Cost : ';
        $lang['_total_promo'] = 'Total Promo : ';
        $lang['_total_refund'] = 'Total Refund : ';
        $lang['_total_balance'] = 'Total Balance : ';
        $lang['_total_collectables'] = 'Total Collectables : ';
        $lang['_total_complete_cost'] = 'Total Complete Cost : ';

        $lang['_servic_name'] = 'Service Name';
        $lang['_latest_date'] = 'Latest Date';
        $lang['_total_service_cost'] = 'Total Service Cost';


        $lang['_transcation_history'] = 'Transactions History : ';

        $lang['_amount'] = 'Amount';
        $lang['_type'] = 'Type';
        $lang['_deposit'] = 'Deposit';

        $lang['_service_date'] = 'Service Date';
        $lang['_package'] = 'Package';
        $lang['_status'] = 'Status';
        $lang['_details'] = 'Details';
        $lang['_charge'] = 'Charge';
        $lang['_group_total_bal'] = 'Group Total Balance' ;

        $lang['_discount'] = 'Discount';
        $lang['_service_sub'] = 'Service Sub Total';

    }else{
        $lang['_date'] = '建立日期';
        $lang['_service'] = '服务';

        $lang['_charge'] = '收费';
        $lang['_group_total_bal'] = '总余额';
        $lang['_total_deposit'] = '总已付款 : ';
        $lang['_total_cost'] = '总花费 : ';
        $lang['_total_promo'] = '总促销 : ';
        $lang['_total_refund'] = '总退款 : ';
        $lang['_total_balance'] = '总余额 : ';
        $lang['_total_collectables'] = '总应收款 : ';
        $lang['_total_complete_cost'] = '总服务费 : ';

        $lang['_servic_name'] = '服务明细';
        $lang['_latest_date'] = '最近的服务日期';
        $lang['_total_service_cost'] = '总服务费';
        $lang['_transcation_history'] = '交易记录 : ';

        $lang['_amount'] = '共计';
        $lang['_type'] = '类型';
        $lang['_deposit'] = '预存款';

        $lang['_service_date'] = '服务日期';
        $lang['_package'] = '查询编号';
        $lang['_status'] = '状态';

        $lang['_details'] = '服务明细';
        $lang['_charge'] = '收费';
        $lang['_group_total_bal'] = '总余额';


        $lang['_service_sub'] = '服务小计';
        $lang['_discount'] = '折扣';
    }

    $result2 = collect($result2)->sortByDesc('sdate')->reverse()->toArray();

    $ctr = 0;
    foreach($result2 as $r){
        $j = 0;
        $members = [];
        $r['members'] = collect($r['members'])->reverse()->toArray();
        foreach($r['members'] as $member){
            $member['services'] = collect($member['services'])->sortBy('total_service_cost')->toArray();
            $members[$j] = $member;
            $j++;
        }
        $result2[$ctr]['members'] = $members;
        $ctr++;
    }
    $result2 = collect($result2)->reverse()->toArray();

    return [
        'services' => $result2,
        'lang' => $lang,
        'watermark' => public_path()."/images/watermark.png",
        'logo' => public_path()."/images/logo.png",
        'font'=> public_path()."/assets/fonts/simhei.ttf"
    ];

 }


 public function getExportedByMember($request){

   $ctr = 0;
   $temp = [];
   $response = [];
   $i = 0;

   $chrg = 0;
   $tempTotal = 0;
   $bal = 0;


   foreach($request->data as $data){
       $temp['id'] = $data['user_id'];
       $temp['name'] = $data['name'];
       $temp['is_vice_leader'] = $data['is_vice_leader'];
       $temp['user_id'] = $data['id'];

       $temp['packages'] = [];
       $temPackage = [];
       $j = 0;

       $totalServiceCost = 0;

       foreach($data['packages'] as $p){

         $datetime = new DateTime($p['created_at']);
         $getdate = $datetime->format('M d,Y');
         $gettime = $datetime->format('h:i A');

         $chrg = ($p['active'] == 0 || $p['status'] !== 'complete') ? 0 : ($p['charge'] + $p['cost'] + $p['tip']);

         if($p['active'] == 0){
              $sub = 0;
         }
         //
         if($p['active'] !== 0){
             $totalServiceCost += ($p['package_cost'] - $p['discount']);
         }
         //
         //Subtotal
         $sub = $chrg;

         //Per Person Balance
         if($p['active'] == 0){
             $sub = 0;
         }

         $bal += $sub;

         $tempTotal +=$sub;

         $p['total_service_cost'] = $tempTotal;
         $p['discount'] = ($p['active'] == 1) ? $p['discount'] : 0;
         $p['service_cost'] = ($p['active'] == 1) ? (($p['cost']) + ($p['charge']) + ($p['tip'])) : 0;


         if($request->lang === 'EN'){
             $p['datetime'] = $getdate;
             $p['status'] = ucfirst($p['status']);
         }else{
             $p['datetime'] = $this->DateChinese($getdate);
             $p['status'] = $this->statusChinese($p['status']);
         }
         $p['remarks'] = strip_tags($p['remarks']);

         $temPackage[$j] = $p;
         $j++;
       }

       $temp['packages'] =  $temPackage;
       $temp['total_service_cost'] = $totalServiceCost;
       $response[$ctr] =  $temp;
       $ctr = $ctr + 1;
     }


     $lang = [];

     if($request->lang === 'EN'){
         $lang['_date'] = 'Date';
         $lang['_service'] = 'Service';
         $lang['_status'] = 'Status';
         $lang['_charge'] = 'Charge';
         $lang['_group_total'] = 'Group Total Cost';
         $lang['_group_summary'] = 'Group Summary';
         $lang['_member_subtotal'] = '-- Member Subtotal --';
         $lang['_total_deposit'] = 'Total Deposit : ';
         $lang['_total_cost'] = 'Total Cost : ';
         $lang['_total_promo'] = 'Total Promo : ';
         $lang['_total_refund'] = 'Total Refund : ';
         $lang['_total_balance'] = 'Total Balance : ';
         $lang['_total_collectables'] = 'Total Collectables : ';
         $lang['_total_complete_cost'] = 'Total Complete Cost : ';
         $lang['_transcation_history'] = 'Transactions History : ';
         $lang['_amount'] = 'Amount';
         $lang['_type'] = 'Type';
         $lang['_deposit'] = 'Deposit';
         $lang['_discount'] = 'Discount';
         $lang['_service_sub'] = 'Service Sub Total';

     }else{
         $lang['_date'] = '建立日期';
         $lang['_service'] = '服务';
         $lang['_status'] = '状态';
         $lang['_charge'] = '收费';
         $lang['_group_total'] = '总余额' ;
         $lang['_group_summary'] = '总结报告';
         $lang['_member_subtotal'] = '-- 成员小计 --';
         $lang['_total_deposit'] = '总已付款 : ';
         $lang['_total_cost'] = '总花费 : ';
         $lang['_total_promo'] = '总促销 : ';
         $lang['_total_refund'] = '总退款 : ';
         $lang['_total_balance'] = '总余额 : ';
         $lang['_total_collectables'] = '总应收款 : ';
         $lang['_total_complete_cost'] = '总服务费 : ';
         $lang['_transcation_history'] = '交易记录 : ';
         $lang['_amount'] = '共计';
         $lang['_type'] = '类型';
         $lang['_deposit'] = '预存款';
         $lang['_service_sub'] = '服务小计';
         $lang['_discount'] = '折扣';
     }

     return [
         'members' => $response,
         'lang' => $lang,
         'watermark' => public_path()."/images/watermark.png",
         'logo' => public_path()."/images/logo.png"
     ];
 }

 public function getExportedByService($request){

   $temp = [];
   $ctr = 0;
   $response = [];

   foreach($request->data as $data){

       $temp['total_service'] = $data['total_service'];
       $temp['total_service_cost'] = $data['total_service_cost'];
       $temp['service_count'] = $data['service_count'];
       $temp['group_id'] = $data['group_id'];
       $temp['detail'] = $data['detail'];

       $datetime = new DateTime($data['service_date']);
       $getdate = $datetime->format('M d,Y');

       if($request->lang == 'EN'){
           $temp['service_date']=  $getdate;
       }else{
           $temp['service_date']=  $this->DateChinese($getdate);
       }

       $temPackage = [];
       $j = 0;

       foreach($data['bydates'] as $p){

         $datetime = new DateTime($p['sdate']);
         $getdate = $datetime->format('M d,Y');

         if($request->lang == 'EN'){
           $p['sdate'] =  $getdate;
         }else{
           $p['sdate'] = $this->DateChinese($getdate);
         }

         $members = [];
         $ctrM = 0;
         foreach($p['members'] as $m){
           $clientServices = [];
           $tmpCtr = 0;

           $m['name'] = ((isset($m['first_name'])) ? $m['first_name'] : ""). " " . (isset($m['last_name']) ? $m['last_name'] : '');
           $m['discount'] = ($m['service']['active'] == 1) ? $m['service']['discount'] : 0;
           $m['service_cost'] = ($m['service']['active'] == 1) ? (($m['service']['cost']) + ($m['service']['charge']) + ($m['service']['tip'])) : 0;
           $stat =($m['service']['status'] = ($m['service']['active'] == 0) ? 'CANCELLED' : $m['service']['status']);
           $m['total_charge'] =  ($m['service']['active'] == 1) ? ((($m['service']['cost']) + ($m['service']['charge']) + ($m['service']['tip'])) - $m['service']['discount']) : 0;

           if($request->lang === 'EN'){
               $m['service']['status'] = ucfirst($stat);
           }else{
               $m['service']['status'] = $this->statusChinese($stat);
           }

           $members[$ctrM] = $m;
           $ctrM++;
         }
         $p['members'] =  $members;
         $temPackage[$j] = $p;
         $j++;
       }

       $temp['bydates'] =  $temPackage;
       $response[$ctr] =  $temp;
       $ctr = $ctr + 1;
    }

    $lang = [];

    if($request->lang === 'EN'){
        $lang['_date'] = 'Date';
        $lang['_service'] = 'Service';
        $lang['_status'] = 'Status';
        $lang['_charge'] = 'Charge';
        $lang['_group_total_bal'] = 'Group Total Balance' ;
        $lang['_total_deposit'] = 'Total Deposit : ';
        $lang['_total_cost'] = 'Total Cost : ';
        $lang['_total_promo'] = 'Total Promo : ';
        $lang['_total_refund'] = 'Total Refund : ';
        $lang['_total_balance'] = 'Total Balance : ';
        $lang['_total_collectables'] = 'Total Collectables : ';
        $lang['_total_complete_cost'] = 'Total Complete Cost : ';

        $lang['_servic_name'] = 'Service Name';
        $lang['_latest_date'] = 'Latest Date';
        $lang['_total_service_cost'] = 'Total Service Cost';
        $lang['_package'] = 'Package';

        $lang['_transcation_history'] = 'Transactions History : ';
        $lang['_discount'] = 'Discount';
        $lang['_service_sub'] = 'Service Sub Total';
        $lang['_amount'] = 'Amount';
        $lang['_type'] = 'Type';
        $lang['_deposit'] = 'Deposit';

    }else{
        $lang['_date'] = '建立日期';
        $lang['_service'] = '服务';
        $lang['_status'] = '状态';
        $lang['_charge'] = '收费';
        $lang['_group_total_bal'] = '总余额';
        $lang['_total_deposit'] = '总已付款 : ';
        $lang['_total_cost'] = '总花费 : ';
        $lang['_total_promo'] = '总促销 : ';
        $lang['_total_refund'] = '总退款 : ';
        $lang['_total_balance'] = '总余额 : ';
        $lang['_total_collectables'] = '总应收款 : ';
        $lang['_total_complete_cost'] = '总服务费 : ';

        $lang['_servic_name'] = '服务明细';
        $lang['_latest_date'] = '最近的服务日期';
        $lang['_total_service_cost'] = '总服务费';
        $lang['_package'] = '查询编号';

        $lang['_transcation_history'] = '交易记录 : ';
        $lang['_service_sub'] = '服务小计';
        $lang['_discount'] = '折扣';
        $lang['_amount'] = '共计';
        $lang['_type'] = '类型';
        $lang['_deposit'] = '预存款';
    }

    return [
        'services' => $response,
        'lang' => $lang,
        'watermark' => public_path()."/images/watermark.png",
        'logo' => public_path()."/images/logo.png"
    ];
 }




 public function getByService(Request $request, $id, $page = 20){

   $from = $request->input('from_date');
   $to = $request->input('to_date');


   if($from != '' || $to != ''){

     if($request->input('ids') != ''){
       $services = explode(',', $request->input('ids'));
     }else{
       $services = [];
     }

     if($request->input('status') != ''){
       $status = explode(',', $request->input('status'));
     }else{
       $status = [];
     }


     $clientServices = DB::table('client_services')
       ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, detail, created_at'))
       ->where('group_id',$id)

       ->where(function($q) use($from, $to, $services, $status){

         if(count($services) > 0){
           $q->whereIn('service_id', $services);
         }

         //if(count($status) > 0){
           $q->whereIn('status', $status);
         //}



         if($to != '' && $from != ''){
             $q->whereBetween('created_at', [date($from), date($to)])->get();
         }else{
             $q->where('created_at','LIKE', '%'.$from.'%');
         }
       })
       ->groupBy('service_id')
       ->orderBy('created_at','DESC')
       ->paginate($page);

   }
   else{
     $clientServices = DB::table('client_services')
       ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, detail, created_at'))
       ->where('group_id',$id)
       ->groupBy('service_id')
       ->orderBy('created_at','DESC')
       ->paginate($page);
   }


   $ctr = 0;
   $temp = [];
   $response = $clientServices;

   $chrg = 0;
   $tempTotal = 0;
   $bal = 0;

     foreach($clientServices->items() as $s){

       $query = ClientService::where('created_at', $s->created_at)->where('service_id',$s->service_id)->where('group_id', $id)->where('active', 1);

       $servicesByDate = DB::table('client_services')
         ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, detail, created_at, client_id'))
         ->where('group_id',$id)
         ->where('service_id',$s->service_id)
         ->groupBy(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'))
         ->orderBy('created_at','DESC')
         ->get();

       $translated = Service::where('id',$s->service_id)->first();

       $temp['detail'] = $s->detail;

       if($translated){
             if($request->input('lang') === 'CN'){
               $temp['detail'] = (($translated->detail_cn != '' && $translated->detail_cn != 'NULL') ? $translated->detail_cn : $translated->detail);
             }
         //$temp['total_service_cost'] = $translated->charge + $translated->cost + $translated->tip + $translated->com_client + $translated->com_agent;
       }

       // if($temp['total_service_cost'] == 0){
       //   $temp['detail'] = $s->detail;
       //   $temp['total_service_cost'] = ($query->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)")));
       // }


       $temp['service_date'] = $s->sdate;
       $temp['group_id'] = $id;

       $discountCtr = 0;
       $totalServiceCount = 0;

       $totalServiceCost = 0;
       foreach($servicesByDate as $sd){

         $queryClients = ClientService::where('service_id', $sd->service_id)->where('created_at', $sd->created_at)->where('group_id', $id)->orderBy('created_at','DESC')->orderBy('client_id')->groupBy('client_id')->get();

         $memberByDate = [];
         $ctr2 = 0;

         foreach($queryClients as $m){

           $clientServices = [];
           $tmpCtr = 0;

           $m->discount = ClientTransaction::where('client_service_id', $m->id)->where('type', 'Discount')->sum('amount');
           $discountCtr += $m->discount;

           $mem = ($memberByDate[$ctr2] = User::where('id',$m->client_id)->select('first_name','last_name')->first());
           $memberByDate[$ctr2]['tcost'] = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$sd->sdate)->where('group_id', $id)->where('client_id',$m->client_id)->value(DB::raw("SUM(cost + charge + tip +com_client + com_agent)"));
           $memberByDate[$ctr2]['service'] = $m;
           $memberByDate[$ctr2]['name'] = $mem['first_name']. " " . $mem['last_name'];
           $memberByDate[$ctr2]['created_at'] = $m->created_at;

           $chrg = ($m->active == 0 || strtolower($m->status) !== 'complete') ? 0 : ($m->charge + $m->cost + $m->tip  + $m->com_client + $m->com_client);

           $sub = $chrg;

           //Per Person Balance
           if($m->active == 0){
               $sub = 0;
           }

           if($request->lang === 'EN'){
               $m->status = ucfirst($m->status);
           }else{
               $m->status = $this->statusChinese($m->status);
           }

           $bal += $sub;

           $tempTotal +=$sub;

           $m->total_service_cost = $tempTotal;

           $ctr2++;

           $totalServiceCost += $chrg;

           if($m->active && $m->status != "cancelled")
             $totalServiceCount++;
        }

        $sd->members = $memberByDate;
     }



       $temp['total_service'] = ($query->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)")));
       $temp['service_count'] = $totalServiceCount;
       $temp['total_service_cost'] = $totalServiceCost; //here
       $temp['bydates'] = $servicesByDate;
       $response[$ctr] = $temp;
       $ctr++;
   }

   return Response::json($response);
 }


 public function getByBatch(Request $request, $groupId, $perPage = 10){

      $dates = $request->input('dates');

      if($dates != ''){

            $ds = explode(',', $dates);

            $clientServices = DB::table('client_services')
              ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, id, detail, created_at, service_id'))
              ->where('active',1)->where('group_id',$groupId)
              //->where(DB::raw('STR_TO_DATE(created_at, "%Y-%m-%d")'), '=', $date)

              ->where(function($q) use($ds){
                //foreach($ds as $key){
                     $q->whereIn(DB::raw('STR_TO_DATE(created_at, "%Y-%m-%d")'), $ds);
                     //$q->where(DB::raw('STR_TO_DATE(created_at, "%Y-%m-%d")'), '=', $key);
                //}

              })

              ->groupBy(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'))
              ->orderBy('id','DESC')
              ->paginate($perPage);
      }else{
            $clientServices = DB::table('client_services')
              ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, id, detail, created_at, service_id'))
              ->where('active',1)->where('group_id',$groupId)
              ->groupBy(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'))
              ->orderBy('id','DESC')
              ->paginate($perPage);
      }


       $ctr = 0;
       $temp = [];
       $response = $clientServices;


      foreach($clientServices->items() as $s){

         $query = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId);


         $temp['detail'] = $s->detail;

         $temp['service_date'] = $s->sdate;
         $temp['sdate'] = $s->sdate;
         $temp['group_id'] = $groupId;


         $datetime = new DateTime($s->sdate);
         $getdate = $datetime->format('Y-m-d');
         $temp['sdate'] =  $getdate;
         $temp['service_date'] = $getdate;


         $queryMembers = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->orderBy('created_at','DESC')->orderBy('client_id')->groupBy('client_id')->get();

         $ctr2 = 0;
         $members = [];
         $discountCtr = 0;
         $totalCost = 0;

         //for total complete cost
         $chrg = 0;
         $tempTotal = 0;
         $bal = 0;

         foreach($queryMembers as $m){
               $ss =  ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->where('client_id',$m->client_id)->get();

               $clientServices = [];
               $tmpCtr = 0;

               foreach($ss as $cs){
                 $cs->discount =  ClientTransaction::where('client_service_id', $cs->id)->where('type', 'Discount')->sum('amount');
                 if($cs->active !== 0){
                   $discountCtr += $cs->discount;
                   $totalCost += (($cs->cost + $cs->charge + $cs->tip + $cs->com_client + $cs->com_agent)) - $cs->discount;
                 }

                 $translated = Service::where('id',$cs->service_id)->first();

                 $cs->detail =  $cs->detail;

                 if($translated){
                       if($request->input('lang') === 'CN'){
                         $cs->detail = (($translated->detail_cn != '' && $translated->detail_cn != 'NULL') ? $translated->detail_cn : $cs->detail);
                       }
                 }

                 // if($request->input('lang') === 'EN'){
                 //     $cs->status = ucfirst($cs->status);
                 // }else{
                 //     $cs->status = $this->statusChinese($cs->status);
                 // }

                 $cs->remarks = strip_tags($cs->remarks);


                 $chrg = ($cs->active == 0 || (strtolower($cs->status) !== 'complete' && strtolower($cs->status) !== 'released') ) ? 0 : ($cs->charge + $cs->cost + $cs->tip);

                 if($cs->active == 0){
                      $cs->status = 'CANCELLED';
                 }

                 $sub = $chrg;

                 //Per Person Balance
                 if($cs->active == 0){
                     $sub = 0;
                 }

                 $bal += $sub;

                 $tempTotal +=$sub;

                 $cs->total_service_cost = 0; //here
                 $cs->total_charge = ($cs->active == 0 || (strtolower($cs->status) !== 'complete' && strtolower($cs->status) !== 'released')) ? 0 : (($cs->cost + $cs->charge + $cs->tip + $cs->com_client + $cs->com_agent));
                 $cs->service_cost =  ($cs->active == 0 || (strtolower($cs->status) !== 'complete' && strtolower($cs->status) !== 'released')) ? 0 : (($cs->cost + $cs->charge + $cs->tip + $cs->com_client + $cs->com_agent)) - $cs->discount;
                 $clientServices[$tmpCtr] = $cs;
                 $tmpCtr++;
               }

               $member = ($members[$ctr2] = User::where('id',$m->client_id)->select('first_name','last_name')->first());
               //  $members[$ctr2]['tcost']
               if($member){
                  $members[$ctr2]['name'] =  $member->first_name ." ". $member->last_name;
               }else{
                  $members[$ctr2]['name'] = "";
               }




               $members[$ctr2]['tcost'] = $query->where('client_id',$m->client_id)->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)"));
               $members[$ctr2]['services'] = $clientServices;
             $ctr2++;
         }
         $temp['total_service_cost'] = $totalCost;
         $temp['members'] = $members;
         $response[$ctr] = $temp;
         $ctr++;
       }

       return $response;
 }



 public function getMembers(Request $request, $id, $page = 20) {

     $sort = $request->input('sort');
     $search = $request->input('search');

     $search_id = 0;
     $q1 = '';  $q2 = ''; $spaces = 0;
     if (preg_match("/^\d+$/", $search)) {
         $search_id = 1;
     }

     if(preg_match('/\s/',$search)){
         $q = explode(" ", $search);
         $spaces = substr_count($search, ' ');
         if($spaces == 2){
             $q1 = $q[0]." ".$q[1];
             $q2 = $q[2];
         }
         if($spaces == 1){
             $q1 = $q[0];
             $q2 = $q[1];
         }
     }

     $mode = '';
     if($search_id == 1 && $spaces == 0){
         $mode = 'id';
     }
     else if($search_id == 0 && $spaces == 0 && $search != ''){
         $mode = 'name';
     }
     else if($spaces >0){
         $mode = 'fullname';
     }

     $mems = DB::table('group_user as g_u')
                 ->where('g_u.group_id', $id)
                 ->get();

     $gids = $mems->pluck('user_id');


     $response = DB::table('users as u')->select(DB::raw('u.id, CONCAT(u.last_name, " ", u.first_name) as name, g_u.is_vice_leader, g_u.total_service_cost, g_u.id as guid'))
                     ->leftjoin(DB::raw('(select * from group_user) as g_u'),'g_u.user_id','=','u.id')
                     ->whereIn('u.id', $gids)
                     ->where('g_u.group_id', $id)
                     ->when($mode == 'fullname', function ($query) use($q1,$q2){
                         return $query->where(function ($query1) use($q1,$q2) {
                             return $query1->where(function ($query2) use($q1,$q2) {
                                         $query2->where('u.first_name', '=', $q1)
                                               ->Where('u.last_name', '=', $q2);
                                     })->orwhere(function ($query2) use($q1,$q2) {
                                         $query2->where('u.last_name', '=', $q1)
                                               ->Where('u.first_name', '=', $q2);
                                     });
                         });
                     })
                     ->when($mode == 'id', function ($query) use($search){
                             return $query->where('u.id','LIKE','%'.$search.'%');
                     })
                     ->when($mode == 'name', function ($query) use($search){
                         return $query->where(function ($query2) use($search) {
                             $query2->where('u.first_name' ,'=', $search)
                                          ->orwhere('u.last_name' ,'=', $search);
                         });
                     })
                     ->when($sort != '', function ($q) use($sort){
                         $sort = explode('-' , $sort);
                         return $q->orderBy($sort[0], $sort[1]);
                     })->get();
                     //->paginate($page);

       return Response::json($response);
 }


 function addGroupPayment(Request $request){

     $validator = Validator::make($request->all(), [
           'group_id' => 'required',
       ]);

       if($validator->fails()) {
           $response['status'] = 'Failed';
           $response['errors'] = $validator->errors();
           $response['code'] = 422;
       } else {
           // $tracking = $request->get('tracking');

          $group_id = $request->get('group_id');

          for($i=0; $i<count($request->payments); $i++) {

             $client_id = $request->payments[$i]['client_id'];
             $cs_id = $request->payments[$i]['id'];
             $amount = $request->payments[$i]['amount'];
             $total_cost = $request->payments[$i]['total_cost'];
             $payment = ClientTransaction::where('type','Payment')->where('client_service_id',$cs_id)->first();

             if($payment){
                 $payment->amount += $amount;
                 $payment->save();
             }
             else{
                 $payment = new ClientTransaction;
                 $payment->client_id = $client_id;
                 $payment->client_service_id = $cs_id;
                 $payment->type = 'Payment';
                 $payment->group_id = $group_id;
                 $payment->amount = $amount;
                 $payment->save();
             }

             $service = ClientService::findOrFail($cs_id);
             if($service->payment_amount > 0){
                 $service->payment_amount += $amount;
             }
             else{
                 $service->payment_amount = $amount;
             }
             if($amount == $total_cost){
                 $service->is_full_payment = 1;
             }
             $service->save();

             // save transaction logs
             $detail = 'Paid an amount of Php '.$amount.'.';
             $detail_cn = '已支付 Php'.$amount.'.';
             $log_data = array(
                 'client_service_id' => null,
                 'client_id' => null,
                 'group_id' => $group_id,
                 'log_type' => 'Transaction',
                 'log_group' => 'payment',
                 'detail'=> $detail,
                 'detail_cn'=> $detail_cn,
                 'amount'=> $amount,
             );
             LogController::save($log_data);
            
           }

           $response['status'] = 'Success';
           $response['code'] = 200;
      }


      return Response::json($response);
  }

}

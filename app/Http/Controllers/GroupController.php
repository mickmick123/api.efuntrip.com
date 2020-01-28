<?php

namespace App\Http\Controllers;
//use Carbon\Carbon;
use App\ClientService;

use App\ClientTransaction;

use App\ContactNumber;

use App\Group;

use App\User;

use App\GroupUser;

use App\Package;

use App\Branch;

use App\Service;
use App\ServiceProfileCost;

use DB, Response, Validator;

use Illuminate\Http\Request;

class GroupController extends Controller
{

    private function generateGroupTracking() {
    	$numeric = '0123456789';

    	do {
    		$tracking = '';
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


    private function getGroupDeposit($id) {
        return ClientTransaction::where('group_id', $id)->where('type', 'Deposit')->sum('amount');
    }

    private function getGroupPayment($id) {
        return ClientTransaction::where('group_id', $id)->where('type', 'Payment')->sum('amount');
    }

    private function getGroupTotalDiscount($id) {
        return ClientTransaction::where('group_id', $id)->where('type', 'Discount')->sum('amount');
    }

    private function getGroupTotalRefund($id) {
        return ClientTransaction::where('group_id', $id)->where('type', 'Refund')->sum('amount');
    }

    private function getGroupTotalCost($id) {
        $groupTotalCost = ClientService::where('group_id', $id)
            ->where('active', 1)
            ->value(DB::raw("SUM(cost + charge + tip + com_agent + com_client)"));

        return ($groupTotalCost) ? $groupTotalCost : 0;
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
        $groupTotalCompleteServiceCost = ClientService::where('group_id', $id)
            ->where('active', 1)
            ->where('status', 'complete')
            ->value(DB::raw("SUM(cost + charge + tip + com_agent + com_client)"));

        return ($groupTotalCompleteServiceCost) ? $groupTotalCompleteServiceCost : 0;
    }

    private function getGroupTotalBalance($id) {
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

    private function getGroupTotalCollectables($id) {
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
            ->when($search != '', function ($q) use($search){
                return $q->where('g.id','LIKE', '%'.$search.'%')->orwhere('g.name','LIKE', '%'.$search.'%');
            })
            ->when($sort != '', function ($q) use($sort){
                $sort = explode('-' , $sort);
                return $q->orderBy($sort[0], $sort[1]);
            })
            ->paginate($perPage);

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

                $group->update(['leader_id' => $request->client_id]);

                DB::table('group_user')->where('group_id', $request->group_id)
                    ->whereIn('user_id', [$oldLeaderId, $request->client_id])
                    ->update(['is_vice_leader' => 0]);
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

            $group->contact = DB::table('contact_numbers')->where('group_id', $id)
                    ->select(array('number'))->first(); //here

            $group->total_complete_service_cost = $this->getGroupTotalCompleteServiceCost($id);
            $group->total_cost = $this->getGroupTotalCost($id);
            $group->total_payment = $this->getGroupDeposit($id) + $this->getGroupPayment($id);

            $group->total_discount = $this->getGroupTotalDiscount($id);
            $group->total_refund = $this->getGroupTotalRefund($id);
            $group->total_balance = $this->getGroupTotalBalance($id);
            $group->total_collectables = $this->getGroupTotalCollectables($id);

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
        		$group->name = $request->group_name;
        		$group->address = $request->address;
        		$group->save();

        		ContactNumber::updateOrCreate(
        			['group_id' => $id],
        			[
        				'number' => $request->contact_number['number'],
        				'is_primary' => 1,
        				'is_mobile' => $request->contact_number['is_mobile']
        			]
        		);

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


public function members($id, $page = 20) {

    $groups = DB::table('group_user as g_u')
        ->select(DB::raw('g_u.id, CONCAT(u.first_name, " ", u.last_name) as name, g_u.user_id,  g_u.is_vice_leader, g_u.total_service_cost'))
        ->leftjoin(DB::raw('(select * from users) as u'),'u.id','=','g_u.user_id')
        ->orderBy('g_u.id', 'desc')
        ->where('group_id', $id)
        ->paginate($page);

      $response = $groups;

      $ctr=0;
      $temp = [];
      foreach($groups->items() as $g){
         $packs = DB::table('packages as p')->select(DB::raw('p.*,g.name as group_name'))
                    ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                     ->where('client_id', $g->user_id)
                     ->where('group_id', $id)
                    ->orderBy('id', 'desc')
                    ->get();

        if(count($packs) > 0){
          foreach($packs as $p){
              $services = DB::table('client_services as cs')
                  ->select(DB::raw('cs.*'))
                  ->where('tracking',$p->tracking)
                  ->get();

              $p->package_cost = $services[0]->cost+ $services[0]->charge + $services[0]->tip + $services[0]->com_agent + $services[0]->com_client;
              $p->detail =  $services[0]->detail;
            //  $p->discount =
          }
          $temp['packages'] = $packs;
        }else{
          $temp['packages'] = [];
        }


        $temp['id'] = $g->id;
        $temp['name'] = $g->name;
        $temp['is_vice_leader'] = $g->is_vice_leader;
        $temp['user_id'] = $g->user_id;
        $temp['total_service_cost'] = $g->total_service_cost;
        $response[$ctr] =  $temp;
        $ctr++;
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


  public function getClientPackagesByBatch($groupId, $page = 20){

        $clientServices = DB::table('client_services')
          ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, id, detail, created_at'))
          ->where('active',1)->where('group_id',$groupId)
          ->groupBy('created_at')
          ->orderBy('id','DESC')
          ->paginate($page);

        $ctr = 0;
        $temp = [];
        $response = $clientServices;

        foreach($clientServices->items() as $s){

          $query = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->where('active', 1);

          $temp['total_service_cost'] = $query->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)"));
          $temp['detail'] = $s->detail;
          $temp['service_date'] = $s->sdate;
          $temp['sdate'] = $s->sdate;
          $temp['group_id'] = $groupId;

          $queryMembers = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->orderBy('created_at','DESC')->orderBy('client_id')->groupBy('client_id')->get();

          $ctr2 = 0;
          $members = [];

          foreach($queryMembers as $m){
                $ss =  ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->where('client_id',$m->client_id)->get();

            //  if(count($ss)){
                  $members[$ctr2] = User::where('id',$m->client_id)->select('first_name','last_name')->first();
                  $members[$ctr2]['tcost'] = $query->where('client_id',$m->client_id)->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)"));
                  $members[$ctr2]['services'] = $ss;
            //  }
              $ctr2++;
          }
          $temp['members'] = $members;
          $response[$ctr] = $temp;
          $ctr++;
        }

        return Response::json($response);
  }


   public function getClientPackagesByService($groupId, $page = 20){

     $clientServices = DB::table('client_services')
       ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, detail, created_at'))
       ->where('active',1)->where('group_id',$groupId)
       ->groupBy('service_id')
       ->orderBy('detail','DESC')
       ->paginate($page);

     $ctr = 0;
     $temp = [];
     $response = $clientServices;

     foreach($clientServices->items() as $s){

         $query = ClientService::where('service_id', $s->service_id)->where('group_id', $groupId)->where('active', 1);

         $temp['total_service_cost'] = $query->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)"));
         $temp['detail'] = $s->detail;
         $temp['service_date'] = $s->sdate;
         $temp['sdate'] = $s->sdate;
         $temp['group_id'] = $groupId;

         $queryClients = ClientService::where('service_id', $s->service_id)->where('group_id', $groupId)->orderBy('created_at','DESC')->orderBy('client_id')->groupBy('client_id')->get();

         $ctr2 = 0;
         $members = [];
         foreach($queryClients as $m){

             $ss =  ClientService::where('service_id', $s->service_id)->where('group_id', $groupId)->where('client_id',$m->client_id)->get();

             //if(count($ss)){
                 $members[$ctr2] = User::where('id',$m->client_id)->select('first_name','last_name')->first();
                 $members[$ctr2]['tcost'] = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->where('client_id',$m->client_id)->value(DB::raw("SUM(cost + charge + tip +com_client + com_agent)"));
                 $members[$ctr2]['services'] = $ss;
             //}
             $ctr2++;
         }

         $temp['members'] = $members;
         $response[$ctr] = $temp;
         $ctr++;
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
          foreach($group_members as $gm){
              $usr =  User::where('id',$gm->user_id)->select('id','first_name','last_name')->limit(1)->get();
              if($usr){
                  $arr[$ctr] = $gm;
                  $arr[$ctr]['client'] =$usr[0];
                  $arr[$ctr]['client']['packages'] = Package::where('client_id',$gm->user_id)->where('group_id',$gm->group_id)->get();

                $ctr++;
              }
          }
          $group_members->data = $arr;

          return Response::json($response);

  }




   public function addServices(Request $request) {

      $g = Group::findOrFail($request->group_id);
      $level = $g->service_profile_id;

      $trackingArray = [];
      $oldNewArray = [];
      $oldNewArrayChinese = [];

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
                  'remarks' => $author,
                  'group_id' => $request->group_id,
                  'tracking' => $trackingArray[$i],
                  'active' => 1,
                  'extend' => null
              ]);

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


}

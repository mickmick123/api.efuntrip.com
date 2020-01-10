<?php

namespace App\Http\Controllers;

use App\ClientService;

use App\ClientTransaction;

use App\ContactNumber;

use App\Group;

use App\User;

use App\GroupUser;

use App\Package;

use App\Branch;

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

    public function manageGroupsPaginate($perPage = 20) {
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
            ->select(array('id', 'name', 'leader_id', 'tracking', 'address'))
            ->find($id);

        if( $group ) {
            $group->leader = DB::table('users')->where('id', $group->leader_id)
                ->select(array('first_name', 'last_name'))->first();

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
            GroupUser::create([
                'group_id' => $request->id,
                'user_id' => $clientId,
                'is_vice_leader' => 0,
                'total_service_cost' => 0
            ]);
        }
        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $request->id;

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


            // $queryClients = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->orderBy('created_at','DESC')->orderBy('client_id')->groupBy('client_id')->get();
            //
            // $ctr2 = 0;
            // $client = [];
            // foreach($queryClients as $m){
            //
            //     $ss =  ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->where('client_id',$m->client_id)->get();
            //
            //     if(count($ss)){
            //         $client[$ctr2] = User::where('id',$m->client_id)->select('first_name','last_name')->first();
            //         $client[$ctr2]['tcost'] = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->where('client_id',$m->client_id)->value(DB::raw("SUM(cost + charge + tip +com_client + com_agent)"));
            //         $client[$ctr2]['services'] = $ss;
            //     }
            //     $ctr2++;
            // }
            //
            // $temp['clients'] = $client;


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

   public function addGroupServices(){

     
   }


}

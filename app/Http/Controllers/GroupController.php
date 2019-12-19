<?php

namespace App\Http\Controllers;

use App\ClientService;

use App\ClientTransaction;

use App\ContactNumber;

use App\Group;

use App\User;

use App\Package;

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

    public function manageGroupsPaginate() {
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
            ->paginate(20);

        $response = $groups;

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

}

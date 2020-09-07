<?php

namespace App\Http\Controllers;

use App\Branch;
use App\BranchUser;

use App\ClientService;

use App\ClientServicePoints;

use App\ClientTransaction;

use App\ClientEWallet;

use App\ContactNumber;

use App\ContactAlternate;

use App\Group;

use App\Package;

use App\Remark;
use App\RoleUser;

use App\Service;
use App\ServiceBranchCost;
use App\ServiceProfileCost;

use App\Financing;

use App\Tasks;

use App\Updates;

use App\User;
use App\Order;
use App\OrderDetails;

use App\OnHandDocument;

use Auth, DB, Response, Validator;

use App\Http\Controllers\LogController;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClientController extends Controller
{

	public function manageClients() {
		$clients = DB::table('users as u')
			->select(DB::raw('u.id, u.first_name, u.last_name,
                (
                    (IFNULL(transactions.total_deposit, 0) + IFNULL(transactions.total_payment, 0) + IFNULL(transactions.total_discount,0))
                    -
                    (IFNULL(transactions.total_refund, 0) + IFNULL(totalCost.amount, 0))
                ) as balance,

                (
                    (IFNULL(transactions.total_deposit, 0) + IFNULL(transactions.total_payment, 0) + IFNULL(transactions.total_discount,0))
                    -
                    (IFNULL(transactions.total_refund, 0) + IFNULL(totalCompleteServiceCost.amount, 0))
                ) as collectables,

                p.latest_package, srv.latest_service as latest_service
                '))
            ->leftjoin(
            	DB::raw('
                    (
                        Select *
                        from role_user as r
                        where r.role_id = 2
                    ) as role
                '),
                'role.user_id', '=', 'u.id'
            )
            ->leftjoin(DB::raw('
                    (
                        Select
                            IFNULL(
                                sum(b.cost) + sum(b.charge) + sum(b.tip) + sum(b.com_client) + sum(b.com_agent),
                            0) as amount,
                            b.client_id

                        from
                            client_services as b

                        where
                            b.active = 1
                            and b.group_id is null

                        group by
                            b.client_id
                    ) as totalCost'),
                    'totalCost.client_id', '=', 'u.id')
            ->leftjoin(DB::raw('
                    (
                        Select
                            IFNULL(
                                sum(b.cost) + sum(b.charge) + sum(b.tip) + sum(b.com_client) + sum(b.com_agent)
                            ,0) as amount,
                            b.client_id

                        from
                            client_services as b

                        where
                            b.active = 1
                            and b.group_id is null
                            and (b.status = "complete" or b.status ="released")

                        group by
                            b.client_id
                    ) as totalCompleteServiceCost'),
                    'totalCompleteServiceCost.client_id', '=', 'u.id')
            ->leftjoin(DB::raw('
                    (
                        Select
                            SUM(IF(c.type = "Deposit", c.amount, 0)) as total_deposit,
                            SUM(IF(c.type = "Refund", c.amount, 0)) as total_refund,
                            SUM(IF(c.type = "Payment", c.amount, 0)) as total_payment,
                            SUM(IF(c.type = "Discount", c.amount, 0)) as total_discount,
                            c.client_id

                        from
                            client_transactions as c

                        where
                            c.group_id is null
                            and c.deleted_at is null

                        group by
                            c.client_id
                    ) as transactions'),
                    'transactions.client_id', '=', 'u.id')
            ->leftjoin(DB::raw('
                    (
                        Select date_format(max(x.dates),"%M %e, %Y, %l:%i %p") as latest_package, x.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as dates,
                            client_id, status
                            FROM packages
                            ORDER BY dates desc
                        ) as x
                        group by x.client_id) as p'),
                    'p.client_id', '=', 'u.id')
            ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%M %e, %Y") as latest_service, cs.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d") as servdates,
                            group_id, active,client_id
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.client_id) as srv'),
                    'srv.client_id', '=', 'u.id')
            ->where('role.role_id', '2')
            ->orderBy('u.first_name', 'asc')
            ->get();


		$response['status'] = 'Success';
		$response['data'] = [
		    'clients' => $clients
		];
		$response['code'] = 200;

		return Response::json($response);
	}


    public function manageClientsPaginate(Request $request, $perPage = 20) {
        $sort = $request->input('sort');
        $search = $request->input('search');
        $from = $request->input('from');

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

        $clients = DB::table('users as u')
            ->select(DB::raw('u.id, u.first_name, u.last_name, concat(u.first_name, " ", u.last_name) as full_name, u.risk,
                (
                    (IFNULL(transactions.total_deposit, 0) + IFNULL(transactions.total_payment, 0) + IFNULL(transactions.total_discount,0))
                    -
                    (IFNULL(transactions.total_refund, 0) + IFNULL(totalCost.amount, 0))
                ) as balance,

                (
                    (IFNULL(transactions.total_deposit, 0) + IFNULL(transactions.total_payment, 0) + IFNULL(transactions.total_discount,0))
                    -
                    (IFNULL(transactions.total_refund, 0) + IFNULL(totalCompleteServiceCost.amount, 0))
                ) as collectable,

                p.latest_package,
								csrv.updated_at,
                srv.latest_service,
                srv.latest_service2,
								log.log_date,
								log.created_at,
                p.latest_package2,
								transactions.transaction_date,
                IFNULL(csrv.active_service_count, 0) AS active_service_count')
            )
            ->leftjoin(
                DB::raw('
                    (
                        Select *
                        from role_user as r
                        where r.role_id = 2
                    ) as role
                '),
                'role.user_id', '=', 'u.id'
            )

						->leftjoin(
                DB::raw('
                    (
                        Select  l.created_at, l.client_id, date_format(max(l.created_at),"%Y%m%d%h%i%s") as log_date
                        from logs as l
                        where l.client_id is not null
												group by l.client_id
												order by log_date desc
                    ) as log

                '),
                'log.client_id', '=', 'u.id'
            )
            ->leftjoin(DB::raw('
                    (
                        Select
                            IFNULL(
                                sum(b.cost) + sum(b.charge) + sum(b.tip) + sum(b.com_client) + sum(b.com_agent),
                            0) as amount,
                            b.client_id

                        from
                            client_services as b

                        where
                            b.active = 1 and b.status != "cancelled"
                            and b.group_id is null

                        group by
                            b.client_id
                    ) as totalCost'),
                    'totalCost.client_id', '=', 'u.id')
            ->leftjoin(DB::raw('
                    (
                        Select
                            IFNULL(
                                sum(b.cost) + sum(b.charge) + sum(b.tip) + sum(b.com_client) + sum(b.com_agent)
                            ,0) as amount,
                            b.client_id

                        from
                            client_services as b

                        where
                            b.active = 1
                            and b.group_id is null
                            and (b.status = "complete" or b.status ="released")

                        group by
                            b.client_id
                    ) as totalCompleteServiceCost'),
                    'totalCompleteServiceCost.client_id', '=', 'u.id')
            ->leftjoin(DB::raw('
                    (
                        Select
                            SUM(IF(c.type = "Deposit", c.amount, 0)) as total_deposit,
                            SUM(IF(c.type = "Refund", c.amount, 0)) as total_refund,
                            SUM(IF(c.type = "Payment", c.amount, 0)) as total_payment,
                            SUM(IF(c.type = "Discount", c.amount, 0)) as total_discount,
                            c.client_id,
														c.updated_at as transaction_date

                        from
                            client_transactions as c

                        where
                            c.group_id is null
                            and c.deleted_at is null

                        group by
                            c.client_id
                    ) as transactions'),
                    'transactions.client_id', '=', 'u.id')
            ->leftjoin(DB::raw('
                    (
                        Select date_format(max(x.dates),"%M %e, %Y, %l:%i %p") as latest_package, date_format(max(x.dates),"%Y%m%d") as latest_package2, x.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as dates,
                            client_id, status
                            FROM packages
                            ORDER BY dates desc
                        ) as x
                        group by x.client_id) as p'),
                    'p.client_id', '=', 'u.id')
            ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%M %e, %Y") as latest_service, date_format(max(cs.servdates),"%Y%m%d") as latest_service2, cs.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d") as servdates,
                            group_id, active,client_id
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.client_id) as srv'),
                    'srv.client_id', '=', 'u.id')
            ->leftJoin(DB::raw('
                (
                    Select count(*) as active_service_count, client_id, updated_at

                    from
                        client_services as cs

                    where
                        cs.active = 1

                    group by
                        cs.client_id
                ) as csrv'),
                'csrv.client_id', '=', 'u.id')
            ->where('role.role_id', '2');

        if( $request->withActiveServiceOnly ) {
            $clients = $clients->where('active_service_count', '>', 0);
        }



        $clients = $clients
            ->when($sort != '', function ($q) use($sort) {
                $sort = explode('-' , $sort);

                if($sort[0] == 'name') {
                    $sort[0] = 'first_name';
                } else if($sort[0] == 'latest_service' || $sort[0] == 'latest_package') {
                    $sort[0] = $sort[0].'2';
                }

                return $q->orderBy($sort[0], $sort[1]);
            })

						->when($from !== '', function ($q) use($from) {
						   	return $q->orderBy('log.log_date', 'desc');
						})

            ->when($mode == 'fullname', function ($query) use($q1, $q2) {
                    return $query->where(function ($query2) use($q1, $q2) {
                        $query2->where('u.first_name', '=', $q1)->Where('u.last_name', '=', $q2);
                    })->orwhere(function ($query2) use($q1, $q2) {
                        $query2->where('u.last_name', '=', $q1)->Where('u.first_name', '=', $q2);
                    });
            })
            ->when($mode == 'id', function ($query) use($search) {
                return $query->where('u.id','LIKE','%'.$search.'%');
            })
            ->when($mode == 'name', function ($query) use($search) {
                return $query->where('first_name' ,'=', $search)->orwhere('last_name' ,'=', $search);
            })
            ->paginate($perPage);


        foreach ($clients as $c){
            $c->remarks = Remark::select('remark','u.first_name as created_by', 'remarks.created_at')->where("client_id", $c->id)->orderBy("remarks.id", "desc")->limit(3)
                ->leftjoin("users as u", "remarks.created_by", "u.id")
                ->get();
            //include wallet
            $c->wallet = $this->getClientEwallet($c->id);

            $total_balance =  $this->getClientTotalBalance($c->id);
            $col_balance =  $this->getClientTotalCollectables($c->id);
            User::where('id', $c->id)
                ->update(['balance' => $total_balance, 'collectable' => (($col_balance >= 0) ? 0 : $col_balance)]);
        }

				$response = $clients;

        $col = User::sum('collectable');
        $bal = User::sum('balance');
        // $clients['balance'] = $bal;

        $custom = collect(['collectables' => $col]);
        $response = $custom->merge($response);

        $custom = collect(['balance' => $bal]);
        $response = $custom->merge($response);

        return Response::json($response);
    }

    public function show($id){
        $client = User::with('nationalities')->find($id);

        if( $client ) {
            $client->contact = DB::table('contact_numbers')->where('user_id', $id)->where('is_primary',1)
                ->select(array('number'))->first();

            $client->birth_country = DB::table('countries')->where('id', $client->birth_country_id)
                ->select(array('name'))->first();

            $client->contact_numbers = DB::table('contact_numbers')->where('user_id', $id)
                ->select(array('number', 'is_primary', 'is_mobile'))->get();

            $client->contact_alternate = DB::table('contact_alternate')->where('user_id', $id)
                ->select(array('user_id', 'detail', 'type'))->get();

            $client->groups = DB::table('group_user')->where('user_id', $id)
                ->select(array('group_id'))->get();

            $updates = DB::table('updates')->where('client_id', $id)->get();

            $upd = Carbon::parse($client->created_at)->format('F j, Y');
            $client->update_address = $upd;
            $client->update_contact = $upd;
            $client->update_passport = $upd;
            $client->update_visa = $upd;

            foreach($updates as $up){
                switch($up->type){
                    case 'Address' : {
                        $client->update_address = Carbon::parse($up->updated_at)->format('F j, Y');
                        break;
                    }

                    case 'Contact' : {
                        $client->update_contact = Carbon::parse($up->updated_at)->format('F j, Y');
                        break;
                    }

                    case 'Passport' : {
                        $client->update_passport = Carbon::parse($up->updated_at)->format('F j, Y');
                        break;
                    }

                    case 'Visa' : {
                        $client->update_visa = Carbon::parse($up->updated_at)->format('F j, Y');
                        break;
                    }
                    default : {
                        break;
                    }
                }
            }


            $branch = DB::table('branch_user')->where('user_id', $id)
                ->select(array('branch_id'))->first();

            if($branch){
                $client->branch = DB::table('branches')->where('id', $branch->branch_id)
                ->select(array('id', 'name'))->first();
            }

            $client->total_points_earned = $this->getClientTotalPointsEarned($id);
            $client->total_complete_service_cost = $this->getClientTotalCompleteServiceCost($id);
            $client->total_cost = $this->getClientTotalCost($id);
            $client->total_payment = $this->getClientPayment($id);
            $client->total_deposit = $this->getClientDeposit($id);
            $client->total_ewallet = $this->getClientEwallet($id);

            $client->total_discount = $this->getClientTotalDiscount($id);
            $client->total_refund = $this->getClientTotalRefund($id);
            $client->total_balance = $this->getClientTotalBalance($id);
            $client->total_collectables = $this->getClientTotalCollectables($id);

            $client->remarks = $this->getClientsRemarks($id, true);

            $response['status'] = 'Success';
            $response['data'] = [
                'client' => $client
            ];
            $response['code'] = 200;
        } else {
            $response['status'] = 'Failed';
            $response['errors'] = 'No query results.';
            $response['code'] = 404;
        }

        return Response::json($response);
    }


	public function searchCom(Request $request){

			$keyword = $request->input('search');
			$branch_id = $request->input('branch_id');
			$branch_ids = DB::connection()->table('branch_user as b')->where('user_id',Auth::user()->id)->pluck('branch_id');


			if(is_numeric($keyword)){

				$results = DB::connection()
						->table('users as a')
						->select(DB::raw('
								a.id,a.first_name,a.last_name,a.created_at,srv.sdates,srv.sdates2, srv.checkyear, bu.branch_id'))
								->leftjoin(DB::raw('
										(
												Select date_format(max(cs.servdates),"%m/%d/%Y") as sdates, date_format(max(cs.servdates),"%Y%m%d") as sdates2, date_format(max(cs.servdates),"%Y") as checkyear ,cs.client_id
												from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as servdates,
														group_id, active,client_id
														FROM client_services
														ORDER BY servdates desc
												) as cs
												where cs.active = 1
												group by cs.client_id) as srv'),
										'srv.client_id', '=', 'a.id')
								->leftjoin(
										DB::raw('
												(
														Select *
														from role_user as r
														where r.role_id = 2
												) as role
										'),
										'role.user_id', '=', 'a.id'
								)
								->leftjoin(
										DB::raw('
												(
														Select *
														from branch_user
												) as bu
										'),
										'bu.user_id', '=', 'a.id'
								)
								->where('role.role_id', '2')
								->when($branch_id != '', function ($q) use($branch_ids){
										return $q->whereIn('bu.branch_id', $branch_ids);
								})
								->where('a.id',$keyword)
								->orderBy('sdates2','DESC')
								->limit(10)
								->get();


					$json = [];

	        foreach($results as $p){
	           $br = Branch::where('id',$p->branch_id)->first()->name;
	           if($p->checkyear >= 2016 || $p->checkyear != null){
	              $json[] = array(
	                  'id' => $p->id,
	                  'name' => $p->first_name." ".$p->last_name." -- [".$br."] -- ".$p->sdates."",
	                  'full_name' => $p->id,
										'branch_id' => $p->branch_id
	              );
	           }
	           if($p->checkyear == null){
	              $json[] = array(
	                  'id' => $p->id,
	                  'name' => $p->first_name." ".$p->last_name." -- [".$br."] -- No Service",
	                  'full_name' => $p->id,
										'branch_id' => $p->branch_id
	              );
	           }

				 }

		      $response['status'] = 'Success';
		      $response['data'] =  $json;
		      $response['code'] = 200;

		      return Response::json($response);

			}else{
					return $this->clientSearch($request);
	    }

	}

	public function clientSearch(Request $request) {
        $keyword = $request->input('search');
        $branch_id = $request->input('branch_id');
	    	$is_member_search = $request->input('is_member_search');

        $branch_ids = DB::connection()->table('branch_user as b')->where('user_id',Auth::user()->id)->pluck('branch_id');

        $cids = ContactNumber::where("number",'LIKE', '%' . $keyword .'%')->pluck('user_id');


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
            $results = DB::connection()
            ->table('users as a')
            ->select(DB::raw('
                a.id,a.first_name,a.last_name,a.created_at,srv.sdates,srv.sdates2, srv.checkyear, bu.branch_id'))
                ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%m/%d/%Y") as sdates, date_format(max(cs.servdates),"%Y%m%d") as sdates2 ,date_format(max(cs.servdates),"%Y") as checkyear ,cs.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as servdates,
                            group_id, active,client_id
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.client_id
                        order by cs.servdates) as srv'),
                    'srv.client_id', '=', 'a.id')
                ->leftjoin(
                    DB::raw('
                        (
                            Select *
                            from role_user as r
                            where r.role_id = 2
                        ) as role
                    '),
                    'role.user_id', '=', 'a.id'
                )
                ->leftjoin(
                    DB::raw('
                        (
                            Select *
                            from branch_user
                        ) as bu
                    '),
                    'bu.user_id', '=', 'a.id'
                )
                ->where('role.role_id', '2')
                ->when($branch_id != '', function ($q) use($branch_ids, $is_member_search, $branch_id){
										if($is_member_search > 0){
											  return $q->where('bu.branch_id', $is_member_search);
										}
                    return $q->whereIn('bu.branch_id', $branch_ids);
                })
                ->where(function ($query) use($q1, $q2, $keyword) {
                    $query->orwhere('a.id',$keyword)
                          ->orwhere(function ($query) use($q1,$q2) {
                                $query->where('first_name', '=', $q1)
                                      ->Where('last_name', '=', $q2);
                            })->orwhere(function ($query) use($q1,$q2) {
                                $query->where('last_name', '=', $q1)
                                      ->Where('first_name', '=', $q2);
                            });
                })
                ->orderBy('sdates2','DESC')
                ->limit(10)
                ->get();
        }
        else{

            $results = DB::connection()
            ->table('users as a')
            ->select(DB::raw('
                a.id,a.first_name,a.last_name,a.created_at,srv.sdates,srv.sdates2, srv.checkyear, bu.branch_id'))
                ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%m/%d/%Y") as sdates, date_format(max(cs.servdates),"%Y%m%d") as sdates2, date_format(max(cs.servdates),"%Y") as checkyear ,cs.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as servdates,
                            group_id, active,client_id
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.client_id) as srv'),
                    'srv.client_id', '=', 'a.id')
                ->leftjoin(
                    DB::raw('
                        (
                            Select *
                            from role_user as r
                            where r.role_id = 2
                        ) as role
                    '),
                    'role.user_id', '=', 'a.id'
                )
                ->leftjoin(
                    DB::raw('
                        (
                            Select *
                            from branch_user
                        ) as bu
                    '),
                    'bu.user_id', '=', 'a.id'
                )
                ->where('role.role_id', '2')
                ->where(function ($query) use($cids, $keyword) {
                        $query->orwhereIn('a.id',$cids)
                              ->orwhere('a.id',$keyword)
                              ->orwhere('first_name','=',$keyword)
                              ->orwhere('last_name','=',$keyword);
                    })
                ->when($branch_id != '', function ($q) use($branch_ids, $is_member_search, $branch_id){
						if($is_member_search > 0){
								return $q->where('bu.branch_id', $is_member_search);
						}
						return $q->whereIn('bu.branch_id', $branch_ids);
                })
                ->orderBy('sdates2','DESC')
                ->limit(10)
                ->get();

            if($results->count() == 0){
                preg_match_all('!\d+!', $keyword, $matches);
                $keyword = implode("", $matches[0]);
                $keyword = ltrim($keyword,"0");
                $keyword = ltrim($keyword,'+');
                $keyword = ltrim($keyword,'63');
                $cids = [];
                if($keyword != ''){
                    $cids = ContactNumber::where("number",'LIKE', '%' . $keyword .'%')->pluck('user_id');
                }

                $results = DB::connection()
                    ->table('users as a')
                    ->select(DB::raw('
                        a.id,a.first_name,a.last_name,a.created_at,srv.sdates,srv.sdates2, srv.checkyear, bu.branch_id'))
                        ->leftjoin(DB::raw('
                            (
                                Select date_format(max(cs.servdates),"%m/%d/%Y") as sdates, date_format(max(cs.servdates),"%Y%m%d") as sdates2, date_format(max(cs.servdates),"%Y") as checkyear ,cs.client_id
                                from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as servdates,
                                    group_id, active,client_id
                                    FROM client_services
                                    ORDER BY servdates desc
                                ) as cs
                                where cs.active = 1
                                group by cs.client_id) as srv'),
                            'srv.client_id', '=', 'a.id')
                        ->leftjoin(
                            DB::raw('
                                (
                                    Select *
                                    from role_user as r
                                    where r.role_id = 2
                                ) as role
                            '),
                            'role.user_id', '=', 'a.id'
                        )
                        ->leftjoin(
                            DB::raw('
                                (
                                    Select *
                                    from branch_user
                                ) as bu
                            '),
                            'bu.user_id', '=', 'a.id'
                        )
                        ->where('role.role_id', '2')
                        ->when($branch_id != '', function ($q) use($branch_ids){
                            return $q->whereIn('bu.branch_id', $branch_ids);
                        })
                        ->where(function ($query) use($cids, $keyword) {
                            $query->orwhereIn('a.id',$cids)
                                  ->orwhere('a.id',$keyword)
                                  ->orwhere('first_name','=',$keyword)
                                  ->orwhere('last_name','=',$keyword);
                        })
                        ->orderBy('sdates2','DESC')
                        ->limit(10)
                        ->get();
            }
        }

        $json = [];

        foreach($results as $p){
           $br = Branch::where('id',$p->branch_id)->first()->name;
           if($p->checkyear >= 2016 || $p->checkyear != null){
              $json[] = array(
                  'id' => $p->id,
                  'name' => $p->first_name." ".$p->last_name." -- [".$br."] -- ".$p->sdates."",
                  'full_name' => $p->first_name." ".$p->last_name,
									'branch_id' => $p->branch_id
              );
           }
           if($p->checkyear == null){
              $json[] = array(
                  'id' => $p->id,
                  'name' => $p->first_name." ".$p->last_name." -- [".$br."] -- No Service",
                  'full_name' => $p->first_name." ".$p->last_name,
									'branch_id' => $p->branch_id
              );
           }

			 }

	      $response['status'] = 'Success';
	      $response['data'] =  $json;
	      $response['code'] = 200;

        return Response::json($response);
    }

    public function getAllUsers() {
        $users = DB::table('role_user as rs')
                    ->leftJoin('users', 'rs.user_id', '=', 'users.id')
                    ->leftJoin('roles', 'rs.role_id', '=', 'roles.id')
                    ->where('rs.role_id', '!=', 2)
                    ->select('user_id as id', 'first_name', 'last_name')
                    ->groupBy('user_id')
                    ->get();

        $response['status'] = 'Success';
        $response['data'] =  $users;
        $response['code'] = 200;

        return Response::json($response);
    }


    public function getContactType() {
        $type = DB::select(DB::raw('SHOW COLUMNS FROM contact_alternate WHERE Field = "type"'))[0]->Type;
        preg_match('/^enum\((.*)\)$/', $type, $matches);
        $values = array();
        foreach(explode(',', $matches[1]) as $value){
            $values[] = trim($value, "'");
        }
        return $values;

        $response['status'] = 'Success';
        $response['data'] =  $values;
        $response['code'] = 200;

        return Response::json($response);
    }


    public function store(Request $request) {
		$validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'middle_name' => 'nullable',
            'last_name' => 'required',
            'birth_date' => 'required|date',
            'gender' => 'required',
            'civil_status' => 'required',
            'height' => 'nullable',
            'weight' => 'nullable',
            'nationalities' => 'required|array',
            'birth_country' => 'required',
            'address' => 'required',
            // 'contact_numbers' => 'required|array',
            'contact_numbers.*.number' => 'nullable|max:13',
            'contact_numbers.*.is_primary' => 'nullable',
            'contact_numbers.*.is_mobile' => 'nullable',
            'branches' => 'required|array',
            'email' => 'nullable|email|unique:users,email',
            'passport' => 'nullable',
            'passport_expiration_date' => 'nullable|date',
            'groups' => 'nullable|array',
            'visa_type' => 'nullable',
            'arrival_date' => 'nullable|date',
            'first_expiration_date' => 'nullable|date',
            'extended_expiration_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'icard_issue_date' => 'nullable|date',
            'icard_expiration_date' => 'nullable|date',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $ce_count = 0;

            foreach($request->contact_alternate as $key=>$contactNumber) {
                if(strlen($contactNumber['detail']) !== 0 && $contactNumber['detail'] !== null) {
                    // if(strlen($contactNumber['number']) === 13) {
                    //     $number = substr($contactNumber['number'], 3);
                    // } else if(strlen($contactNumber['number']) === 12) {
                    //     $number = substr($contactNumber['number'], 2);
                    // } else {
                    //     $number = substr($contactNumber['number'], 1);
                    // }
                    $number = $contactNumber['detail'];
                    $type = $contactNumber['type'];

                    if($type === '+63') {
                        $contact = ContactNumber::where('number','LIKE','%'.$number.'%')->count();
                    } else {
                        $contact = ContactAlternate::where('detail','LIKE','%'.$number.'%')->count();
                    }

                    if($contact > 0) {
                        // $contact_error['contact_numbers.'.$key.'.number'] = ['The contact number has already been taken.'];
                        $contact_error['contact_alternate.'.$key.'.detail'] = ['The contact number has already been taken.'];
                        $ce_count++;
                    }
                }
            }

            if($ce_count > 0) {
                $response['status'] = 'Failed';
                $response['errors'] = $contact_error;
                $response['code'] = 422;
            } else {
                $client = new User;
                $client->first_name = $request->first_name;
                $client->middle_name = ($request->middle_name) ? $request->middle_name : null;
                $client->last_name = $request->last_name;
                $client->birth_date = $request->birth_date;
                $client->gender = $request->gender;
                $client->civil_status = $request->civil_status;
                $client->height = ($request->height) ? $request->height : null;
                $client->weight = ($request->weight) ? $request->weight : null;
                $client->birth_country_id = $request->birth_country;
                $client->address = $request->address;
                $client->email = ($request->email) ? $request->email : null;
                $client->passport = ($request->passport) ? $request->passport : null;
                $client->passport_exp_date = ($request->passport_expiration_date) ? $request->passport_expiration_date : null;
                if( $request->visa_type == '9A' ) {
                	$client->visa_type = $request->visa_type;
                	$client->arrival_date = ($request->arrival_date) ? $request->arrival_date : null;
                	$client->first_expiration_date = ($request->first_expiration_date) ? $request->first_expiration_date : null;
                	$client->extended_expiration_date = ($request->extended_expiration_date) ? $request->extended_expiration_date : null;
                } elseif( $request->visa_type == '9G' || $request->visa_type == 'TRV' ) {
                	$client->visa_type = $request->visa_type;
                	$client->expiration_date = ($request->expiration_date) ? $request->expiration_date : null;
                	$client->icard_issue_date = ($request->icard_issue_date) ? $request->icard_issue_date : null;
                	$client->icard_expiration_date = ($request->icard_expiration_date) ? $request->icard_expiration_date : null;
                } elseif( $request->visa_type == 'CWV' ) {
                	$client->visa_type = $request->visa_type;
                	$client->expiration_date = ($request->expiration_date) ? $request->expiration_date : null;
                }
                $client->save();

                //save action logs
                $detail = "Created new client -> ".$client->first_name.' '.$client->last_name.'.';
                $detail_cn = "Created new client -> ".$client->first_name.' '.$client->last_name.'.';
                $log_data = array(
                    'client_id' => $client->id,
                    'group_id' => null,
                    'log_type' => 'Action',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> 0,
                );
                LogController::save($log_data);

                foreach($request->nationalities as $nationality) {
                	$client->nationalities()->attach($nationality);
                }

                // foreach($request->contact_numbers as $contactNumber) {
                //     if(strlen($contactNumber['number']) !== 0 && $contactNumber['number'] !== null) {
                //         ContactNumber::create([
                //             'user_id' => $client->id,
                //             'number' => '+63'.$contactNumber['number'],
                //             'is_primary' => $contactNumber['is_primary'],
                //             'is_mobile' => $contactNumber['is_mobile']
                //         ]);

                //         if( $contactNumber['is_primary'] ) {
                //             $client->update([
                //                 'password' => bcrypt('+63'.$contactNumber['number'])
                //             ]);
                //         }
                //     }
                // }

                $primaryContact = 0;
                foreach($request->contact_alternate as $key => $contactNumber) {
                    if(strlen($contactNumber['detail']) !== 0 && $contactNumber['detail'] !== null) {

                        if($contactNumber['type'] === '+63') {
                            $primaryContact++;

                            $cn = new ContactNumber;
                            $cn->user_id = $client->id;
                            $cn->number = $contactNumber['type'].$contactNumber['detail'];
                            $cn->is_primary = ($primaryContact === 1) ? 1 : 0;
                            $cn->is_mobile = 1;
                            $cn->save();
                        } else {
                            $ca = new ContactAlternate;
                            $ca->user_id = $client->id;
                            $ca->detail = $contactNumber['detail'];
                            $ca->type = $contactNumber['type'];
                            $ca->save();
                        }

                        if($primaryContact === 1) {
                            $client->update([
                                'password' => bcrypt($contactNumber['type'].$contactNumber['detail'])
                            ]);
                        }

                        // ContactNumber::create([
                        //     'user_id' => $client->id,
                        //     'number' => '+63'.$contactNumber['number'],
                        //     'is_primary' => $contactNumber['is_primary'],
                        //     'is_mobile' => $contactNumber['is_mobile']
                        // ]);

                        // if( $contactNumber['is_primary'] ) {
                        //     $client->update([
                        //         'password' => bcrypt('+63'.$contactNumber['number'])
                        //     ]);
                        // }
                    }
                }

                // foreach($request->contact_alternate as $contactAlternate) {
                //     $ca = new ContactAlternate;
                //     $ca->user_id = $client->id;
                //     $ca->detail = $contactAlternate['detail'];
                //     $ca->type = $contactAlternate['type'];
                //     $ca->save();
                // }

                $client->branches()->detach();
                foreach($request->branches as $branch) {
                	$client->branches()->attach($branch);
                }

                if( $request->groups ) {
                	foreach($request->groups as $group) {
                		$client->groups()->attach($group);
                	}
                }

                $client->roles()->attach(2);
                $response['status'] = 'Success';
        	    $response['code'] = 200;
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
            $client = User::find($id);

            if( $client ) {
                $client->update(['risk' => $request->risk]);
                $client->save();

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
            'first_name' => 'required',
            'middle_name' => 'nullable',
            'last_name' => 'required',
            'birth_date' => 'required|date',
            'gender' => 'required',
            'civil_status' => 'required',
            'height' => 'nullable',
            'weight' => 'nullable',
            'nationalities' => 'required|array',
            'birth_country' => 'required',
            'address' => 'required',
            'contact_numbers' => 'required|array',
            'contact_numbers.*.number' => 'nullable|max:13',
            'contact_numbers.*.is_primary' => 'nullable',
            'contact_numbers.*.is_mobile' => 'nullable',
            'branches' => 'required|array',
            'email' => 'nullable|email|unique:users,email,'.$id,
            'passport' => 'nullable',
            'passport_expiration_date' => 'nullable|date',
            'groups' => 'nullable|array',
            'visa_type' => 'nullable',
            'arrival_date' => 'nullable|date',
            'first_expiration_date' => 'nullable|date',
            'extended_expiration_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'icard_issue_date' => 'nullable|date',
            'icard_expiration_date' => 'nullable|date',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $ce_count = 0;

            // foreach($request->contact_numbers as $key=>$contactNumber) {
            //     if(strlen($contactNumber['number']) !== 0 && $contactNumber['number'] !== null) {
            //         if(strlen($contactNumber['number']) === 13) {
            //             $number = substr($contactNumber['number'], 3);
            //         } else if(strlen($contactNumber['number']) === 12) {
            //             $number = substr($contactNumber['number'], 2);
            //         } else {
            //             $number = substr($contactNumber['number'], 1);
            //         }

            //         $contact = ContactNumber::where('number','LIKE','%'.$number.'%')->get();
            //         $client_contact = ContactNumber::where('user_id', $id)->where('number','LIKE','%'.$number.'%')->count();


            //         if($client_contact === 0) {
            //             if($contact) {
            //                 $num_duplicate = 0;
            //                 foreach($contact as $con) {
            //                     if(strval ($con['user_id']) !== strval ($id)) {
            //                       $num_duplicate++;
            //                     }
            //                 }

            //                 if($num_duplicate > 0) {
            //                     $contact_error['contact_numbers.'.$key.'.number'] = ['The contact number has already been taken.'];
            //                     $ce_count++;
            //                 }

            //             }
            //         }
            //     }
            // }

            foreach($request->contact_alternate as $key=>$contactNumber) {
                if(strlen($contactNumber['detail']) !== 0 && $contactNumber['detail'] !== null) {
                    // if(strlen($contactNumber['number']) === 13) {
                    //     $number = substr($contactNumber['number'], 3);
                    // } else if(strlen($contactNumber['number']) === 12) {
                    //     $number = substr($contactNumber['number'], 2);
                    // } else {
                    //     $number = substr($contactNumber['number'], 1);
                    // }
                    $number = $contactNumber['detail'];
                    $type = $contactNumber['type'];

                    if($type === '+63') {
                        $contact = ContactNumber::where('number','LIKE','%'.$number.'%')->where('user_id', '!=', $id)->count();
                    } else {
                        $contact = ContactAlternate::where('detail','LIKE','%'.$number.'%')->where('user_id', '!=', $id)->count();
                    }

                    if($contact > 0) {
                        // $contact_error['contact_numbers.'.$key.'.number'] = ['The contact number has already been taken.'];
                        $contact_error['contact_alternate.'.$key.'.detail'] = ['The contact number has already been taken.'];
                        $ce_count++;
                    }
                }
            }

            if($ce_count > 0) {
                $response['status'] = 'Failed';
                $response['errors'] = $contact_error;
                $response['code'] = 422;
            } else {
                $client = User::find($id);

                if( $client ) {
                    $client->first_name = $request->first_name;
                    $client->middle_name = ($request->middle_name) ? $request->middle_name : null;
                    $client->last_name = $request->last_name;
                    $client->birth_date = $request->birth_date;
                    $client->gender = $request->gender;
                    $client->civil_status = $request->civil_status;
                    $client->height = ($request->height) ? $request->height : null;
                    $client->weight = ($request->weight) ? $request->weight : null;
                    $client->birth_country_id = $request->birth_country;
                    $client->address = $request->address;
                    $client->email = ($request->email) ? $request->email : null;
                    $client->passport = ($request->passport) ? $request->passport : null;
                    $client->passport_exp_date = ($request->passport_expiration_date) ? $request->passport_expiration_date : null;
                    if( $request->visa_type == '9A' ) {
                        $client->visa_type = $request->visa_type;

                        $client->arrival_date = ($request->arrival_date) ? $request->arrival_date : null;
                        $client->first_expiration_date = ($request->first_expiration_date) ? $request->first_expiration_date : null;
                        $client->extended_expiration_date = ($request->extended_expiration_date) ? $request->extended_expiration_date : null;

                        $client->expiration_date = null;
                        $client->icard_issue_date = null;
                        $client->icard_expiration_date = null;
                    } elseif( $request->visa_type == '9G' || $request->visa_type == 'TRV' ) {
                        $client->visa_type = $request->visa_type;

                        $client->expiration_date = ($request->expiration_date) ? $request->expiration_date : null;
                        $client->icard_issue_date = ($request->icard_issue_date) ? $request->icard_issue_date : null;
                        $client->icard_expiration_date = ($request->icard_expiration_date) ? $request->icard_expiration_date : null;

                        $client->arrival_date = null;
                        $client->first_expiration_date = null;
                        $client->extended_expiration_date = null;
                    } elseif( $request->visa_type == 'CWV' ) {
                        $client->visa_type = $request->visa_type;

                        $client->expiration_date = ($request->expiration_date) ? $request->expiration_date : null;

                        $client->arrival_date = null;
                        $client->first_expiration_date = null;
                        $client->extended_expiration_date = null;
                        $client->icard_issue_date = null;
                        $client->icard_expiration_date = null;
                    } else {
                        $client->visa_type = null;
                    }

                    if($client->isDirty()){
                        //getOriginal() -> get original values of model
                        //getDirty -> get all fields updated with value
                        $changes = $client->getDirty();
                        $detail = "Updated client account. ";
                        foreach ($changes as $key => $value) {
                            $old = $client->getOriginal($key);

                            $field = str_replace("_", " ", $key);
                            $field = str_replace(" exp ", " expiration ", $field);
                            if( $old ) {
                                $detail .= "Change ".$field." from ".$old." to ".$value.". ";
                            } else {
                                $detail .= "Change ".$field." to ".$value.". ";
                            }


                            if($key == 'address'){
                                $upd = Updates::updateOrCreate(
                                            ['client_id' => $id, 'type' => 'Address'],
                                            ['updated_at' => Carbon::now()]
                                        );
                            }

                            if($key == 'passport' || $key == 'passport_exp_date' ){
                                $upd = Updates::updateOrCreate(
                                            ['client_id' => $id, 'type' => 'Passport'],
                                            ['updated_at' => Carbon::now()]
                                        );
                            }

                            if($key == 'visa_type' || $key == 'arrival_date' || $key == 'first_expiration_date' || $key == 'extended_expiration_date' || $key == 'expiration_date' || $key == 'icard_issue_date' || $key == 'icard_expiration_date'){
                                $upd = Updates::updateOrCreate(
                                            ['client_id' => $id, 'type' => 'Visa'],
                                            ['updated_at' => Carbon::now()]
                                        );
                            }
                        }
                        // save action logs
                        $detail_cn = $detail;
                        $log_data = array(
                            'client_id' => $client->id,
                            'group_id' => null,
                            'log_type' => 'Action',
                            'detail'=> $detail,
                            'detail_cn'=> $detail_cn,
                            'amount'=> 0,
                        );
                         LogController::save($log_data);
                    }

                    $client->save();

                    $client->nationalities()->detach();
                    foreach($request->nationalities as $nationality) {
                        $client->nationalities()->attach($nationality);
                    }


                    $rcn = ContactNumber::where('user_id', $client->id)->delete();

                    $rca = ContactAlternate::where('user_id', $client->id)->delete();

                    $primaryContact = 0;
                    foreach($request->contact_alternate as $key => $contactNumber) {
                        if(strlen($contactNumber['detail']) !== 0 && $contactNumber['detail'] !== null) {

                            if($contactNumber['type'] === '+63') {
                                $primaryContact++;

                                $cn = new ContactNumber;
                                $cn->user_id = $client->id;
                                $cn->number = $contactNumber['type'].$contactNumber['detail'];
                                $cn->is_primary = ($primaryContact === 1) ? 1 : 0;
                                $cn->is_mobile = 1;
                                $cn->save();
                            } else {
                                $ca = new ContactAlternate;
                                $ca->user_id = $client->id;
                                $ca->detail = $contactNumber['detail'];
                                $ca->type = $contactNumber['type'];
                                $ca->save();
                            }

                            if($primaryContact === 1) {
                                $client->update([
                                    'password' => bcrypt($contactNumber['type'].$contactNumber['detail'])
                                ]);
                            }

                            // ContactNumber::create([
                            //     'user_id' => $client->id,
                            //     'number' => '+63'.$contactNumber['number'],
                            //     'is_primary' => $contactNumber['is_primary'],
                            //     'is_mobile' => $contactNumber['is_mobile']
                            // ]);

                            // if( $contactNumber['is_primary'] ) {
                            //     $client->update([
                            //         'password' => bcrypt('+63'.$contactNumber['number'])
                            //     ]);
                            // }
                        }
                    }

                    // $client->contactNumbers()->delete();
                    // foreach($request->contact_numbers as $contactNumber) {
                    //     $contactNum = str_replace('+63', '', $contactNumber['number']);

                    //     if(strlen($contactNum) !== 0 && $contactNum !== null) {
                    //         ContactNumber::create([
                    //             'user_id' => $client->id,
                    //             'number' => '+63'.$contactNum,
                    //             'is_primary' => json_decode($contactNumber['is_primary'], true),
                    //             'is_mobile' => $contactNumber['is_mobile']
                    //         ]);

                    //         if( $contactNumber['is_primary'] ) {
                    //             $old = ContactNumber::where('user_id',$client->id)->where('is_primary',1)->first();
                    //             if($old){
                    //                 if($old->number != '+63'.$contactNum){
                    //                     $upd = Updates::updateOrCreate(
                    //                         ['client_id' => $client->id, 'type' => 'Contact'],
                    //                         ['updated_at' => Carbon::now()]
                    //                     );
                    //                 }
                    //             }
                    //             $client->update([
                    //                 'password' => bcrypt('+63'.$contactNum)
                    //             ]);
                    //         }
                    //     }
                    // }

                    // DB::table('contact_alternate')->where('user_id', $client->id)->delete();
                    // foreach($request->contact_alternate as $contactAlternate) {
                    //     $ca = new ContactAlternate;
                    //     $ca->user_id = $client->id;
                    //     $ca->detail = $contactAlternate['detail'];
                    //     $ca->type = $contactAlternate['type'];
                    //     $ca->save();
                    // }


                    $client->branches()->detach();
                    foreach($request->branches as $branch) {
                        $client->branches()->attach($branch);
                    }

                    if( $request->groups ) {
                        $client->groups()->detach();
                        foreach($request->groups as $group) {
                            $client->groups()->attach($group);
                        }
                    }

                    $response['status'] = 'Success';
                    $response['code'] = 200;
                } else {
                    $response['status'] = 'Failed';
                    $response['errors'] = 'No query results.';
                    $response['code'] = 404;
                }
            }
        }

        return Response::json($response);
	}

    public function getClientServices($id, $tracking = 0) {
        if($tracking == 0 && strlen($tracking) == 1){

            $services = DB::table('client_services as cs')
                ->select(DB::raw('cs.*,g.name as group_name, ct.amount as discount_amount,ct.reason as discount_reason, cp.reason as payment_reason, s.parent_id, s.form_id, u.arrival_date, u.first_expiration_date, u.extended_expiration_date, u.expiration_date, u.icard_issue_date, u.icard_expiration_date'))
                ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','cs.group_id')
                // ->leftjoin(DB::raw('(select * from client_transactions) as ct'),'ct.client_service_id','=','cs.id')
                ->leftJoin(DB::raw('(select * from client_transactions) as ct'), function($join){
                    $join->on('ct.client_service_id', '=', 'cs.id');
                    $join->where('ct.type','=','Discount');
                })
                ->leftJoin(DB::raw('(select * from client_transactions) as cp'), function($join){
                    $join->on('cp.client_service_id', '=', 'cs.id');
                    $join->where('cp.type','=','Payment');
                })
                ->leftjoin(DB::raw('(select * from services) as s'),'s.id','=','cs.service_id')
                ->leftjoin(DB::raw('(select * from users) as u'),'u.id','=','cs.client_id')
                ->where('cs.client_id',$id)
                ->orderBy('cs.id', 'desc')
                ->get();
        }
        else{
            $services = DB::table('client_services as cs')
                ->select(DB::raw('cs.*,g.name as group_name, ct.amount as discount_amount,ct.reason as discount_reason,cp.reason as payment_reason, s.parent_id, s.form_id, u.arrival_date, u.first_expiration_date, u.extended_expiration_date, u.expiration_date, u.icard_issue_date, u.icard_expiration_date'))
                ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','cs.group_id')
                // ->leftjoin(DB::raw('(select * from client_transactions) as ct'),'ct.client_service_id','=','cs.id')
                ->leftJoin(DB::raw('(select * from client_transactions) as ct'), function($join){
                    $join->on('ct.client_service_id', '=', 'cs.id');
                    $join->where('ct.type','=','Discount');
                })
                ->leftJoin(DB::raw('(select * from client_transactions) as cp'), function($join){
                    $join->on('cp.client_service_id', '=', 'cs.id');
                    $join->where('cp.type','=','Payment');
                })
                ->leftjoin(DB::raw('(select * from services) as s'),'s.id','=','cs.service_id')
                ->leftjoin(DB::raw('(select * from users) as u'),'u.id','=','cs.client_id')
                ->where('cs.client_id',$id)->where('cs.tracking',$tracking)
                ->orderBy('cs.id', 'desc')
                ->get();
        }

        $response['status'] = 'Success';
        $response['data'] = $services;
        $response['code'] = 200;

        return Response::json($response);
    }

    public function getClientPackages($id) {
        $onprocess = DB::table('packages as p')
                    ->select(DB::raw('p.*,g.name as group_name, srv.latest_service'))
                    ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                    ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%Y%m%d%H%i%s") as latest_service, cs.client_id, cs.tracking
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as servdates,
                            group_id, active,client_id,tracking
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.tracking) as srv'),
                    'srv.tracking', '=', 'p.tracking')
                    ->where('p.client_id', $id)
                    ->where('p.status','on process')
                    ->orderBy('srv.latest_service', 'desc')
                    ->get();

        $pending = DB::table('packages as p')
                    ->select(DB::raw('p.*,g.name as group_name, srv.latest_service'))
                    ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                    ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%Y%m%d%H%i%s") as latest_service, cs.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as servdates,
                            group_id, active,client_id
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.client_id) as srv'),
                    'srv.client_id', '=', 'p.client_id')
                    ->where('p.client_id', $id)
                    ->where('status','pending')
                    ->orderBy('srv.latest_service', 'desc')
                    ->get();

        $complete = DB::table('packages as p')
                    ->select(DB::raw('p.*,g.name as group_name, srv.latest_service'))
                    ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                    ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%Y%m%d%H%i%s") as latest_service, cs.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as servdates,
                            group_id, active,client_id
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.client_id) as srv'),
                    'srv.client_id', '=', 'p.client_id')
                    ->where('p.client_id', $id)
                    ->where('status','complete')
                    ->orderBy('srv.latest_service', 'desc')
                    ->get();

        $released = DB::table('packages as p')
                    ->select(DB::raw('p.*,g.name as group_name, srv.latest_service'))
                    ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                    ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%Y%m%d%H%i%s") as latest_service, cs.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as servdates,
                            group_id, active,client_id
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.client_id) as srv'),
                    'srv.client_id', '=', 'p.client_id')
                    ->where('p.client_id', $id)
                    ->where('status','released')
                    ->orderBy('srv.latest_service', 'desc')
                    ->get();

        $cancelled = DB::table('packages as p')
                    ->select(DB::raw('p.*,g.name as group_name, srv.latest_service'))
                    ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                    ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%Y%m%d%H%i%s") as latest_service, cs.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as servdates,
                            group_id, active,client_id
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.client_id) as srv'),
                    'srv.client_id', '=', 'p.client_id')
                    ->where('p.client_id', $id)
                    ->where('status','cancelled')
                    ->orderBy('srv.latest_service', 'desc')
                    ->get();

        $empty = DB::table('packages as p')
                    ->select(DB::raw('p.*,g.name as group_name, srv.latest_service'))
                    ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                    ->leftjoin(DB::raw('
                    (
                        Select date_format(max(cs.servdates),"%Y%m%d%H%i%s") as latest_service, cs.client_id
                        from( SELECT STR_TO_DATE(created_at, "%Y-%m-%d %H:%i:%s") as servdates,
                            group_id, active,client_id
                            FROM client_services
                            ORDER BY servdates desc
                        ) as cs
                        where cs.active = 1
                        group by cs.client_id) as srv'),
                    'srv.client_id', '=', 'p.client_id')
                    ->where('p.client_id', $id)
                    ->where('status',null)
                    ->orderBy('srv.latest_service', 'desc')
                    ->get();


        $p1 = collect($onprocess);
        $p2 = collect($pending);
        $p3 = collect($complete);
        $p4 = collect($released);
        $p5 = collect($cancelled);
        $p6 = collect($empty);

        $packs = (((($p1->merge($p2))->merge($p3))->merge($p4))->merge($p5))->merge($p6);

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

    public function getClientGroups($id) {
        $group_ids = DB::table('group_user')->where('user_id',$id)->pluck('group_id');

        $groups = Group::with('branches', 'contactNumbers')
            ->select(array('id', 'name', 'leader_id', 'tracking', 'address'))
            ->whereIn('id',$group_ids)->get();

        foreach($groups as $g){
            $g->leader = DB::table('users')->where('id', $g->leader_id)
                ->select(array('first_name', 'last_name'))->first();
            $g->client_last_active = DB::table('client_services')->where('group_id', $g->id)->where('client_id', $id)
                ->select(array('created_at'))->orderBy('created_at','Desc')->first();
            $g->group_latest_active = DB::table('client_services')->where('group_id', $g->id)
                ->select(array('created_at'))->orderBy('created_at','Desc')->first();
        }

        $response['status'] = 'Success';
        $response['data'] = $groups;
        $response['code'] = 200;

        return Response::json($response);
    }

    public function addClientService(Request $request) {
        $validator = Validator::make($request->all(), [
            'tracking' => 'required',
            'client_id' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $c = User::findOrFail($request->client_id);
            $level = $c->service_profile_id;
            $service_ids = [];
            for($i=0; $i<count($request->services); $i++) {
                $service = Service::findorfail($request->services[$i]);

                if($request->note != '') {
                    $remarks = $request->note.' - '. Auth::user()->first_name.' <small>('.date('Y-m-d H:i:s').')</small>';
                } else {
                    $remarks = '';
                }

              $scharge = $service->charge;
              $scost = $service->cost;
              $stip = $service->tip;

              if($request->branch_id > 1){
                  $bcost = ServiceBranchCost::where('branch_id',$request->branch_id)->where('service_id',$service->id)->first();
                  $scost = $bcost->cost;
                  $stip = $bcost->tip;
                  $scharge = $bcost->charge;
              }
              else{
                  $scost = $service->cost;
                  $stip = $service->tip;
                  $scharge = $service->charge;
              }

              //has profile id
              if($level > 0 && $level != null){
                  $newcost = ServiceProfileCost::where('profile_id',$level)
                                ->where('branch_id',$request->branch_id)
                                ->where('service_id',$service->id)
                                ->first();
                  if($newcost){
                      $scharge = $newcost->charge;
                      $scost = $newcost->cost;
                      $stip = $newcost->tip;
                  }
              }

              $scharge = ($scharge > 0 ? $scharge : $service->charge);
              $scost = ($scost > 0 ? $scost : $service->cost);
              $stip = ($stip > 0 ? $stip : $service->tip);

                $cs = ClientService::create([
                    'client_id' => $request->client_id,
                    'service_id' => $request->services[$i],
                    'detail' => $service->detail,
                    'cost' => $scost,
                    'charge' => $scharge,
                    'tip' => $stip,
                    'tracking' => $request->tracking,
                    'remarks' => $remarks,
                    'active' => 1,
                ]);

                $service_ids[] = $cs->id;

                // DB::table('client_service_points')->insert(
                //     array(
                //         'client_service_id' => $cs->id,
                //         'points' => 1
                //     )
                // );
                ClientServicePoints::create([
                    'client_service_id' => $cs->id,
                    'points' => 1
                ]);

                $this->updatePackageStatus($request->tracking); //update package status

                // save transaction logs
                $totalAmount = $cs->cost + $cs->tip + $cs->charge;
                $detail_cn = ($service->detail_cn!='' ? $service->detail_cn : $service->detail);

                $detail = 'Added service "'.$service->detail.'", Service status is pending.';
                $detail_cn = '已添加服务 "'.$detail_cn.'" 服务状态为 待办。';
                $log_data = array(
                    'client_service_id' => $cs->id,
                    'client_id' => $cs->client_id,
                    'group_id' => $cs->group_id,
                    'log_type' => 'Transaction',
                    'log_group' => 'service',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> 0,
                );
                 LogController::save($log_data);
            }

            $response['status'] = 'Success';
            $response['service_ids'] = $service_ids;
            $response['code'] = 200;
        }

        return Response::json($response);
    }

    public function editClientService(Request $request) {
        $validator = Validator::make($request->all(), [
            'cs_id' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
                $cs = ClientService::findorfail($request->cs_id);
                $service = Service::where('id',$cs->service_id)->first();

                $oldstatus = $cs->status;
                $oldactive = $cs->active;

                $detail_cn = $service->detail;
                if($service){ // get chinese translation of service detail
                    $detail_cn = ($service->detail_cn!='' ? $service->detail_cn : $service->detail);
                }

                $translog = '';
                $translog_cn = '';
                $transtat = '';
                $transtat_cn = '';
                $newVal = 0;
                $oldVal = 0;

                // check changes active/inactive
                if ($cs->active != $request->active) {
                    if($request->active == 1) { // Enabled
                        $transtat = 'Service was enabled.';
                        $transtat_cn = '服务被标记为已启用.';
                        $translog = 'Total service charge from Php0 to ' . 'Php'. ($cs->cost + $cs->charge + $cs->tip);
                        $translog_cn = '总服务费从 Php0 到 ' . 'Php'. ($cs->cost + $cs->charge + $cs->tip);
                    } elseif($request->active == 0) { // Disabled
                        $transtat = 'Service was disabled.';
                        $transtat_cn = '服务被标记为失效.';
                        $translog = 'Total service charge from Php'. ($cs->cost + $cs->charge + $cs->tip).' to Php'.'0';
                        $translog_cn = '总服务费从 Php'. ($cs->cost + $cs->charge + $cs->tip).' 到 Php'.'0';
                    }

                    $newVal +=0;
                    $oldVal +=($cs->cost + $cs->charge + $cs->tip);
                }

                //check changes of discount
                $oldDiscount = 0;
                $newDiscount = 0;
                $discnotes = '';
                $discnotes_cn = '';
                $temp_note = 'total service charge from Php';
                $temp_note_cn = '总服务费从 Php';
                $deduct = 0;
                if($request->discount > 0) {

                    $__oldDiscount = null;

                    $discountExist = ClientTransaction::where("client_service_id",$cs->id)->where('type','Discount')->withTrashed()->first();

                    if($discountExist){ //update discount if existing
                        $__oldDiscount = $discountExist->amount;
                        $discountExist->amount =  $request->discount;
                        $discountExist->reason =  $request->reason;
                        $discountExist->deleted_at = null;
                        $discountExist->save();
                    } else { // if not exist, create new discount
                        ClientTransaction::create([
                            'client_id' => $cs->client_id,
                            'type' => 'Discount',
                            'amount' => $request->discount,
                            'group_id' => $cs->group_id,
                            'client_service_id' => $cs->id,
                            'reason' => $request->reason,
                            'tracking' => $cs->tracking,
                        ]);
                    }

                    // Update discount
                    $f = ($cs->cost + $cs->charge + $cs->tip) - $__oldDiscount;
                    $t = ($cs->cost + $cs->charge + $cs->tip) - $request->get('discount');
                    if($__oldDiscount != null && $__oldDiscount != $request->get('discount')) {
                        $deduct = $request->get('discount');
                        $discnotes = ' updated discount from Php' . $__oldDiscount . ' to Php' . $request->get('discount').' with the reason of '.$request->reason.', '. $temp_note . $f. ' to Php' .$t;
                        $discnotes_cn = ' 已更新折扣 ' . $__oldDiscount . ' 到 ' . $request->get('discount') .' 因为 '.$request->reason.', '. $temp_note_cn . ' 到 Php' . $t;
                        $oldDiscount = $__oldDiscount;
                        $newDiscount = $request->get('discount');
                    }

                    if($__oldDiscount == $request->get('discount')){
                        $oldDiscount = $__oldDiscount;
                        $newDiscount = $request->get('discount');
                    }

                    // New Discount
                    $f = ($cs->cost + $cs->charge + $cs->tip);
                    $t = ($cs->cost + $cs->charge + $cs->tip) - $request->get('discount');
                    if($__oldDiscount == null) {
                        $deduct = $request->get('discount');
                        $discnotes = ' discounted an amount of Php'.$request->get('discount').' with the reason of '.$request->reason.', '. $temp_note . $f. ' to Php' .$t;
                        $discnotes_cn = ' 已折扣额度 Php'.$request->get('discount').' 因为 '.$request->reason.', '. $temp_note_cn . $f. ' 到 Php' .$t;
                        $newDiscount = $request->get('discount');
                    }

                } else {
                    $discountExist = ClientTransaction::where('client_service_id', $cs->id)->where('type','Discount')->first();
                    if($discountExist){
                        // Delete from client_transactions
                        $discountExist->forceDelete();
                        $f = ($cs->cost + $cs->charge + $cs->tip) - $discountExist->amount;
                        $t = ($cs->cost + $cs->charge + $cs->tip);
                        $deduct = -1 * $discountExist->amount;
                        // When user removed discount
                        $discnotes = ' removed discount of Php ' . $discountExist->amount .' with the reason of '.$request->reason.', '. $temp_note . $f. ' to Php' .$t;
                        $discnotes_cn = ' 移除折扣 ' . $discountExist->amount.' 因为 '.$request->reason.', '. $temp_note_cn . $f. ' 到 Php' .$t;
                        $oldDiscount = $discountExist->amount;
                    }
                }

                // Check if there's changes in amounts
                $service_status = $request->status;
                $oldServiceCost = $cs->cost + $cs->charge + $cs->tip;
                $newServiceCost = $request->get('cost') + $request->get('charge') + $request->get('tip');
                // if($newDiscount > 0 || $oldDiscount > 0 ){
                //     $oldServiceCost -= $oldDiscount;
                //     $newServiceCost -= $newDiscount;
                // }

                if($request->get('active') == 1) { // Enabled
                    $toAmount = $newServiceCost;
                } elseif($request->get('active') == 0) { // Disabled
                    $toAmount = 0;
                }

                if ($oldServiceCost != $newServiceCost || $service_status == 'complete') {
                    if($service_status == 'complete' && $service_status != $cs->status){
                        $translog = 'Total service charge is Php' . ($toAmount - $deduct);
                        $translog_cn = '总服务费 Php' . ($toAmount - $deduct);
                    }
                    else if($service_status == 'complete' && $service_status == $cs->status){
                         $translog = 'Total service charge from Php' . ($oldServiceCost - $deduct) . ' to Php' . ($toAmount - $deduct);
                         $translog_cn = '总服务费从 Php' . ($oldServiceCost - $deduct) . ' 到 Php' . ($toAmount - $deduct);
                    }
                    else{
                        $translog = 'Total service charge from Php' . ($oldServiceCost - $deduct) . ' to Php' . ($toAmount - $deduct);
                        $translog_cn = '总服务费从 Php' . ($oldServiceCost - $deduct) . ' 到 Php' . ($toAmount - $deduct);
                    }

                    $newVal +=$newServiceCost;
                    $oldVal +=$oldServiceCost - $deduct;
                }

                if ($oldServiceCost == $newServiceCost && $translog == '' && $cs->status != $service_status) {
                    $translog = 'Service status change from '.$cs->status.' to '.$service_status;
                    $translog_cn = '';
                }


                // create client service remarks/note
                $remarks = $request->note.' - '. Auth::user()->first_name.' <small>('.date('Y-m-d H:i:s').')</small>';
                if($request->note==''){
                    $remarks = '';
                }
                $note = $cs->remarks;
                if($note!=''){
                    if($request->note!=''){
                        $note = $note.'</br>'.$remarks;
                    }
                }
                else{
                    $note = $remarks;
                }

                $cs->remarks = $note;
                $cs->cost = $request->cost;
                $cs->tip = $request->tip;
                $cs->status = $request->status;
                $cs->active = $request->active;
                $cs->save();

                $this->updatePackageStatus($cs->tracking); //update package status

                // Soft delete discount
                if($request->get('active') == 0) {
                    ClientTransaction::where('client_id', $cs->client_id)
                        ->where('client_service_id', $cs->id)
                        ->where('tracking', $cs->tracking)
                        ->where('type', 'Discount')
                        ->delete();
                }
                // Restore discount
                elseif($request->get('active') == 1) {
                    ClientTransaction::withTrashed()
                        ->where('client_id', $cs->client_id)
                        ->where('client_service_id', $cs->id)
                        ->where('tracking', $cs->tracking)
                        ->where('type', 'Discount')
                        ->restore();
                }

                //update user expirys
                $client_user = User::findorfail($cs->client_id);
                if($request->status == 'complete' && $request->active==1){
                    if($request->parent_id == 114 || $request->parent_id == 63){
                        $client_user->visa_type = '9A';
                        $client_user->arrival_date = $request->arrival_date;
                        $client_user->first_expiration_date = $request->first_exp_date;
                        $client_user->extended_expiration_date = $request->exp_date;
                    }
                    else if($request->parent_id == 70 || $request->parent_id == 148){
                        if($request->parent_id == 70){
                            $client_user->visa_type = '9G';
                        }
                        else{
                            $client_user->visa_type = 'TRV';
                        }
                        $client_user->icard_expiration_date = $request->icard_exp_date;
                        $client_user->icard_issue_date = $request->icard_issue_date;
                        $client_user->expiration_date = $request->exp_date;
                    }
                    else{
                        $client_user->visa_type = 'CWV';
                        $client_user->expiration_date = $request->exp_date;
                    }

                    $client_user->save();
                }

                //save transaction logs
                if($discnotes != '' ){
                    $log2 =  'Updated Service : '.$discnotes.'. ';
                    $log_cn2 =  '服务更新 : '. $discnotes_cn. '. ';

                    $log_data2 = array(
                        'client_service_id' => $cs->id,
                        'client_id' => $cs->client_id,
                        'group_id' => $cs->group_id,
                        'log_type' => 'Transaction',
                        'log_group' => 'service',
                        'detail'=> $log2,
                        'detail_cn'=> $log_cn2,
                        'amount'=> $t,
                    );

                    if(($cs->status != 'complete')){
                        $log_data2['amount'] = 0;
                    }
                    else{
                        $log_data2['amount'] = '-'.$t;
                    }

                    LogController::save($log_data2);
                }


                $log =  ' : '.$translog.'. ' . $transtat;
                $log_cn =  ' : '. $translog_cn. '. ' . $transtat_cn;
                if($translog != '' || $transtat != '' ){
                    $newVal = $oldVal - $newVal;
                    //$user = Auth::user();
                    if($oldactive == 0 && $request->active == 1){
                        $newVal = '-'.$newVal;
                    }


                    $log_data = array(
                        'client_service_id' => $cs->id,
                        'client_id' => $cs->client_id,
                        'group_id' => $cs->group_id,
                        'log_type' => 'Transaction',
                        'log_group' => 'service',
                        'detail'=> $log,
                        'detail_cn'=> $log_cn,
                        'amount'=> $newVal,
                    );

                    if($oldstatus != $service_status && $service_status == 'complete'){
                        $log_data['detail'] = 'Completed Service '.$log;
                        $log_data['detail_cn'] = '完成的服务 '.$log_cn;
                        $log_data['amount'] = '-'.($newServiceCost - $deduct);
                    }
                    else{
                        if(($cs->status != 'complete')){
                            $log_data['amount'] = 0;
                        }
                        else{
                            $log_data['amount'] = '-'.($newServiceCost - $deduct);
                        }

                        $log_data['detail'] = 'Updated Service '.$log;
                        $log_data['detail_cn'] = '服务更新 '.$log_cn;
                    }

                    LogController::save($log_data);
                }
            $response['tracking'] = $cs->tracking;
            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }

    public function addClientFunds(Request $request) {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            // $tracking = $request->get('tracking');
            $client_id = $request->get('client_id');
            $type = $request->get('type');
            $storage = $request->get('storage');
            $amount = $request->get('amount');
            $reason = $request->get('reason');
            $reason2 = $request->get('reason2');
            $cs_id = $request->get('cs_id');
            // $branch_id = $request->get('branch_id');
            $bank = $request->get('bank');
            $alipay_reference = $request->get('alipay_reference');
            $selected_client = $request->get('selected_client');
            $selected_group = $request->get('selected_group');
            $transfer_to = $request->get('transfer_to');

            if ($type == "Deposit") {
                $ewallet_depo = new ClientEWallet;
                $ewallet_depo->client_id = $client_id;
                $ewallet_depo->type = 'Deposit';
                $ewallet_depo->group_id = null;
                if($storage=='Bank'){
                    $ewallet_depo->storage_type = $bank;
                }
                if($storage=='Alipay'){
                    $amount = $amount-($amount*0.0175);
                    $ewallet_depo->storage_type = $bank;
                    $ewallet_depo->alipay_reference = $alipay_reference;
                }
                $ewallet_depo->amount = $amount;
                $ewallet_depo->reason = $reason2;
                $ewallet_depo->save();

                // save financing
                $deptype = $storage;
                if($storage == 'Bank'){
                    $deptype = $bank;
                }

                $finance = new Financing;
                $finance->user_sn = Auth::user()->id;
                $finance->type = "deposit";
                $finance->record_id = $ewallet_depo->id;
                $finance->cat_type = "process";
                $finance->cat_storage = $storage;
                $finance->branch_id = 1;
                $finance->storage_type = $bank;
                $finance->trans_desc = Auth::user()->first_name.' received '.$deptype.' deposit from client #'.$client_id;
                if($storage=='Alipay'){
                    $finance->trans_desc = Auth::user()->first_name.' received '.$deptype.' deposit from client #'.$client_id.' with Alipay reference: '.$alipay_reference;
                }
                ((strcasecmp($storage,'Cash')==0) ? $finance->cash_client_depo_payment = $amount : $finance->bank_client_depo_payment = $amount);
                $finance->save();

                // save transaction logs
                $detail = 'Receive '.$deptype.' deposit with an amount of Php'.$amount.'.';
                $detail_cn = '预存了款项 Php'.$amount.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $client_id,
                    'group_id' => null,
                    'log_type' => 'Ewallet',
                    'log_group' => 'deposit',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> $amount,
                );
                 LogController::save($log_data);
            }

            else if($type == "Payment") {
                $payment = ClientTransaction::where('type','Payment')->where('client_service_id',$cs_id)->first();
                $rson = 'Paid Php'.$amount.' - '.$reason.' ('.date('Y-m-d H:i:s').')<br><br>';
                if($payment){
                    $payment->amount += $amount;
                    $payment->reason = $rson.$payment->reason;
                    $payment->save();
                }
                else{
                    $payment = new ClientTransaction;
                    $payment->client_id = $client_id;
                    if($request->get('paytype') == 'Service'){
                        $payment->client_service_id = $cs_id;
                    }
                    else{
                        $payment->order_id = $cs_id;
                    }
                    $payment->type = 'Payment';
                    $payment->group_id = null;
                    $payment->amount = $amount;
                    $payment->reason = $rson;
                    $payment->save();
                }

                if($request->get('paytype') == 'Service'){
                    $service = ClientService::findOrFail($cs_id);
                    if($service->payment_amount > 0){
                        $service->payment_amount += $amount;
                    }
                    else{
                        $service->payment_amount = $amount;
                    }
                    if($service->payment_amount == $request->get('total_cost')){
                        $service->is_full_payment = 1;
                    }
                    $service->save();
                }
                else{
                    $order = Order::findOrFail($cs_id);
                    if($order->money_received > 0){
                        $order->money_received += $amount;
                    }
                    else{
                        $order->money_received = $amount;
                    }
                    $order->save();
                }
                //for financing
                // $finance = new Financing;
                // $finance->user_sn = Auth::user()->id;
                // $finance->type = "payment";
                // $finance->record_id = $payment->id;
                // $finance->cat_type = "process";
                // $finance->cat_storage = $storage;
                // $finance->branch_id = $branch_id;
                // $finance->storage_type = $bank;
                // $finance->trans_desc = Auth::user()->first_name.' received payment from client #'.$client_id.' on Package #'.$tracking;
                // if($storage=='Alipay'){
                //     $finance->trans_desc = Auth::user()->first_name.' received payment from client #'.$client_id.' on Package #'.$tracking.' with Alipay reference: '.$alipay_reference;
                // }
                // ((strcasecmp($storage,'Cash')==0) ? $finance->cash_client_depo_payment = $amount : $finance->bank_client_depo_payment = $amount);
                // $finance->save();

                // save transaction logs
                $detail = 'Paid an amount of Php'.$amount.'.';
                $detail_cn = '已支付 Php'.$amount.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $client_id,
                    'group_id' => null,
                    'log_type' => 'Transaction',
                    'log_group' => 'payment',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> $amount,
                );
                LogController::save($log_data);

                $detail = 'Paid '.strtolower($request->get('paytype')).' with an amount of Php'.$amount.'.';
                $detail_cn = '已支付 Php'.$amount.'.';
                $log_data = array(
                    'client_service_id' => ($request->get('paytype') == 'Service' ? $cs_id : null),
                    'client_id' => $client_id,
                    'group_id' => null,
                    'log_type' => 'Ewallet',
                    'log_group' => 'payment',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> '-'.$amount,
                );
                LogController::save($log_data);
            }

            else if($type == "Refund") {
                    $ewallet_refund = new ClientEWallet;
                    $ewallet_refund->client_id = $client_id;
                    $ewallet_refund->type = 'Refund';
                    $ewallet_refund->amount = $amount;
                    $ewallet_refund->group_id = null;
                    $ewallet_refund->reason = $reason;
                    if($storage=='Bank'){
                        $ewallet_refund->storage_type = $bank;
                    }
                    $ewallet_refund->save();

                    //for financing
                    $finance = new Financing;
                    $finance->user_sn = Auth::user()->id;
                    $finance->type = "refund";
                    $finance->record_id = $ewallet_refund->id;
                    $finance->cat_type = "process";
                    $finance->cat_storage = $storage;
                    $finance->cash_client_refund = $amount;
                    $finance->branch_id = 1;
                    $finance->trans_desc = Auth::user()->first_name.' refund to client #'.$client_id.' for the reason of '.$reason;
                    $finance->storage_type = ($storage!='Cash') ? $bank : null;
                    $finance->save();

                    // save transaction logs
                    $detail = 'Withdrew an amount of Php'.$amount.' with the reason of <i>"'.$reason.'"</i>.';
                    $detail_cn = '退款了 Php'.$amount.' 因为 "'.$reason.'".';
                    $log_data = array(
                        'client_service_id' => null,
                        'client_id' => $client_id,
                        'group_id' => null,
                        'log_type' => 'Ewallet',
                        'log_group' => 'refund',
                        'detail'=> $detail,
                        'detail_cn'=> $detail_cn,
                        'amount'=> '-'.$amount,
                    );
                    LogController::save($log_data);
            }

            else if($type == "Discount" || $type == "Promo"){
                $discount = new ClientTransaction;
                $discount->client_id = $client_id;
                $discount->tracking = $tracking;
                $discount->type = 'Discount';
                $discount->amount = $amount;
                $discount->group_id = null;
                $discount->reason = $reason;
                if($storage=='Bank'){
                    $discount->storage_type = $bank;
                }
                $discount->save();

                // save transaction logs
                $detail = 'Discounted an amount of Php'.$amount.' with the reason of <i>"'.$reason.'"</i>.';
                $detail_cn = '给于折扣 Php'.$amount.' 因为"'.$reason.'".';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $client_id,
                    'group_id' => null,
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
                $refund = new ClientEWallet;
                $refund->client_id = $client_id;
                $refund->type = 'Refund';
                $refund->amount = $amount;
                $refund->group_id = null;
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
                $detail = 'Withdrew an amount of Php'.$amount.', transferred to '.$request->transfer_to.' '.$transferred;
                $detail_cn = '退款 Php'.$amount.', 转移到了客户 '.$transferred;
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $client_id,
                    'group_id' => null,
                    'log_type' => 'Ewallet',
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
                $depo = new ClientEWallet;
                $depo->client_id = $transTo;
                $depo->type = 'Deposit';
                $depo->amount = $amount;
                $depo->group_id = $grid;
                // $depo->tracking = null;
                $depo->save();

                // for financing
                $finance = new Financing;
                $finance->user_sn = Auth::user()->id;
                $finance->type = "transfer";
                $finance->record_id = $depo->id;
                $finance->cat_type = "process";
                $finance->cat_storage = $storage;
                $finance->branch_id = 1;
                ((strcasecmp($storage,'Cash')==0) ? $finance->cash_client_depo_payment = $amount : $finance->bank_client_depo_payment = $amount);
                ((strcasecmp($storage,'Cash')==0) ? $finance->cash_client_refund = $amount : $finance->bank_cost = $amount);
                $finance->trans_desc = Auth::user()->first_name.' transferred funds from client #'.$client_id.' to '.$request->transfer_to.' '.$transferred.'.';
                $finance->save();

                 // save transaction logs
                $client = User::findorfail($client_id);
                $detail = 'Deposited an amount of Php'.$amount.' from client '.$client->first_name.' '.$client->last_name.'.';
                $detail_cn = '预存了款项 Php'.$amount.' 从客户 '.$client_id.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $transTo,
                    'group_id' => $grid,
                    'log_type' => 'Ewallet',
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

    public function oldaddClientFunds(Request $request) {
        $validator = Validator::make($request->all(), [
            'tracking' => 'required',
            'client_id' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $tracking = $request->get('tracking');
            $client_id = $request->get('client_id');
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

            if ($type == "Deposit") {
                $depo = new ClientTransaction;
                $depo->client_id = $client_id;
                $depo->tracking = $tracking;
                $depo->type = 'Deposit';
                $depo->group_id = null;
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
                $finance->trans_desc = Auth::user()->first_name.' received deposit from client #'.$client_id.' on Package #'.$tracking;
                if($storage=='Alipay'){
                    $finance->trans_desc = Auth::user()->first_name.' received deposit from client #'.$client_id.' on Package #'.$tracking.' with Alipay reference: '.$alipay_reference;
                }
                ((strcasecmp($storage,'Cash')==0) ? $finance->cash_client_depo_payment = $amount : $finance->bank_client_depo_payment = $amount);
                $finance->save();

                // save transaction logs
                $detail = 'Deposited an amount of Php'.$amount.'.';
                $detail_cn = '预存了款项 Php'.$amount.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $client_id,
                    'group_id' => null,
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
                $payment->group_id = null;
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
                $finance->trans_desc = Auth::user()->first_name.' received payment from client #'.$client_id.' on Package #'.$tracking;
                if($storage=='Alipay'){
                    $finance->trans_desc = Auth::user()->first_name.' received payment from client #'.$client_id.' on Package #'.$tracking.' with Alipay reference: '.$alipay_reference;
                }
                ((strcasecmp($storage,'Cash')==0) ? $finance->cash_client_depo_payment = $amount : $finance->bank_client_depo_payment = $amount);
                $finance->save();

                // save transaction logs
                $detail = 'Paid an amount of Php'.$amount.'.';
                $detail_cn = '已支付 Php'.$amount.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $client_id,
                    'group_id' => null,
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
                    $refund->group_id = null;
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
                    $finance->trans_desc = Auth::user()->first_name.' refund to client #'.$client_id.' on Package #'.$tracking.' for the reason of '.$reason;
                    $finance->storage_type = ($storage!='Cash') ? $bank : null;
                    $finance->save();

                    // save transaction logs
                    $detail = 'Refunded an amount of Php'.$amount.' with the reason of <i>"'.$reason.'"</i>.';
                    $detail_cn = '退款了 Php'.$amount.' 因为 "'.$reason.'".';
                    $log_data = array(
                        'client_service_id' => null,
                        'client_id' => $client_id,
                        'group_id' => null,
                        'log_type' => 'Transaction',
                        'log_group' => 'refund',
                        'detail'=> $detail,
                        'detail_cn'=> $detail_cn,
                        'amount'=> '-'.$amount,
                    );
                    LogController::save($log_data);
            }

            else if($type == "Discount" || $type == "Promo"){
                $discount = new ClientTransaction;
                $discount->client_id = $client_id;
                $discount->tracking = $tracking;
                $discount->type = 'Discount';
                $discount->amount = $amount;
                $discount->group_id = null;
                $discount->reason = $reason;
                if($storage=='Bank'){
                    $discount->storage_type = $bank;
                }
                $discount->save();

                // save transaction logs
                $detail = 'Discounted an amount of Php'.$amount.' with the reason of <i>"'.$reason.'"</i>.';
                $detail_cn = '给于折扣 Php'.$amount.' 因为"'.$reason.'".';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $client_id,
                    'group_id' => null,
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
                $refund->group_id = null;
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
                    'client_id' => $client_id,
                    'group_id' => null,
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
                $finance->trans_desc = Auth::user()->first_name.' transffered funds from client #'.$client_id.' to '.$request->transfer_to.' '.$transferred.'.';
                $finance->save();

                 // save transaction logs
                $client = User::findorfail($client_id);
                $detail = 'Deposited an amount of Php'.$amount.' from client '.$client->first_name.' '.$client->last_name.'.';
                $detail_cn = '预存了款项 Php'.$amount.' 从客户 '.$client_id.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $transTo,
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

    public function addClientPackage(Request $request){
        $client_id = $request->client_id;
        Repack:
        $tracking = $this->generateIndividualTrackingNumber(7);
        $check_package = Package::where('tracking', $tracking)->count();

        if($check_package > 0) :
            goto Repack;
        endif;

        $new_package_data = array(
            'client_id' => $client_id,
            'tracking' => $tracking,
        );

        $new_package = Package::insert($new_package_data);


        if ($new_package) :

            //save action logs
            $detail = 'Created new package '.$tracking.'.';
            $detail_cn = '创建了新的服务包 '.$tracking.'.';
            $log_data = array(
                'client_service_id' => null,
                'client_id' => $client_id,
                'group_id' => null,
                'log_type' => 'Action',
                'detail'=> $detail,
                'detail_cn'=> $detail_cn,
            );
            LogController::save($log_data);

            $response['status'] = 'Success';
            $response['tracking'] = $tracking;
            $response['code'] = 200;
        else :
            $response['status'] = 'Failed';
        endif;

        return json_encode($response);
    }

    public function deleteClientPackage(Request $request){
        $tracking = $request->get('tracking');
        $check_registered_service = ClientService::where('tracking', $tracking)->count();
        $package = Package::where('tracking', $tracking)->first();

        if ($check_registered_service < 1) :
            $delete_package = Package::where('tracking', $tracking)->delete();
            if($delete_package) :
                //save action logs
                $detail = 'Deleted package number '.$tracking.'.';
                $detail_cn = '删除了服务包 '.$tracking.'.';
                $log_data = array(
                    'client_service_id' => null,
                    'client_id' => $package->client_id,
                    'group_id' => null,
                    'log_type' => 'Action',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                );
                LogController::save($log_data);
                $result = array('status' => 'success', 'log' => 'Deleted Package', 'code' => 200);
            else :
                $result = array('status' => 'failed', 'log' => 'Error Deleting Package');
            endif;
        else :
            $result = array('status' => 'failed', 'log' => 'Unable to delete, package contains services.');
        endif;
        return json_encode($result);
    }

    public function addTemporaryClient(Request $request) {
        $validator = Validator::make($request->all(), [
            'contact_number' => 'required|min:10|max:13|unique:contact_numbers,number',
            'branch' => 'required',
            'birthdate' => 'nullable|date',
            'passport' => 'nullable|unique:users,passport'
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $contactNumber = '+63'.$request->contact_number;

            // if(strlen($contactNumber) === 13) {
            //     $number = substr($contactNumber, 3);
            // } else if(strlen($contactNumber) === 12) {
            //     $number = substr($contactNumber, 2);
            // } else {
            //     $number = substr($contactNumber, 1);
            // }
            // $number = '+63'.$contactNumber;

            // $contact = ContactNumber::where('number','LIKE','%'.$number.'%')->count();
            $contact = ContactNumber::where('number', $contactNumber)->count();

            if($contact > 0) {
                $response['status'] = 'Failed';
                $response['errors'] = ['contact_number' => ['The contact number has already been taken.']];
                $response['code'] = 422;
            } else {
                $client = User::create([
                    'first_name' => ($request->first_name) ? $request->first_name : $contactNumber,
                    'last_name' => ($request->last_name) ? $request->last_name : 'N/A',
                    'birth_date' => ($request->birthdate) ? $request->birthdate : null,
                    'passport' => ($request->passport) ? $request->passport : null,
                    'gender' => ($request->gender) ? $request->gender : null
                ]);

                $client->update(['email' => $client->id]);

                //save action logs
                $detail = "Created new client -> ".$client->first_name.' '.$client->last_name.'.';
                $detail_cn = "Created new client -> ".$client->first_name.' '.$client->last_name.'.';
                $log_data = array(
                    'client_id' => $client->id,
                    'group_id' => null,
                    'log_type' => 'Action',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> 0,
                );
                LogController::save($log_data);

                ContactNumber::create([
                    'user_id' => $client->id,
                    'number' => $contactNumber
                ]);

                $client->branches()->attach($request->branch);
                $client->roles()->attach(2);

                $response['status'] = 'Success';
                $response['code'] = 200;
            }

        }

        return Response::json($response);
    }

    //get branch of current user
    public function getBranchAuth(){
        $branch = DB::table('branch_user')->where('user_id', Auth::User()->id)
                ->pluck('branch_id')[0];
        return $branch;
    }

    //List of Pending Services
    public function getPendingServices(Request $request, $perPage = 20){
        $auth_branch =  $this->getBranchAuth();

        $sort = $request->input('sort');
        $search = $request->input('search');

        $services = ClientService::with('client')->whereHas('client.branches', function ($query) use ($auth_branch) {
                $query->where('branches.id', '=', $auth_branch);
                })->where('client_services.status','pending')->where('client_services.active', '1')
                ->where(function($query) {
                    return $query->where('checked', '0')->orWhere('checked', NULL);
                })
                ->with(array('client.groups' => function($query){
                    $query->select('name');
                }))
                ->leftjoin('group_user', 'client_services.client_id', '=', 'group_user.user_id')
                ->leftjoin('groups', 'group_user.group_id', '=', 'groups.id')
                ->select('client_services.*', 'groups.name as group_name')
                ->when($sort != '', function ($q) use($sort){
                    $sort = explode('-' , $sort);
                    if($sort[0] === 'group') {
                        return $q->orderBy('group_name', $sort[1]);
                    } else {
                        return $q->orderBy('client_services.' . $sort[0], $sort[1]);
                    }

                })
                ->where(function ($services) use($search) {
                    $dateSearch = str_replace('/', '-', $search);
                    $services->orwhere('client_services.created_at', 'LIKE', '%'.$dateSearch.'%')
                          ->orwhere('client_services.detail', 'LIKE', '%'.$search.'%')
                          ->orwhere('groups.name', 'LIKE', '%'.$search.'%');
                })
                ->paginate($perPage);


        $response['status'] = 'Success';
        $response['data'] = $services;
        $response['code'] = 200;
        return Response::json($response);

    }

    //List of On Process Services
    public function getOnProcessServices(Request $request, $perPage = 20){
        $auth_branch =  $this->getBranchAuth();

        $sort = $request->input('sort');
        $search = $request->input('search');

        $services = ClientService::with('client')->whereHas('client.branches', function ($query) use ($auth_branch) {
                $query->where('branches.id', '=', $auth_branch);
                })->where('client_services.status','on process')->where('client_services.active', '1')
                ->where(function($query) {
                    return $query->where('checked', '0')
                        ->orWhere('checked', NULL);
                })->with(array('client.groups' => function($query){
                    $query->select('name');
                }))
                ->leftjoin('group_user', 'client_services.client_id', '=', 'group_user.user_id')
                ->leftjoin('groups', 'group_user.group_id', '=', 'groups.id')
                ->select('client_services.*', 'groups.name as group_name')
                ->when($sort != '', function ($q) use($sort){
                    $sort = explode('-' , $sort);
                    if($sort[0] === 'group') {
                        return $q->orderBy('group_name', $sort[1]);
                    } else {
                        return $q->orderBy('client_services.' . $sort[0], $sort[1]);
                    }
                })
                ->where(function ($services) use($search) {
                    $dateSearch = str_replace('/', '-', $search);
                    $services->orwhere('client_services.created_at', 'LIKE', '%'.$dateSearch.'%')
                          ->orwhere('client_services.detail', 'LIKE', '%'.$search.'%')
                          ->orwhere('groups.name', 'LIKE', '%'.$search.'%');
                })
                ->paginate($perPage);

        $response['status'] = 'Success';
        $response['data'] = $services;
        $response['code'] = 200;
        return Response::json($response);

    }

    //List of Today's Services
    public function getTodayServices(Request $request, $perPage = 20){
        $auth_branch =  $this->getBranchAuth();

        $date = $request->input('date');
        $search = $request->input('search');
        $sort = $request->input('sort');

        $services = ClientService::with('client')->whereHas('client.branches', function ($query) use ($auth_branch) {
                $query->where('branches.id', '=', $auth_branch);
                })
                // ->where('client_services.status','complete')
                ->where('client_services.active', '1')
                ->where('client_services.created_at', 'LIKE', '%'.$date.'%')
                ->where(function($query) {
                    return $query->where('checked', '0')->orWhere('checked', NULL);
                })->with(array('client.groups' => function($query){
                    $query->select('name');
                }))
                ->leftjoin('users', 'client_services.client_id', '=', 'users.id')
                ->select('client_services.*', DB::raw('CONCAT(users.last_name," ",users.first_name) AS full_name'))
                ->where(function ($services) use($search) {
                    $dateSearch = str_replace('/', '-', $search);
                    $services->orwhere('client_services.created_at', 'LIKE', '%'.$dateSearch.'%')
                          ->orwhere(DB::raw('CONCAT(users.last_name," ",users.first_name)'), 'LIKE', '%'.$search.'%')
                          ->orwhere('client_services.id', 'LIKE', '%'.$search.'%')
                          ->orwhere('client_services.tracking', 'LIKE', '%'.$search.'%')
                          ->orwhere('client_services.detail', 'LIKE', '%'.$search.'%');
                })
                ->when($sort != '', function ($q) use($sort){
                    $sort = explode('-' , $sort);
                    if($sort[0] === 'name') {
                        return $q->orderBy('full_name', $sort[1]);
                    } else {
                        return $q->orderBy('client_services.' . $sort[0], $sort[1]);
                    }
                })
                ->paginate($perPage);

        $response['status'] = 'Success';
        $response['data'] = $services;
        $response['code'] = 200;
        $response['date'] = $date;
        return Response::json($response);

    }

    // List of Today's Tasks
    public function getTodayTasks(Request $request) {
      $date = $request['data'];

      $tasks = Tasks::with('client_service')
                ->with(array('client_service.client'))
                ->leftjoin('users as u', 'tasks.who_is_in_charge', '=', 'u.id')
                ->when($date != null, function ($q) use ($date) {
                  return $q->where('tasks.date', '>=', $date);
                })
                ->select('tasks.*', 'u.first_name as in_charge_first_name', 'u.last_name as in_charge_last_name')
                ->orderBy('tasks.date', 'desc')
                ->get();


      $response['status'] = 'Success';
      $response['data'] = $tasks;
      $response['code'] = 200;
      $response['test'] = $request['data'];
      return Response::json($response);
    }

    public function getEmployees() {
      $role = DB::table('role_user')
              ->leftjoin('users', 'role_user.user_id', '=', 'users.id')
              ->where('role_user.role_id', '4')
              ->select('role_user.*', 'users.first_name', 'users.last_name')
              ->get();

      $response['status'] = 'Success';
      $response['data'] = $role;
      $response['code'] = 200;
      return Response::json($response);
    }

    public function getReminders(Request $request, $perPage = 5) {
      $range = Carbon::now()->addDays(7)->format('Y-m-d');


      $query = User::where('visa_type', '<>', null)
              ->where(function($query) use ($range) {
                return $query->where('expiration_date', '<=', $range)->orWhere('first_expiration_date', '<=', $range);
              })
              ->orderBy('id', 'asc')
              ->paginate($perPage);

      $response['status'] = 'Success';
      $response['data'] = $query;
      $response['code'] = 200;
      return Response::json($response);
    }

    //ServiceProfile
    public function switchCostLevel($clientId, $level) {

    if($clientId !== 0){

        $client = User::where('id',$clientId)->first();
        $branchUser = BranchUser::where('user_id',$clientId)->first();
        if($client){
            $client->service_profile_id = $level;
            $client->save();
        }

        $services = ClientService::where('client_id',$clientId)->where('group_id',null)
                        ->where(function ($query) {
                            $query->where('status', '!=', 'complete')
                                      ->where('status', '!=', 'released');
                            })
                        ->get();

        foreach($services as $ms){
            $getService = Service::where('id',$ms->service_id)->first();
            if($getService){
                $cost = 0;
                $charge = 0;
                $tip = 0;
                $client = 0 ;
                $agent = 0 ;

                if($branchUser->branch_id > 1){
                    $amounts = ServiceBranchCost::where('branch_id',$branchUser->branch_id)->where('service_id',$getService->id)->first();
                    $charge = $amounts->charge;
                    $cost = $amounts->cost;
                    $tip = $amounts->tip;
                }

                if($level > 0){
                    $pcost = ServiceProfileCost::where('profile_id',$level)->where('service_id',$getService->id)->where('branch_id',$branchUser->branch_id)->first();
                    if($pcost){
                        $charge = $pcost->charge;
                        $cost = $pcost->cost;
                        $tip = $pcost->tip;
                        $client = $pcost->com_client;
                        $agent = $pcost->com_agent;
                    }

                    $charge = ($charge > 0 ? $charge : $getService->charge);
                    $cost = ($cost > 0 ? $cost : $getService->cost);
                    $tip = ($tip > 0 ? $tip : $getService->tip);
                }

                if($level == 0 && $branchUser->branch_id == 1){
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

    /**** Private Functions ****/

    private function generateIndividualTrackingNumber($length = 7) {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    public static function updatePackageStatus($tracking){
        $status = null; // empty

        $countCancelledServices = DB::table('client_services')
            ->select('*')
            ->where('tracking', $tracking)
            // ->where('active', 0)
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

    /**** END Private Functions ****/


    /**** Computations ****/

    private function getClientTotalPointsEarned($id) {
        return  ClientService::where('agent_com_id', $id)
                    ->where(function ($query) {
                        $query->where('status', 'complete')
                             ->orwhere('status', 'released');
                    })
                    ->sum('com_agent') +
                ClientService::where('client_com_id', $id)
                    ->where(function ($query) {
                        $query->where('status', 'complete')
                             ->orwhere('status', 'released');
                    })
                    ->sum('com_client');
    }

    private function getClientDeposit($id) {
        return ClientTransaction::where('client_id', $id)->where('group_id', null)->where('type', 'Deposit')->sum('amount');
    }

    private function getClientPayment($id) {
        $clientActiveServices = ClientService::where('client_id', $id)
                                    ->where('active', 1)->where('group_id', null)
                                    ->where(function ($query) {
                                                $query->where('status','!=', 'cancelled');
                                            })->pluck('id');

        return ClientTransaction::where('client_id', $id)->where('group_id', null)
                    ->where(function ($q) use($clientActiveServices){
                        $q->whereIn('client_service_id', $clientActiveServices);
                        $q->orwhere('client_service_id',null);
                    })
                    ->where('type', 'Payment')
                    ->sum('amount');
    }

    private function getClientTotalDiscount($id) {
        return ClientTransaction::where('client_id', $id)->where('group_id', null)->where('type', 'Discount')
                    ->where('client_service_id',null)->sum('amount');
    }

    private function getClientTotalRefund($id) {
        return ClientTransaction::where('client_id', $id)->where('group_id', null)->where('type', 'Refund')->sum('amount');
    }

    private function getClientTotalCost($id) {
        $clientTotalCost = ClientService::where('client_id', $id)
            ->where('active', 1)->where('group_id', null)->where('status','!=','cancelled');
        $clids = $clientTotalCost->pluck('id');
        $clientTotalCost =   $clientTotalCost->value(DB::raw("SUM(cost + charge + tip + com_agent + com_client)"));

        $orderCost = Order::where('user_id', $id)->pluck('order_id');

        $clientTotalCost += OrderDetails::whereIn('order_id',$orderCost)->where('order_status',1)->sum('total_price');

        $discount =  ClientTransaction::where('client_id', $id)->where('group_id', null)->where('type', 'Discount')
                    ->where('client_service_id','!=',null)->whereIn('client_service_id', $clids)->sum('amount');

        $discount = ($discount) ? $discount : 0;

        return (($clientTotalCost) ? $clientTotalCost : 0) - $discount;
    }

    private function getClientTotalCompleteServiceCost($id) {
        $clientTotalCompleteServiceCost = ClientService::where('client_id', $id)
            ->where('active', 1)->where('group_id', null)
            ->where(function ($query) {
                        $query->where('status', 'complete')
                             ->orwhere('status', 'released');
                    });
        $clids = $clientTotalCompleteServiceCost->pluck('id');
        $clientTotalCompleteServiceCost = $clientTotalCompleteServiceCost->value(DB::raw("SUM(cost + charge + tip + com_agent + com_client)"));

        $discount =  ClientTransaction::where('client_id', $id)->where('group_id', null)->where('type', 'Discount')
                    ->where('client_service_id','!=',null)->whereIn('client_service_id', $clids)->sum('amount');

        $discount = ($discount) ? $discount : 0;

        return (($clientTotalCompleteServiceCost) ? $clientTotalCompleteServiceCost : 0) - $discount;
    }

    public function getClientTotalBalance($id) {
        return  (
                    (
                        $this->getClientDeposit($id)
                        + $this->getClientPayment($id)
                        +$this->getClientTotalDiscount($id)
                    )
                    -
                    (
                        $this->getClientTotalRefund($id)
                        + $this->getClientTotalCost($id)
                    )
                );
    }

    public function getClientTotalCollectables($id) {
        return  (
                    (
                        $this->getClientDeposit($id)
                        + $this->getClientPayment($id)
                        + $this->getClientTotalDiscount($id)
                    )
                    -
                    (
                        $this->getClientTotalRefund($id)
                        + $this->getClientTotalCompleteServiceCost($id)
                    )
                );
    }

  /**** END Computations ****/


	/*** Payments ***/
	public function getUnpaidServices(Request $request, $clientId, $isAutoGenerated, $page = 10){

			$sort = $request->input('sort');
			$search = $request->input('search');

			$clientServices = DB::table('client_services')
				->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, detail, created_at, payment_amount, tracking, SUM(cost + charge + tip) as total_cost'))
				->where('group_id',null)
				->where('active', 1)
				->where('client_id', $clientId)
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
				->paginate($page);


			$ctr = 0;
			$temp = [];


			$response = $clientServices;
			$tempResponse = [];

			foreach($clientServices->items() as $s){
				$s->discount =  ClientTransaction::where('client_service_id', $s->id)->where('type', 'Discount')->sum('amount');
				$temp['detail'] = $s->detail;
				$temp['id'] = $s->id;
				$temp['service_date'] = $s->sdate;
				$temp['sdate'] = $s->sdate;
				$temp['client_id'] = $clientId;
				$temp['tracking'] = $s->tracking;
			  $temp['total_cost'] = (($s->total_cost - $s->discount) - ($s->payment_amount));
				$tempResponse[$ctr] = $temp;
				$ctr++;
			 }

			 $response['services'] = $tempResponse;
			 $response['total_available_balance'] = ClientService::where('group_id', null)->where('client_id', $clientId)->sum('payment_amount');
			 $response['total_deposit'] = $this->getClientDeposit($clientId);

			return Response::json($response);
	}

	public function addServicePayment(Request $request){

			if($request->client_id !== null){

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

    public function getDocumentsOnHand($clientId) {
        $onHandDocuments = OnHandDocument::with('document')->withTrashed()
            ->where('client_id',$clientId)->orderBy('id', 'desc')->get()
            ->unique('document_id')->values();

        $response['status'] = 'Success';
        $response['data'] = [
            'onHandDocuments' => $onHandDocuments
        ];
        $response['code'] = 200;

        return Response::json($response);
    }

    public function getClientEwallet($id) {
        $depo = ClientEWallet::where('client_id', $id)->where('group_id', null)->where('type', 'Deposit')->sum('amount');

        $withdraw = ClientEWallet::where('client_id', $id)->where('group_id', null)->where('type', 'Refund')->sum('amount');

        $clientActiveServices = ClientService::where('client_id', $id)
                                    ->where('active', 1)->where('group_id', null)
                                    ->where(function ($query) {
                                                $query->where('status','!=', 'cancelled');
                                            })->pluck('id');

        $payment = ClientTransaction::where('client_id', $id)->where('group_id', null)
                    ->where('type', 'Payment')
                    ->where(function ($q) {
                        $q->where('client_service_id','!=',null);
                        $q->orwhere('order_id','!=',null);
                    })
                    ->whereIn('client_service_id', $clientActiveServices)
                    ->sum('amount');

        return $depo - ($withdraw + $payment);
    }

		public function getClientsByIds(Request $request) {

        $clients = DB::table('users')
										->whereIn('id', $request->ids)
										->select(DB::raw('id, first_name, last_name, concat(first_name, " ", last_name) as full_name'))
										->get();

        $response['status'] = 'Success';
        $response['data'] = $clients;
        $response['code'] = 200;

        return Response::json($response);
    }


    /*** Visa App ***/
    public function getAllClients() {
        $clients = DB::table('users')->get();

        $response['status'] = 'Success';
        $response['data'] = $clients;
        $response['code'] = 200;

        return Response::json($response);
    }

    public function addClientsRemark(Request $request){
        $validator = Validator::make($request->all(), [
            'remarks' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $d = new Remark;
            $d->client_id = $request->id;
            $d->remark = $request->remarks;
            $d->created_by = Auth::user()->id;
            $d->created_at = date("Y-m-d H:i:s");
            $d->save();

            $response['status'] = 'success';
            $response['code'] = 422;
            $response['data'] = $d;
        }

        return Response::json($request);
    }

    public function getClientsRemarks($client_id,$profile=false){
        $limit = PHP_INT_MAX;
        if($profile){
            $limit = 3;
        }
        $list = Remark::select('remark','u.first_name as created_by', 'remarks.created_at')->where("client_id", $client_id)->orderBy("remarks.id", "desc")
            ->leftjoin("users as u", "remarks.created_by", "u.id")->limit($limit)->get();

        foreach ($list as $l){
            $l->created_at = gmdate("F j, Y", strtotime($l->created_at));
        }

        $response['status'] = 'success';
        $response['code'] = 422;
        $response['data'] = $list;
        if(!$profile){
            return Response::json($response);
        }else{
            return $list;
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\ContactNumber;

use App\User;

use App\ClientService;

use App\ClientTransaction;

use App\Group;

use App\Package;

use App\Branch;

use Auth, DB, Response, Validator;

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

                p.latest_package, srv.latest_service as latest_service'))
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
                            and b.status = "complete"

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
            ->orderBy('u.id', 'desc')
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
                ) as collectable, 

                p.latest_package, srv.latest_service'))
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
                            and b.status = "complete"

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
            ->when($sort != '', function ($q) use($sort){
                $sort = explode('-' , $sort);
                return $q->orderBy($sort[0], $sort[1]);
            })
            ->paginate($perPage);            

        $response = $clients;

        return Response::json($response);
    }

    public function show($id){
        $client = User::with('nationalities')->find($id);

        if( $client ) {
            $client->contact = DB::table('contact_numbers')->where('user_id', $id)->where('is_primary',1)
                ->select(array('number'))->first();

            $client->birth_country = DB::table('countries')->where('id', $client->birth_country_id)
                ->select(array('name'))->first();

            $branch = DB::table('branch_user')->where('user_id', $id)
                ->select(array('branch_id'))->first();

            if($branch){
                $client->branch = DB::table('branches')->where('id', $branch->branch_id)
                ->select(array('name'))->first();
            }

            $client->total_points_earned = $this->getClientTotalPointsEarned($id);
            $client->total_complete_service_cost = $this->getClientTotalCompleteServiceCost($id);
            $client->total_cost = $this->getClientTotalCost($id);
            $client->total_payment = $this->getClientDeposit($id) + $this->getClientPayment($id);

            $client->total_discount = $this->getClientTotalDiscount($id);
            $client->total_refund = $this->getClientTotalRefund($id);
            $client->total_balance = $this->getClientTotalBalance($id);
            $client->total_collectables = $this->getClientTotalCollectables($id);

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

	public function clientSearch(Request $request) {
        $keyword = $request->input('search');
        $branch_id = $request->input('branch_id');

        $cids = ContactNumber::where("number",'LIKE', '%' . $keyword .'%')->pluck('id');
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
                ->when($branch_id != '', function ($q) use($branch_id){
                    return $q->where('bu.branch_id', $branch_id);
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
                ->when($branch_id != '', function ($q) use($branch_id){
                    return $q->where('bu.branch_id', $branch_id);
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

            if($results->count() == 0){
                preg_match_all('!\d+!', $keyword, $matches);
                $keyword = implode("", $matches[0]);
                $keyword = ltrim($keyword,"0");
                $keyword = ltrim($keyword,'+');
                $keyword = ltrim($keyword,'63');
                $cids = ContactNumber::where("number",'LIKE', '%' . $keyword .'%')->pluck('id');

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
                        ->when($branch_id != '', function ($q) use($branch_id){
                            return $q->where('bu.branch_id', $branch_id);
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
           if($p->checkyear >= 2016){
              $json[] = array(
                  'id' => $p->id,
                  'name' => $p->first_name." ".$p->last_name." -- [".$br."] -- ".$p->sdates."",
                  'full_name' => $p->first_name." ".$p->last_name,
              );
           }
           if($p->checkyear == null){
              $json[] = array(
                  'id' => $p->id,
                  'name' => $p->first_name." ".$p->last_name." -- [".$br."] -- No Service",
                  'full_name' => $p->first_name." ".$p->last_name,
              );
           } 
        }
        $response['status'] = 'Success';
        $response['data'] =  $json;
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
            'contact_numbers' => 'required|array',
            'contact_numbers.*.number' => 'required|unique:contact_numbers,number',
            'contact_numbers.*.is_primary' => 'required',
            'contact_numbers.*.is_mobile' => 'required',
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

        	foreach($request->nationalities as $nationality) {
        		$client->nationalities()->attach($nationality);
        	}

        	foreach($request->contact_numbers as $contactNumber) {
        		ContactNumber::create([
        			'user_id' => $client->id,
        			'number' => $contactNumber['number'],
        			'is_primary' => $contactNumber['is_primary'],
        			'is_mobile' => $contactNumber['is_mobile']
        		]);

        		if( $contactNumber['is_primary'] ) {
        			$client->update([
        				'password' => bcrypt($contactNumber['number'])
        			]);
        		}
        	}

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
            'contact_numbers.*.number' => 'required',
            'contact_numbers.*.is_primary' => 'required',
            'contact_numbers.*.is_mobile' => 'required',
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
	        	$client->save();

	        	$client->nationalities()->detach();
	        	foreach($request->nationalities as $nationality) {
	        		$client->nationalities()->attach($nationality);
	        	}

	        	$client->contactNumbers()->delete();
	        	foreach($request->contact_numbers as $contactNumber) {
	        		ContactNumber::create([
	        			'user_id' => $client->id,
	        			'number' => $contactNumber['number'],
	        			'is_primary' => $contactNumber['is_primary'],
	        			'is_mobile' => $contactNumber['is_mobile']
	        		]);

	        		if( $contactNumber['is_primary'] ) {
	        			$client->update([
	        				'password' => bcrypt($contactNumber['number'])
	        			]);
	        		}
	        	}

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

        return Response::json($response);
	}

    public function getClientServices($id, $tracking = 0) {
        if($tracking == 0 && strlen($tracking) == 1){  
             
            $services = DB::table('client_services as cs')
                ->select(DB::raw('cs.*,g.name as group_name'))
                ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','cs.group_id')
                ->where('client_id',$id)
                ->orderBy('cs.id', 'desc')
                ->get();
        }
        else{
            $services = DB::table('client_services as cs')
                ->select(DB::raw('cs.*,g.name as group_name'))
                ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','cs.group_id')
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
        $packs = DB::table('packages as p')->select(DB::raw('p.*,g.name as group_name'))
                    ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                    ->where('client_id', $id)
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

    public function getClientGroups($id) {    
        $group_ids = DB::table('group_user')->where('user_id',$id)->pluck('group_id');

        $groups = Group::with('branches', 'contactNumbers')
            ->select(array('id', 'name', 'leader_id', 'tracking', 'address'))
            ->whereIn('id',$group_ids)->get();

        foreach($groups as $g){
            $g->leader = DB::table('users')->where('id', $g->leader_id)
                ->select(array('first_name', 'last_name'))->first();
        }

        $response['status'] = 'Success';
        $response['data'] = $groups;
        $response['code'] = 200;

        return Response::json($response);
    }

    public function addTemporaryClient(Request $request) {
        $validator = Validator::make($request->all(), [
            'contact_number' => 'required|min:11|max:11|unique:contact_numbers,number',
            'branch' => 'required',
            'birthdate' => 'nullable|date',
            'passport' => 'nullable|unique:users,passport'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
            $contactNumber = $request->contact_number;

            $client = User::create([
                'first_name' => ($request->first_name) ? $request->first_name : $contactNumber,
                'last_name' => ($request->last_name) ? $request->last_name : $contactNumber,
                'birth_date' => ($request->birthdate) ? $request->birthdate : null,
                'passport' => ($request->passport) ? $request->passport : null,
                'gender' => ($request->gender) ? $request->gender : null
            ]);

            $client->update(['email' => $client->id]);

            ContactNumber::create([
                'user_id' => $client->id,
                'number' => $contactNumber
            ]);

            $client->branches()->attach($request->branch);

            $response['status'] = 'Success';
            $response['code'] = 200;
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
    public function getPendingServices(){
        $auth_branch =  $this->getBranchAuth();
        $services = ClientService::with('client')->whereHas('client.branches', function ($query) use ($auth_branch) {
                $query->where('branches.id', '=', $auth_branch);
                })->where('client_services.status','pending')->where('client_services.active', '1')
                ->where(function($query) {
                    return $query->where('checked', '0')->orWhere('checked', NULL);
                })->with(array('client.groups' => function($query){
                    $query->select('name');
                }))->leftJoin('services','services.id','=','client_services.service_id')->where('services.parent_id','!=',0)
                ->paginate(20);


        $response['status'] = 'Success';
        $response['data'] = $services;
        $response['code'] = 200;
        return Response::json($response);

    }

    //List of On Process Services
    public function getOnProcessServices(){
        $auth_branch =  $this->getBranchAuth();
        $services = ClientService::with('client')->whereHas('client.branches', function ($query) use ($auth_branch) {
                $query->where('branches.id', '=', $auth_branch);
                })->where('client_services.status','on process')->where('client_services.active', '1')
                ->where(function($query) {
                    return $query->where('checked', '0')
                        ->orWhere('checked', NULL);
                })->with(array('client.groups' => function($query){
                    $query->select('name');
                }))->leftJoin('services','services.id','=','client_services.service_id')->where('services.parent_id','!=',0)
                  ->get();

        $response['status'] = 'Success';
        $response['data'] = $services;
        $response['code'] = 200;
        return Response::json($response);

    }

    /**** Computations ****/

    private function getClientTotalPointsEarned($id) {
        return ClientService::where('agent_com_id', $id)->where('status','complete')->sum('com_agent') + 
                ClientService::where('client_com_id', $id)->where('status','complete')->sum('com_client');
    }

    private function getClientDeposit($id) {
        return ClientTransaction::where('client_id', $id)->where('group_id', null)->where('type', 'Deposit')->sum('amount');
    }

    private function getClientPayment($id) {
        return ClientTransaction::where('client_id', $id)->where('group_id', null)->where('type', 'Payment')->sum('amount');
    }

    private function getClientTotalDiscount($id) {
        return ClientTransaction::where('client_id', $id)->where('group_id', null)->where('type', 'Discount')->sum('amount');
    }

    private function getClientTotalRefund($id) {
        return ClientTransaction::where('client_id', $id)->where('group_id', null)->where('type', 'Refund')->sum('amount');
    }

    private function getClientTotalCost($id) {
        $clientTotalCost = ClientService::where('client_id', $id)
            ->where('active', 1)->where('group_id', null)
            ->value(DB::raw("SUM(cost + charge + tip + com_agent + com_client)"));

        return ($clientTotalCost) ? $clientTotalCost : 0;
    }

    private function getClientTotalCompleteServiceCost($id) {
        $clientTotalCompleteServiceCost = ClientService::where('client_id', $id)
            ->where('active', 1)->where('group_id', null)
            ->where('status', 'complete')
            ->value(DB::raw("SUM(cost + charge + tip + com_agent + com_client)"));

        return ($clientTotalCompleteServiceCost) ? $clientTotalCompleteServiceCost : 0;
    }

    private function getClientTotalBalance($id) {
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

    private function getClientTotalCollectables($id) {
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

}

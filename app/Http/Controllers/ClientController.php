<?php

namespace App\Http\Controllers;

use App\ContactNumber;

use App\User;

use App\ClientService;

use App\ClientTransaction;

use App\Group;

use DB, Response, Validator;

use Illuminate\Http\Request;

class ClientController extends Controller
{
    
	public function manageClients() {
		$clients = DB::table('users as u')
			->select(DB::raw('u.id, u.first_name, u.last_name, NULL as balance, NULL as collectables, NULL as latest_package, NULL as latest_service'))
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


    public function manageClientsPaginate() {
        $clients = DB::table('users as u')
            ->select(DB::raw('u.id, u.first_name, u.last_name,balance, collectable, NULL as latest_package, NULL as latest_service'))
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
            ->where('role.role_id', '2')
            ->paginate(20);

        $response = $clients;

        return Response::json($response);
    }

    public function show($id){
        $client = User::find($id);

        if( $client ) {
            $client->contact = DB::table('contact_numbers')->where('user_id', $id)->where('is_primary',1)
                ->select(array('number'))->first();

            $client->birth_country = DB::table('countries')->where('id', $client->birth_country_id)
                ->select(array('name'))->first();

            $client->nationality = DB::table('nationalities')->where('id', $client->birth_country_id)
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
            'contact_numbers.*.number' => 'required',
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

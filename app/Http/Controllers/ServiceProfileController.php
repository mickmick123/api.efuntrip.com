<?php

namespace App\Http\Controllers;

use App\ClientService;

use App\Group;

use App\OnHandDocument;

use App\ServiceProfile;

use App\ServiceProfileCost;

use App\User;

use DB, Response, Validator;

use Illuminate\Support\Str;

use Illuminate\Http\Request;

class ServiceProfileController extends Controller
{
    
    public function index() {
    	$response['status'] = 'Success';
		$response['data'] = [
		    'service_profiles' => ServiceProfile::where('is_active', 1)->orderBy('name')->get()
		];
		$response['code'] = 200;

		return Response::json($response);
    }

    public function store(Request $request) {
    	$validator = Validator::make($request->all(), [ 
            'name' => 'required|unique:service_profiles,name',
            'type' => 'required'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	$serviceProfile = ServiceProfile::create([
        		'name' => $request->name,
        		'slug' => Str::slug($request->name, '-'),
                'type' => $request->type
        	]);

            $serviceIds = DB::table('services')->pluck('id');
            $branchIds = DB::table('branches')->pluck('id');

            foreach( $serviceIds as $serviceId ) {
                foreach( $branchIds as $branchId ) {
                    ServiceProfileCost::create([
                        'service_id' => $serviceId,
                        'profile_id' => $serviceProfile->id,
                        'branch_id' => $branchId,
                        'cost' => 0, 
                        'charge' => 0,
                        'tip' => 0,
                        'com_agent' => 0,
                        'com_client' => 0
                    ]);
                }
            }

        	$response['status'] = 'Success';
			$response['code'] = 200;
        }

        return Response::json($response);
    }

    public function show($id) {
    	$serviceProfile = ServiceProfile::find($id);

		if( $serviceProfile ) {
			$response['status'] = 'Success';
			$response['data'] = [
			    'service_profile' => $serviceProfile
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
            'name' => 'required|unique:service_profiles,name,'.$id,
            'type' => 'required'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	$serviceProfile = ServiceProfile::find($id);

        	if( $serviceProfile ) {
        		$serviceProfile->update([
                    'name' => $request->name,
                    'slug' => Str::slug($request->name, '-'),
                    'type' => $request->type
                ]);

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

  public function destroy($id) {
		$serviceProfile = ServiceProfile::find($id);

		if( $serviceProfile ) {
			$serviceProfile->update(['is_active' => 0]);

			$response['status'] = 'Success';
        	$response['code'] = 200;
		} else {
			$response['status'] = 'Failed';
        	$response['errors'] = 'No query results.';
			$response['code'] = 404;
		}

		return Response::json($response);
  }
  

  public function getUsersGroups($id) {
    $groups = Group::where('service_profile_id', $id)->select('id', 'name', 'balance')->get();
    $users = User::where('service_profile_id', $id)
            ->select('users.id', DB::raw('CONCAT(users.first_name," ",users.last_name) AS name'), 'users.balance')
            ->get();

    $data = [];

    if(count($groups) > 0) {
      foreach($groups as $group) {
        $data[] = [
          'id' => $group->id,
          'name' => $group->name,
          'balance' => $group->balance,
          'type' => 'group'
        ];
      }
    }

    if(count($users) > 0) {
      foreach($users as $user) {

        $onHand = DB::table('on_hand_documents as ohd')
                  ->leftJoin('documents as docs', 'ohd.document_id', 'docs.id')
                  ->where('client_id', $user->id)
                  ->where('count', '>', 0)
                  ->select('ohd.*', 'docs.title')
                  ->get();
        $cs = DB::table('client_services')->where('client_id', $user->id)->whereIn('status', ['pending', 'on process'])->get();

        $data[] = [
          'id' => $user->id,
          'name' => $user->name,
          'balance' => $user->balance,
          'type' => 'client',
          'on_hand_documents' => $onHand,
          'client_services' => $cs
        ];
      }
    }

    $response['status'] = 'Success';
    $response['code'] = 200;
    $response['data'] = $data;
    return Response::json($response);
  }


}

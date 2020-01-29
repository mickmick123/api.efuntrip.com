<?php

namespace App\Http\Controllers;

use App\User;

use App\Role;

use App\Branch;

use App\ContactNumber;

use App\RoleUser;


use DB, Auth, Response, Validator, Hash;

use Illuminate\Http\Request;

class AccountsController extends Controller
{
	//get branch of current user
	public function getBranchAuth(){
		$branch = DB::table('branch_user')->where('user_id', Auth::User()->id)
                ->pluck('branch_id')[0];
        return $branch;
	}
    //get all Cpanel Users
    public function getCpanelUsers() {
	 	$role_id = Role::where('name', 'cpanel-admin')->pluck("id");
	 	$both_branch = Branch::where('name', 'Both')->pluck("id")[0];
    	$auth_branch =  $this->getBranchAuth();
	 	$users = User::select('id', 'email', 'first_name', 'last_name')
	 				->with(array('roles' => function($query){
	 					$query->select('roles.id', 'roles.label');
	 				}))->whereHas('roles', function ($query) use ($role_id) {
                		$query->where('roles.id', '=', $role_id);
	 	 	 		})->whereHas('branches', function ($query) use ($auth_branch, $both_branch) {
                		$query->where('branches.id', '=', $auth_branch)->orWhere('branches.id', '=', $both_branch);
	 	 	 		})->get();

	 	$response['status'] = 'Success';
	 	$response['data'] = $users;
        $response['code'] = 200;
	 	return Response::json($response);
	}

    //Save new cpanel user
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name'                    => 'required',
            'middle_name'                   => 'nullable',
            'last_name'                     => 'required',
            'birthday'                      => 'required|date',
            'gender'                        => 'required',
            'civil_status'                  => 'required',
            'height'                        => 'nullable',
            'weight'                        => 'nullable',
            'address'                       => 'required',
            'contact_numbers'               => 'required|array',
            'contact_numbers.*.number'      => 'nullable|min:11|max:13',
            'contact_numbers.*.is_primary'  => 'nullable',
            'contact_numbers.*.is_mobile'   => 'nullable',
            'email'                         => 'nullable|email|unique:users',
            'branch'                        => 'required'
            // 'password'                      => 'required|confirmed|min:6'         
        ]);



        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $user = new User;
            $user->first_name = $request->first_name;
            $user->middle_name = $request->middle_name;
            $user->last_name = $request->last_name;
            $user->birth_date = $request->birthday;
            $user->gender = $request->gender;
            $user->civil_status = $request->civil_status;
            $user->height = $request->height;
            $user->weight = $request->weight;
            $user->address = $request->address;
            $user->email = $request->email;
            $user->password = Hash::make('123admin');
            $user->save();

            foreach($request->contact_numbers as $contactNumber) {
                if(strlen($contactNumber['number']) !== 0 && $contactNumber['number'] !== null) {
                    ContactNumber::create([
                        'user_id' => $user->id,
                        'number' => $contactNumber['number'],
                        'is_primary' => $contactNumber['is_primary'],
                        'is_mobile' => $contactNumber['is_mobile']
                    ]);
                }
            }

            $user->roles()->detach();
            $user->roles()->attach($request->roles);

            $user->branches()->detach();
            $user->branches()->attach($request->branch);
            
            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }

    //get specific user
    public function show($id){
        $user = User::select('id', 'email', 'first_name', 'last_name', 'middle_name', 'birth_date', 'gender', 'civil_status', 'height', 'weight', 'address')
                    ->whereId($id)
                    ->with(array('roles' => function($query){
                        $query->select('roles.id');
                    }))->with(array('branches' => function($query){
                        $query->select('branches.id');
                    }))->with('contactNumbers')->first();

        if( $user ) {
            $response['status'] = 'Success';
            $response['data'] = [
                'user' => $user
            ];
            $response['code'] = 200;
        } else {
            $response['status'] = 'Failed';
            $response['errors'] = 'No query results.';
            $response['code'] = 404;
        }

        return Response::json($response);
    }

    //edit cpanel user
    public function update(Request $request, $id) {
         $validator = Validator::make($request->all(), [
            'first_name'                    => 'required',
            'middle_name'                   => 'nullable',
            'last_name'                     => 'required',
            'birthday'                      => 'required|date',
            'gender'                        => 'required',
            'civil_status'                  => 'required',
            'height'                        => 'nullable',
            'weight'                        => 'nullable',
            'address'                       => 'required',
            'contact_numbers'               => 'required|array',
            'contact_numbers.*.number'      => 'nullable|min:11|max:13',
            'contact_numbers.*.is_primary'  => 'nullable',
            'contact_numbers.*.is_mobile'   => 'nullable',
            'email'                         => 'nullable|email|unique:users,email,'.$request->id,
        ]);

        if($validator->fails()){
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
            $ce_count = 0;

            foreach($request->contact_numbers as $key=>$contactNumber) {
                if(strlen($contactNumber['number']) !== 0 && $contactNumber['number'] !== null) {
                    if(strlen($contactNumber['number']) === 13) {
                        $number = substr($contactNumber['number'], 3);
                    } else if(strlen($contactNumber['number']) === 12) {
                        $number = substr($contactNumber['number'], 2);
                    } else {
                        $number = substr($contactNumber['number'], 1);
                    }
                    
                    $contact = ContactNumber::where('number','LIKE','%'.$number.'%')->get();
                    
                    if($contact) {
                        $num_duplicate = 0;
                        foreach($contact as $con) {
                            if(strval ($con['user_id']) === strval ($request->id)) {
                                $num_duplicate++;
                            }
                        }

                        if($num_duplicate === 0) {
                            $contact_error['contact_numbers.'.$key.'.number'] = ['The contact number has already been taken.'];
                            $ce_count++;
                        }
                        
                    }
                }
            }

            if($ce_count > 0){
                $response['status'] = 'Failed';
                $response['errors'] = $contact_error;
                $response['code'] = 422;
            }else{
                $user = User::findOrFail($request->id);
                
                if($user){
                    $user->first_name = $request->first_name;
                    $user->middle_name = $request->middle_name;
                    $user->last_name = $request->last_name;
                    $user->birth_date = $request->birthday;
                    $user->gender = $request->gender;
                    $user->civil_status = $request->civil_status;
                    $user->height = $request->height;
                    $user->weight = $request->weight;
                    $user->address = $request->address;
                    $user->password = Hash::make($request->password);
                    $user->email = $request->email;
                    $user->save();

                    $user->branches()->detach();
                    $user->branches()->attach($request->branch);

                    $user->roles()->detach();
                    foreach($request->roles as $role) {
                        $user->roles()->attach($role);
                    }

                    //delete all contact numbers saved first before saving updates
                    $contact_numbers = ContactNumber::where('user_id', $request->id)->delete();
                    foreach($request->contact_numbers as $contactNumber) {
                        if(strlen($contactNumber['number']) !== 0 && $contactNumber['number'] !== null) {
                            ContactNumber::create([
                                'user_id' => $user->id,
                                'number' => $contactNumber['number'],
                                'is_primary' => $contactNumber['is_primary'],
                                'is_mobile' => $contactNumber['is_mobile']
                            ]);
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

    public function destroy($id){
        $user = User::findOrFail($id);
        
        if( $user ) {
            $user->delete();
            $user->branches()->detach();
            $user->roles()->detach();
            $client->contactNumbers()->delete();
            $response['status'] = 'Success';
            $response['code'] = 200;
        } else {
            $response['status'] = 'Failed';
            $response['errors'] = 'No query results.';
            $response['code'] = 404;
        }

        return Response::json($response);
    }





}

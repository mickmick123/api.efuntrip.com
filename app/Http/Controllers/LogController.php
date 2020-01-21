<?php

namespace App\Http\Controllers;

use App\Log;

use Auth, DB, Response, Validator;

use Illuminate\Http\Request;


class LogController extends Controller
{
    public function getTransactionLogs($client_id, $group_id) {  
        if($group_id == 0){
            $group_id = null;
        }  
        $cs_ids = DB::table('logs')->where('client_id',$client_id)->where('group_id',$group_id)->where('log_type','Transaction')->pluck('client_service_id');

        // $groups = Group::with('branches', 'contactNumbers')
        //     ->select(array('id', 'name', 'leader_id', 'tracking', 'address'))
        //     ->whereIn('id',$group_ids)->get();

        // foreach($groups as $g){
        //     $g->leader = DB::table('users')->where('id', $g->leader_id)
        //         ->select(array('first_name', 'last_name'))->first();
        // }

        $response['status'] = 'Success';
        $response['data'] = $cs_ids;
        $response['code'] = 200;

        return Response::json($response);
    }

    public static function save($log_data) {
        if(Auth::check()) {
            //Insert new log
            $log_data['processor_id'] = Auth::user()->id;        
            $log_data['log_date'] = date('Y-m-d');        
            Log::insert($log_data);
        }
    }

}

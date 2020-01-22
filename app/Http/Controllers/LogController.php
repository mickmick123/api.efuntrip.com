<?php

namespace App\Http\Controllers;

use App\Log;

use App\User;

use App\ClientService;

use App\Http\Controllers\ClientController;

use Auth, DB, Response, Validator;

use Illuminate\Http\Request;

use Carbon\Carbon;

class LogController extends Controller
{
    public function getTransactionLogs($client_id, $group_id) {  
        if($group_id == 0){
            $group_id = null;
        }  
        $translogs = DB::table('logs')->where('client_id',$client_id)->where('group_id',$group_id)->where('log_type','Transaction')->orderBy('id','desc')->get();

        $arraylogs = [];
        $month = null;
        $day = null;
        $year = null;
        $currentBalance = app(ClientController::class)->getClientTotalCollectables($client_id);
        $currentService = null;

        foreach($translogs as $t){
            if(($t->log_group == 'service' && $t->client_service_id != $currentService) || $t->log_group != 'service'){
                $body = "";
                $usr =  User::where('id',$t->processor_id)->select('id','first_name','last_name')->get();

                $cs = ClientService::where('id',$t->client_service_id)->first();

                $t->balance = $currentBalance;

                $currentBalance -= ($t->amount);

                $cdate = Carbon::parse($t->log_date)->format('M d Y');
                $dt = explode(" ", $cdate);
                $m = $dt[0];
                $d = $dt[1];
                $y = $dt[2];
                if($y == $year){
                    $y = null;
                    if($m == $month && $d == $day){
                        $m = null;
                        $d = null;
                    }
                    else{
                        $month = $m;
                        $day = $d;
                    }
                }
                else{
                    $year = $y;
                    $month = $m;
                    $day = $d;
                }


                if($cs){
                    $csdetail = $cs->detail;
                    $cstracking =  $cs->tracking;
                    $csstatus =  $cs->status;
                    $csactive =  $cs->active;
                    $currentService = $cs->id;

                    $body = DB::table('logs')->where('client_service_id', $cs->id)
                    ->where('id','!=', $t->id)
                    ->orderBy('id', 'desc')
                    ->distinct('detail')
                    ->pluck('detail');
                    $body = $body;
                }
                else{
                    $csdetail = ucfirst($a->type);
                    $cstracking = '';
                    $csstatus = '';
                    $csactive = 'none';
                    $body = '';
                    //$currentService = null;
                }

                $arraylogs[] = array(
                    'month' => $m,
                    'day' => $d,
                    'year' => $y,
                    'display_date' => Carbon::parse($y.'-'.$m.'-'.$d)->format('F d,Y'),
                    'data' => array ( 
                        'id' => $t->id,
                        'head' => $t->detail,
                        'body' => $body,
                        'balance' => $t->balance,
                        'prevbalance' => $currentBalance,
                        'amount' => $t->amount,
                        'type' => $t->log_group,
                        'processor' => $usr[0]->first_name,
                        'date' => Carbon::parse($t->log_date)->format('F d,Y'),
                        'title' => $csdetail,
                        'tracking' => $cstracking,
                        'status' => $csstatus,
                        'active' => $csactive,

                    )
                );
            }
        }

        $response['status'] = 'Success';
        $response['data'] = $arraylogs;
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

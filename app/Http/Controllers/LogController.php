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

    public static function save($log_data) {
        if(Auth::check()) {
            //Insert new transaction log
            $log_data['processor_id'] = Auth::user()->id;        
            $log_data['log_date'] = date('Y-m-d');        
            Log::insert($log_data);
        }
    }

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
                        $y = null;
                    }
                    else{
                        $month = $m;
                        $day = $d;
                        $y = $year;
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
                    if($csactive == 0){
                        $csstatus =  'Disabled';
                    }
                    $currentService = $cs->id;

                    $body = DB::table('logs')->where('client_service_id', $cs->id)
                    ->where('id','!=', $t->id)
                    ->orderBy('id', 'desc')
                    ->distinct('detail')
                    ->pluck('detail');
                    $body = $body;
                }
                else{
                    $csdetail = ucfirst($t->log_group);
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
                    'display_date' => Carbon::parse($t->log_date)->format('F d,Y'),
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

    public function getGroupTransactionLogs($client_id, $group_id) {  
        if($client_id == 0){
            $client_id = null;
        }

        $translogs = DB::table('logs')->where('client_id',$client_id)->where('group_id',$group_id)->where('log_type','Transaction')->orderBy('id','desc')->get();

        $arraylogs = [];
        $month = null;
        $day = null;
        $year = null;
        $currentBalance = app(GroupController::class)->getGroupTotalCollectables($group_id);
        $currentService = null;
        $currentDate = null;

        foreach($translogs as $t){
            $cs = ClientService::where('id',$t->client_service_id)->first();
            if(($t->log_group == 'service' && $cs->service_id != $currentService) || $t->log_group != 'service'){
                $body = "";
                $usr =  User::where('id',$t->processor_id)->select('id','first_name','last_name')->get();

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
                        $y = null;
                    }
                    else{
                        $month = $m;
                        $day = $d;
                        $y = $year;
                    }
                }
                else{
                    $year = $y;
                    $month = $m;
                    $day = $d;
                }
                $total_cost = 0;
                if($cs){
                    $csdetail = $cs->detail;
                    $cstracking =  $cs->tracking;
                    $csstatus =  $cs->status;
                    $csactive =  $cs->active;
                    if($csactive == 0){
                        $csstatus =  'Disabled';
                    }
                    $currentService = $cs->service_id;
                    $currentDate = $t->log_date;
                    $servs = DB::table('client_services')
                                ->where('service_id', $currentService)
                                ->where('group_id', $t->group_id)
                                ->where('created_at','LIKE', '%'.$t->log_date.'%')
                                ->orderBy('id','Desc')
                                ->get();
                    $servs_id = $servs->pluck('id');
                    $head = [];
                    $ctr = 0;
                    $total_cost = 0;
                    $total_disc = 0;
                    foreach($servs as $s){
                        $client = User::findorfail($s->client_id);
                        $head[$ctr]['status'] = $csstatus;
                        $head[$ctr]['id'] = $s->client_id;
                        $head[$ctr]['client'] = $client->first_name.' '.$client->last_name;
                        $head[$ctr]['details'] =  DB::table('logs')->where('client_service_id', $s->id)
                                    ->where('log_type', 'Transaction')
                                    ->orderBy('id', 'desc')
                                    ->distinct('detail')
                                    ->pluck('detail');

                        if($s->status == 'complete' && $s->active != 0){
                            $total_disc = DB::table('client_transactions')
                                    ->where('type', 'Discount')->where('group_id',$t->group_id)
                                    ->where('client_service_id', $s->id)
                                    ->sum('amount');
                            $total_cost += ($s->charge + $s->cost + $s->tip + $s->com_agent + $s->com_client) - $total_disc;
                        }            
                        $ctr++;
                    }
                    $t->amount = '-'.$total_cost;
                    
                    $body = '';
                }
                else{
                    $csdetail = ucfirst($t->log_group);
                    $cstracking = '';
                    $csstatus = '';
                    $csactive = 'none';
                    $head = [];
                    $head[0]['details'] = $t->detail;
                    $body = '';
                    //$currentService = null;
                }

                $arraylogs[] = array(
                    'month' => $m,
                    'day' => $d,
                    'year' => $y,
                    'display_date' => Carbon::parse($t->log_date)->format('F d,Y'),
                    'data' => array ( 
                        'id' => $t->id,
                        'head' => $head,
                        'body' => $body,
                        'total_cost' => ($total_cost > 0 ? $total_cost : 0),
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


public function getCommissionLogs($client_id, $group_id) {  
        if($client_id == 0){
            $client_id = null;
            $translogs = DB::table('logs')->where('client_id','!=',null)->where('group_id',$group_id)->where('log_type','Commission')->orderBy('id','desc')->get();
        }
        else{
            $translogs = DB::table('logs')->where('client_id',$client_id)->where('group_id','!=',null)->where('log_type','Commission')->orderBy('id','desc')->get();
        }


        $arraylogs = [];
        $month = null;
        $day = null;
        $year = null;
        $currentBalance = app(GroupController::class)->getGroupTotalCollectables($group_id);
        $currentService = null;
        $currentDate = null;

        foreach($translogs as $a){
            
           
                $totalClientCommission =  Log::select(DB::raw("SUM(amount) as total"))
                        ->where('log_type','Commission')
                        ->where('group_id',$a->group_id)
                        ->where('client_id',$a->client_id)
                        ->where('log_group',$a->log_group)
                        ->where('id','<=',$a->id)
                        ->first()->total;

                $client_last_record  =  Log::select('amount')->where('group_id',$a->group_id)
                        ->where('client_id',$a->client_id)
                        ->where('log_group',$a->log_group)
                        ->where('id','<',$a->id)
                        ->orderby('id', 'desc')->first();

                        //\Log::info($totalClientCommission);
            if($client_last_record['amount']==null){
                $client_last_record['amount'] = $totalClientCommission;
               
            }
            $usr =  User::where('id',$a->processor_id)->select('id','first_name','last_name')->get();
            $cdate = Carbon::parse($a->log_date)->format('M d Y');
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

            $arraylogs[] = array(
                'month' => $m,
                'day' => $d,
                'year' => $y,
                'data' => array ( 
                    'id' => $a->id,
                    'title' => $a->detail,
                    'balance' => $totalClientCommission,
                    'prevbalance' => floatval($totalClientCommission)-floatval($client_last_record['amount']),
                    'type' => $a->log_group,
                    'processor' => $usr[0]->first_name,
                    'date' => Carbon::parse($a->log_date)->format('F d,Y'),
                )
            );
        }


        $response['status'] = 'Success';
        $response['data'] = $arraylogs;
        $response['code'] = 200;

        return Response::json($response);
    }

    public function getActionLogs($client_id, $group_id) {  
        if($group_id == 0){
            $group_id = null;
        }  
        $translogs = DB::table('logs')->where('client_id',$client_id)->where('group_id',$group_id)->where('log_type','Action')->orderBy('id','desc')->get();

        if($group_id > 0){
            $translogs = DB::table('logs')->where('group_id',$group_id)->where('log_type','Action')->orderBy('id','desc')->get();
        }

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
                        $y = null;
                    }
                    else{
                        $month = $m;
                        $day = $d;
                        $y = $year;
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
                    if($csactive == 0){
                        $csstatus =  'Disabled';
                    }
                    $currentService = $cs->id;

                    $body = DB::table('logs')->where('client_service_id', $cs->id)
                    ->where('id','!=', $t->id)
                    ->orderBy('id', 'desc')
                    ->distinct('detail')
                    ->pluck('detail');
                    $body = $body;
                }
                else{
                    $csdetail = ucfirst($t->log_group);
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
                    'display_date' => Carbon::parse($t->log_date)->format('F d,Y'),
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

    public function getDocumentLogs($client_id) {
        $dates = DB::table('logs')->where('client_id', $client_id)
            ->where('group_id', null)
            ->where('log_type', 'Document')
            ->groupBy('log_date')
            ->orderBy('log_date', 'desc')
            ->pluck('log_date');

        $data = [];
        foreach( $dates as $date ) {
            $logs = Log::whereDate('log_date', '=', $date)
                ->where('client_id', $client_id)
                ->where('group_id', null)
                ->where('log_type', 'Document')
                ->orderBy('id', 'desc')
                ->get();

            $clientServicesIdArray = [];
            $lastDisplayDate = null;

            foreach( $logs as $log ) {
                $clientServiceId = $log->client_service_id;

                if( !in_array($clientServiceId, $clientServicesIdArray) ) {
                    $service = ClientService::select(['id', 'detail', 'status', 'tracking', 'active'])
                        ->with([
                            'logs' => function($query1) {
                                $query1->select(['id', 'client_service_id', 'detail', 'processor_id'])
                                    ->where('log_type', 'Document');
                            },
                            'logs.processor' => function($query2) {
                                $query2->select(['id', 'first_name', 'last_name']);
                            },
                            'logs.documents' => function($query3) {
                                $query3->select(['title']);
                            }
                        ])
                        ->findorfail($clientServiceId);

                    $displayDate = ($lastDisplayDate != $date) ? Carbon::parse($date)->format('F d, Y') : null;

                    $data[] = [
                        'display_date' => $displayDate,
                        'service' => $service
                    ];

                    $lastDisplayDate = $date;
                    $clientServicesIdArray[] = $clientServiceId;
                }
            }
        }

        $response['status'] = 'Success';
        $response['data'] = $data;
        $response['code'] = 200;

        return Response::json($response);
    }

}

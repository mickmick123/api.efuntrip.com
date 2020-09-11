<?php

namespace App\Http\Controllers;

use App\Log;

use App\Group;
use App\GroupUser;

use App\User;
use App\Document;
use App\Service;
use App\ClientService;

use App\OnHandDocument;

use App\ClientReport;
use App\ClientTransaction;

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
                    if($csactive == 0 && $csstatus != 'cancelled'){
                        $csstatus =  'Disabled';
                    }
                    $currentService = $cs->id;

                    $body = DB::table('logs as l')->select(DB::raw('l.detail, l.log_date, pr.first_name'))
                    ->where('client_service_id', $cs->id)->where('group_id',null)
                    ->where('l.id','!=', $t->id)
                    ->leftjoin(
                        DB::raw('
                            (
                                Select id,first_name, last_name
                                from users as u
                            ) as pr
                        '),
                        'pr.id', '=', 'l.processor_id'
                    )
                    ->where('l.id','!=', $t->id)
                    ->where('log_type','Transaction')
                    ->orderBy('l.id', 'desc')
                    //->distinct('detail')
                    ->get();
                    //\Log::info($body);

                    $data = collect($body->toArray())->flatten()->all();

                    $body = $data;
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
        // return 0;

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
                    $service = Service::findorfail($cs->service_id);
                    $detail_cn = ($service->detail_cn!='' ? $service->detail_cn : $service->detail);
                    $csdetail_cn = $detail_cn;
                    $cstracking =  $cs->tracking;
                    $csstatus =  $cs->status;
                    $csactive =  $cs->active;
                    if($csactive == 0 && $csstatus != 'cancelled'){
                        $csstatus =  'Disabled';
                    }
                    $currentService = $cs->service_id;
                    $currentDate = $t->log_date;
                    $servs = DB::table('client_services')
                                ->where('service_id', $currentService)
                                ->where('group_id', $t->group_id)
                                // ->where('created_at','LIKE', '%'.$t->log_date.'%')
                                ->orderBy('id','Desc')
                                ->get();

                    $servs_id = $servs->pluck('id');

                    // $body = DB::table('logs as l')->select(DB::raw('l.detail, l.log_date, pr.first_name'))
                    // ->where('client_service_id', $cs->id)->where('group_id',$t->group_id)
                    // ->where('l.id','!=', $t->id)
                    // ->leftjoin(
                    //     DB::raw('
                    //         (
                    //             Select id,first_name, last_name
                    //             from users as u
                    //         ) as pr
                    //     '),
                    //     'pr.id', '=', 'l.processor_id'
                    // )
                    // ->where('l.id','!=', $t->id)
                    // ->where('log_type','Transaction')
                    // ->orderBy('l.id', 'desc')
                    // //->distinct('detail')
                    // ->get();
                    // //\Log::info($body);

                    // $data = collect($body->toArray())->flatten()->all();

                    // $body = $data;

                    $head = [];
                    $ctr = 0;
                    $total_cost = 0;
                    $total_disc = 0;
                    foreach($servs as $s){
                        $client = User::findorfail($s->client_id);
                        $head[$ctr]['status'] = $csstatus;
                        $head[$ctr]['id'] = $s->client_id;
                        $head[$ctr]['client'] = $client->first_name.' '.$client->last_name;
                        $loghead =  DB::table('logs')->where('client_service_id', $s->id)->where('group_id',"!=",null)
                                    ->where('log_type', 'Transaction')
                                    ->orderBy('id', 'desc')
                                    ->distinct('detail')->get();
                                    // ->pluck('detail');

                        $head[$ctr]['details'] = $loghead->pluck('detail');
                        $head[$ctr]['details_cn'] = $loghead->pluck('detail_cn');

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
                    $csdetail_cn = ucfirst($t->log_group);
                    $cstracking = '';
                    $csstatus = '';
                    $csactive = 'none';
                    $head = [];
                    $head[0]['details'] = $t->detail;
                    $head[0]['details_cn'] = $t->detail_cn;
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
                        'title_cn' => $csdetail_cn,
                        'tracking' => $cstracking,
                        'status' => $csstatus,
                        'active' => $csactive,
                        'datetime' => $t->created_at,

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

       $documentLogs = DB::table('logs')->where('client_id', $client_id)
            ->select(DB::raw('id, label, log_type, processor_id, service_procedure_id, detail, detail_cn, label, amount, log_type, log_date, created_at'))
            ->where('log_type', 'Document')
            ->where('client_service_id',null)
            ->where('label', 'not like', "%Documents Needed%")
            ->where('label', 'not like', "%Prepare%")
            ->orderBy('id', 'desc')
            ->get();

        $data = [];
        foreach( $documentLogs as $log ) {

           $payload = [];

            $payload['documents'] = DB::table('documents as doc')
                  ->select(DB::raw('doc_log.pending_count, doc_log.count, doc_log.previous_on_hand, doc.title, doc.is_unique, doc.title_cn, doc.shorthand_name, doc_log.document_id'))
                  ->join('document_log as doc_log', 'doc_log.document_id', 'doc.id')
                  ->where('doc_log.log_id', $log->id)
                  ->get();

            $payload['processor']  = DB::table('users')
                  ->select(DB::raw('CONCAT(first_name, " ", last_name) as name'))
                  ->where('id', $log->processor_id)
                  ->get();

            $payload['procedure']  = DB::table('service_procedures')
                        ->select(DB::raw('documents_to_display, documents_mode, is_suggested_count'))
                        ->where('id', $log->service_procedure_id)
                        ->get();

            $displayDate = Carbon::parse($log->log_date)->format('F d, Y');


              $data[] = [
                      'display_date' => $displayDate,
                      'info' => $log,
                      'document_logs' =>$payload,
              ];


       }

        $response['status'] = 'Success';
        $response['data'] = $data;
        $response['code'] = 200;

        return Response::json($response);
    }



    public function getAllLogs($client_service_id) {
        $logs = Log::with('documents', 'serviceProcedure.action', 'serviceProcedure.category')->where('client_service_id',$client_service_id)->where('log_type','!=','Commission')
            ->orderBy('id','desc')->get();

        foreach( $logs as $log ) {
            $usr =  User::where('id',$log->processor_id)->select('first_name','last_name')->get();
            $log->processor = ($usr) ? ($usr[0]->first_name ." ".$usr[0]->last_name) : "";
            $log->detail =  ($log->detail !=='' && $log->detail !== null) ? $log->detail : '';
            $log->detail_cn =  ($log->detail_cn !=='' && $log->detail_cn !== null) ? $log->detail_cn : '';
        }

        $response['status'] = 'Success';
        $response['data'] = $logs;
        $response['code'] = 200;

        return Response::json($response);
    }


    public function getGroupDocumentLogs($group_id) {
        $groups = GroupUser::where('group_id', $group_id)->select('user_id')->get();
        $groupData = [];

        foreach($groups as $group) {
            $groupData[] = $group->user_id;
        }

        $documentLogs = DB::table('logs')->whereIn('client_id', $groupData)->where('log_type', 'Document')->select('log_date')->groupBy('log_date')->get();

        // $documentLogs = collect($documentLogs)->sortByDesc('log_date')->toArray();

        foreach($documentLogs as $docLog) {

            $docLog->display_date = Carbon::parse($docLog->log_date)->format('F d, Y');

            $logs = DB::table('logs')
                    ->whereIn('client_id', $groupData)
                    ->where('log_date', $docLog->log_date)
                    ->where('log_type', 'Document')
                    ->where('client_service_id',null)
                    ->where('label', 'not like', "%Documents Needed%")
                    ->where('label', 'not like', "%Prepare%")
                    ->orderBy('id', 'DESC')
                    ->get();
            
            // $logs = collect($logs)->sortByDesc('id')->toArray();

            


            // foreach($logs as $log) {
                // $clients = DB::table('logs')
                //         ->whereIn('client_id', $groupData)
                //         ->leftJoin('users', 'logs.client_id', '=', 'users.id')
                //         ->where('log_date', $docLog->log_date)
                //         ->where('log_type', 'Document')
                //         ->where('label', $log->label)
                //         // ->where('logs.created_at', $log->created_at)
                //         ->select('users.id', 'users.first_name', 'users.last_name')
                //         ->orderBy('logs.id', 'DESC')
                //         ->get();

                // $log->clients = $clients;
            // }

                
                foreach($logs as $log) {
                    $data = [];
                    $clients = DB::table('logs')
                            ->leftJoin('users', 'logs.client_id', '=', 'users.id')
                            ->where('logs.id', $log->id)
                            // ->where('logs.created_at', $log->created_at)
                            ->select('users.id', 'users.first_name', 'users.last_name')
                            ->orderBy('logs.id', 'DESC')
                            ->first();

                    $log->clients = $clients;

                    $payload = [];

                    $payload['documents'] = DB::table('documents as doc')
                        ->select(DB::raw('doc_log.pending_count, doc_log.count, doc_log.previous_on_hand, doc.title, doc.is_unique, doc.title_cn, doc.shorthand_name, doc_log.document_id'))
                        ->join('document_log as doc_log', 'doc_log.document_id', 'doc.id')
                        ->where('doc_log.log_id', $log->id)
                        ->get();
    
                    $payload['processor']  = DB::table('users')
                        ->select(DB::raw('CONCAT(first_name, " ", last_name) as name'))
                        ->where('id', $log->processor_id)
                        ->get();
    
                    $payload['procedure']  = DB::table('service_procedures')
                                ->select(DB::raw('documents_to_display, documents_mode, is_suggested_count'))
                                ->where('id', $log->service_procedure_id)
                                ->get();
    
                    $displayDate = Carbon::parse($log->log_date)->format('F d, Y');
    
    
                    // $data[] = [
                    //         'display_date' => $displayDate,
                    //         'info' => $log,
                    //         'document_logs' =>$payload,
                    // ];

                    $log->document_logs = $payload;
                }
            

            $docLog->logs = $logs;
            
            // $clients = DB::table('logs')
            //         ->leftJoin('users', 'logs.client_id', '=', 'users.id')
            //         ->where('log_type', 'Document')
            //         ->where('log_date', $docLog->log_date)
            //         ->whereIn('client_id', $groupData)
            //         ->select('users.id', 'users.first_name', 'users.last_name')
            //         ->orderBy('id', 'DESC')
            //         ->groupBy('client_id')
            //         ->get();

            // foreach($clients as $client) {
            //     $dl = DB::table('logs')
            //         ->where('log_type', 'Document')
            //         ->where('log_date', $docLog->log_date)
            //         ->where('client_id', $client->id)
            //         ->where('client_service_id',null)
            //         ->where('label', 'not like', "%Documents Needed%")
            //         ->where('label', 'not like', "%Prepare%")
            //         ->orderBy('id', 'DESC')
            //         ->get();

            //     $data = [];
            //     foreach($dl as $log) {
            //         $payload = [];

            //         $payload['documents'] = DB::table('documents as doc')
            //             ->select(DB::raw('doc_log.pending_count, doc_log.count, doc_log.previous_on_hand, doc.title, doc.is_unique, doc.title_cn, doc.shorthand_name, doc_log.document_id'))
            //             ->join('document_log as doc_log', 'doc_log.document_id', 'doc.id')
            //             ->where('doc_log.log_id', $log->id)
            //             ->get();
    
            //         $payload['processor']  = DB::table('users')
            //             ->select(DB::raw('CONCAT(first_name, " ", last_name) as name'))
            //             ->where('id', $log->processor_id)
            //             ->get();
    
            //         $payload['procedure']  = DB::table('service_procedures')
            //                     ->select(DB::raw('documents_to_display, documents_mode, is_suggested_count'))
            //                     ->where('id', $log->service_procedure_id)
            //                     ->get();
    
            //         $displayDate = Carbon::parse($log->log_date)->format('F d, Y');
    
    
            //         $data[] = [
            //                 'display_date' => $displayDate,
            //                 'info' => $log,
            //                 'document_logs' =>$payload,
            //         ];
            //     }

            //     $client->logs = $data;
            // }

            // $docLog->clients = $clients;
        }

        // foreach($groups as $group) {
        //     $client_id = $group->user_id;

        //     $client = User::where('id', $client_id)->select('first_name', 'last_name')->first();

        //     $documentLogs = DB::table('logs')->where('client_id', $client_id)
        //     ->select(DB::raw('id, label, log_type, processor_id, service_procedure_id, detail, detail_cn, label, amount, log_type, log_date, created_at'))
        //     ->where('log_type', 'Document')
        //     ->where('client_service_id',null)
        //     ->where('label', 'not like', "%Documents Needed%")
        //     ->where('label', 'not like', "%Prepare%")
        //     ->orderBy('id', 'desc')
        //     ->get();

        //     $onHandDocs = OnHandDocument::where('client_id', $client_id)
        //                 ->leftJoin('documents', 'on_hand_documents.document_id', '=', 'documents.id')
        //                 ->select('on_hand_documents.*', 'documents.title', 'documents.title_cn')
        //                 ->orderBy('on_hand_documents.id', 'DESC')
        //                 ->get();

        //     $data = [];
        //     foreach( $documentLogs as $log ) {

        //         $payload = [];

        //         $payload['documents'] = DB::table('documents as doc')
        //             ->select(DB::raw('doc_log.pending_count, doc_log.count, doc_log.previous_on_hand, doc.title, doc.is_unique, doc.title_cn, doc.shorthand_name, doc_log.document_id'))
        //             ->join('document_log as doc_log', 'doc_log.document_id', 'doc.id')
        //             ->where('doc_log.log_id', $log->id)
        //             ->get();

        //         $payload['processor']  = DB::table('users')
        //             ->select(DB::raw('CONCAT(first_name, " ", last_name) as name'))
        //             ->where('id', $log->processor_id)
        //             ->get();

        //         $payload['procedure']  = DB::table('service_procedures')
        //                     ->select(DB::raw('documents_to_display, documents_mode, is_suggested_count'))
        //                     ->where('id', $log->service_procedure_id)
        //                     ->get();

        //         $displayDate = Carbon::parse($log->log_date)->format('F d, Y');


        //         $data[] = [
        //                 'display_date' => $displayDate,
        //                 'info' => $log,
        //                 'document_logs' =>$payload,
        //         ];


        //     }

        //     $group->client = $client;
        //     $group->onHandDocuments = $onHandDocs;
        //     $group->logs = $data;
        // }
        

        $response['status'] = 'Success';
        $response['data'] = $documentLogs;
        $response['code'] = 200;

        return Response::json($response);
    }

    public function getGroupDocsOnHand($group_id) {
        $groups = GroupUser::where('group_id', $group_id)->orderBy('user_id', 'DESC')->get();

        foreach($groups as $group) {
            $client_id = $group->user_id;

            $client = User::where('id', $client_id)->select('first_name', 'last_name')->first();

            $onHandDocs = OnHandDocument::where('client_id', $client_id)
                        ->leftJoin('documents', 'on_hand_documents.document_id', '=', 'documents.id')
                        ->select('on_hand_documents.*', 'documents.title', 'documents.title_cn')
                        ->orderBy('on_hand_documents.id', 'DESC')
                        ->get();

            $group->client = $client;
            $group->onHandDocuments = $onHandDocs;
        }

        $response['status'] = 'Success';
        $response['data'] = $groups;
        $response['code'] = 200;

        return Response::json($response);
    }


    // OLD LOGS //

    public function getOldTransactionLogs($client_id, $group_id){
        $arraylogs = [];

        if($group_id == 0 || $group_id == null){
            $transtotal = DB::table('logs')->where('client_id',$client_id)->where('group_id',null)->where('log_type','Transaction')->sum('amount');

            $transaction =  DB::table('old_logs_transaction')->where('client_id', $client_id)->where('group_id',0)->where('display',0)->orderBy('id', 'desc')->get();

            $arraylogs = [];
            $month = null;
            $day = null;
            $year = null;
            $currentBalance = app(ClientController::class)->getClientTotalCollectables($client_id)-$transtotal;
            foreach($transaction as $a){
                $usr =  User::where('id',$a->user_id)->select('id','first_name','last_name')->limit(1)->get()->makeHidden(['full_name', 'avatar', 'permissions', 'access_control', 'binded', 'unread_notif', 'group_binded', 'document_receive', 'is_leader', 'total_points', 'total_deposit', 'total_discount', 'total_refund', 'total_payment', 'total_cost', 'total_complete_cost', 'total_balance', 'collectable', 'branch', 'three_days']);

                $cs = ClientService::where('id',$a->service_id)->first();

                $a->balance = $currentBalance;
                $currentBalance -= $a->amount;
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

                if($cs){
                    $csdetail = $cs->detail;
                    $cstracking =  $cs->tracking;
                    $csstatus =  $cs->status;
                    $csactive =  $cs->active;
                }
                else{
                    $csdetail = ucfirst($a->type);
                    $cstracking = '';
                    $csstatus = '';
                    $csactive = 'none';
                }

                $arraylogs[] = array(
                    'month' => $m,
                    'day' => $d,
                    'year' => $y,
                    'data' => array (
                        'id' => $a->id,
                        'title' => $a->detail,
                        'balance' => $a->balance,
                        'prevbalance' => $currentBalance,
                        'amount' => $a->amount,
                        'type' => $a->type,
                        'processor' => $usr[0]->first_name,
                        'old_detail' => $a->old_detail,
                        'date' => Carbon::parse($a->log_date)->format('F d,Y'),
                        'service_name' => $csdetail,
                        'tracking' => $cstracking,
                        'status' => $csstatus,
                        'active' => $csactive,

                    )
                );
            }
        }
        else{
            return $this->groupOldTransactionLogs($group_id, 0);
        }

        return json_encode($arraylogs);
    }


    public function groupOldTransactionLogs($groupId, $limit = 0) {
        $transtotal = DB::table('logs')->where('group_id',$groupId)->where('log_type','Transaction')->sum('amount');

        $transaction =  DB::table('old_logs_transaction')->where('group_id', $groupId)
                        ->where('display',0)
                        ->orderBy('id', 'desc')->get();
        if($limit > 0){
            $transaction =  DB::table('old_logs_transaction')->where('group_id', $groupId)
                            ->where('display',0)
                            ->orderBy('id', 'desc')->limit($limit)->get();
        }
        $arraylogs = [];
        $month = null;
        $day = null;
        $year = null;
        $currentBalance = app(GroupController::class)->getGroupTotalCollectables($groupId) - $transtotal;
        foreach($transaction as $a){

            $usr =  User::where('id',$a->user_id)->select('id','first_name','last_name')->limit(1)->get()->makeHidden(['full_name', 'avatar', 'permissions', 'access_control', 'binded', 'unread_notif', 'group_binded', 'document_receive', 'is_leader', 'total_points', 'total_deposit', 'total_discount', 'total_refund', 'total_payment', 'total_cost', 'total_complete_cost', 'total_balance', 'collectable', 'branch', 'three_days']);

            $cs = ClientService::where('id',$a->service_id)->first();

            $a->balance = $currentBalance;
            $currentBalance -= $a->amount;
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

            if($cs){
                $csdetail = $cs->detail;
                $cstracking =  $cs->tracking;
                $csstatus =  $cs->status;
                $csactive =  $cs->active;
            }
            else{
                $csdetail = ucfirst($a->type);
                $cstracking = '';
                $csstatus = '';
                $csactive = 'none';
            }

            $arraylogs[] = array(
                'month' => $m,
                'day' => $d,
                'year' => $y,
                'data' => array (
                    'id' => $a->id,
                    'title' => $a->detail,
                    'balance' => $a->balance,
                    'prevbalance' => $currentBalance,
                    'amount' => $a->amount,
                    'type' => $a->type,
                    'processor' => $usr[0]->first_name,
                    'old_detail' => $a->old_detail,
                    'date' => Carbon::parse($a->log_date)->format('F d,Y'),
                    'service_name' => $csdetail,
                    'tracking' => $cstracking,
                    'status' => $csstatus,
                    'active' => $csactive,
                    'total' => $a->total,
                )
            );
        }
        return $arraylogs;
    }


    public function getTransactionHistory($client_id, $group_id) {
        if($group_id == 0){
            $group_id = null;
        }
        else{
            return $this->groupTransactionHistory($group_id);
        }

        $translogs = DB::table('logs')->where('client_id',$client_id)->where('group_id',$group_id)->where('log_type','Ewallet')->orderBy('id','desc')->get();

        $arraylogs = [];
        $month = null;
        $day = null;
        $year = null;
        $currentBalance = app(ClientController::class)->getClientEwallet($client_id);
        $currentService = null;

        foreach($translogs as $t){
            if(($t->log_group == 'payment' && $t->client_service_id != $currentService) || $t->log_group != 'payment'){
                $body = "";
                $usr =  User::where('id',$t->processor_id)->select('id','first_name','last_name')->get();

                $cs = ClientService::where('id',$t->client_service_id)
                        ->first();

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
                    $cst = $cs->cost + $cs->tip + $cs->charge + $cs->com_client + $cs->com_agent;
                    $disc = ClientTransaction::where('client_service_id', $cs->id)->where('type','Discount')->first();
                    if($disc){
                        $cst -=$disc->amount;
                    }

                    $csdetail = $cs->detail.' <b style="color: red; margin-left: 25px;">Price : Php'.$cst.' , Balance : Php'.($cst - $cs->payment_amount).'</b>';
                    $cstracking =  $cs->tracking;
                    $csstatus =  $cs->status;
                    $csactive =  $cs->active;
                    if($csactive == 0 && $csstatus != 'cancelled'){
                        $csstatus =  'Disabled';
                    }
                    $currentService = $cs->id;

                    $body = DB::table('logs as l')->select(DB::raw('l.detail, l.log_date, pr.first_name, l.amount'))
                    ->where('client_service_id', $cs->id)->where('group_id',null)
                    ->where('l.id','!=', $t->id)
                    ->leftjoin(
                        DB::raw('
                            (
                                Select id,first_name, last_name
                                from users as u
                            ) as pr
                        '),
                        'pr.id', '=', 'l.processor_id'
                    )
                    ->where('l.id','!=', $t->id)
                    ->where('log_type','Ewallet')
                    ->orderBy('l.id', 'desc')
                    //->distinct('detail')
                    ->get();

                    $t->amount = DB::table('logs as l')
                                    ->where('client_service_id', $cs->id)->where('group_id',null)
                                    ->where('log_type','Ewallet')->sum('amount');

                    $data = collect($body->toArray())->flatten()->all();

                    $body = $data;
                    $csshow = 1;
                    // if($cs->active == 0 || $cs->status == 'cancelled'){
                    //     $csshow = 0;
                    // }

                }
                else{
                    $csdetail = ucfirst($t->log_group);
                    $cstracking = '';
                    $csstatus = '';
                    $csactive = 'none';
                    $body = '';
                    $csshow = 1;
                    //$currentService = null;
                }

                if($csshow){
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
        }

        $response['status'] = 'Success';
        $response['data'] = $arraylogs;
        $response['code'] = 200;

        return Response::json($response);
    }

    public function groupTransactionHistory($group_id) {

        $translogs = DB::table('logs')->where('group_id',$group_id)->where('log_type','Ewallet')->orderBy('id','desc')->get();

        $arraylogs = [];
        $month = null;
        $day = null;
        $year = null;
        $currentBalance = app(GroupController::class)->getGroupEwallet($group_id);
        $currentService = null;
        $currentLabel = null;

        foreach($translogs as $t){
            if(($t->log_group == 'payment' && $t->client_service_id != $currentService && $t->label != $currentLabel) || $t->log_group != 'payment'){
                $body = "";
                $usr =  User::where('id',$t->processor_id)->select('id','first_name','last_name')->get();

                $cs = ClientService::where('id',$t->client_service_id)
                        ->first();

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
                    if($t->label == null){
                        $cst = $cs->cost + $cs->tip + $cs->charge + $cs->com_client + $cs->com_agent;
                        $disc = ClientTransaction::where('client_service_id', $cs->id)->where('type','Discount')->first();
                        if($disc){
                            $cst -=$disc->amount;
                        }

                        $csdetail = $cs->detail.' <b style="color: red; margin-left: 25px;">Price : Php'.$cst.' , Balance : Php'.($cst - $cs->payment_amount).'</b>';
                        $cstracking =  $cs->tracking;
                        $csstatus =  $cs->status;
                        $csactive =  $cs->active;
                        if($csactive == 0 && $csstatus != 'cancelled'){
                            $csstatus =  'Disabled';
                        }


                        $currentService = $cs->id;
                        $currentLabel = $t->label;

                        $body = DB::table('logs as l')->select(DB::raw('l.detail, l.log_date, pr.first_name, l.amount'))
                        ->where('client_service_id', $cs->id)->where('group_id',$group_id)
                        ->where('l.id','!=', $t->id)
                        ->leftjoin(
                            DB::raw('
                                (
                                    Select id,first_name, last_name
                                    from users as u
                                ) as pr
                            '),
                            'pr.id', '=', 'l.processor_id'
                        )
                        ->where('log_type','Ewallet')
                        ->orderBy('l.id', 'desc')
                        //->distinct('detail')
                        ->get();

                        $t->amount = DB::table('logs as l')
                                        ->where('client_service_id', $cs->id)->where('group_id',$group_id)
                                        ->sum('amount');

                        $data = collect($body->toArray())->flatten()->all();

                        $body = $data;
                    }
                    else if($t->label != null && $currentLabel != $t->label){
                        $csdetail = $t->label;
                        $translogs = DB::table('logs')->where('group_id',$group_id)->where('log_type','Ewallet')->where('label',$t->label)->orderBy('id','desc')->get();

                        $cs_ids = $translogs->pluck('client_service_id');

                        $t->amount = DB::table('logs as l')
                                        ->where('label', $t->label)->where('group_id',$group_id)
                                        ->sum('amount');
                        $t->detail = "Total payment Php".abs($t->amount);

                        $body = $translogs;

                        $cstracking =  null;
                        $csstatus =  null;
                        $csactive =  null;
                        $currentLabel = $t->label;
                        $currentService = $cs->id;

                    }
                    else{
                        $cs->active = 0;
                    }

                    $csshow = 1;
                    // if($cs->active == 0 || $cs->status == 'cancelled'){
                    //     $csshow = 0;
                    // }

                }
                else{
                    $csdetail = ucfirst($t->log_group);
                    $cstracking = '';
                    $csstatus = '';
                    $csactive = 'none';
                    $body = '';
                    $csshow = 1;
                    //$currentService = null;
                }

                $t->balance = $currentBalance;

                $currentBalance -= ($t->amount);

                if($csshow){
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
                            'date' => Carbon::parse($t->created_at)->format('F d,Y h:i:s'),
                            'title' => $csdetail,
                            'tracking' => $cstracking,
                            'status' => $csstatus,
                            'active' => $csactive,

                        )
                    );
                }

            }
        }

        $response['status'] = 'Success';
        $response['data'] = $arraylogs;
        $response['code'] = 200;

        return Response::json($response);
    }


}

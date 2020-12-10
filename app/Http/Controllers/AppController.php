<?php

namespace App\Http\Controllers;

use App\User;

use App\ContactNumber;

use App\Device;

use App\ClientEWallet;
use App\Financing;
use App\ClientService;
use App\ClientTransaction;
use App\Client;
use App\Group;
use App\GroupUser;
use App\QrCode;
use App\Log;
use App\Http\Controllers\LogController;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Redirect;

use Response;
use Validator;
use Hash, DB;
use Carbon\Carbon;

use GuzzleHttp\Client as ClientGuzzle;
use phpseclib\Crypt\RSA;
use Illuminate\Support\Facades\Auth;

class AppController extends Controller
{

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
            'device_token' => 'required',
            'device_id' => 'required',
            'device_type' => 'required',
        ]);

        $login = $request->username;
        $result = filter_var( $login, FILTER_VALIDATE_EMAIL);

        if(!$result){
           preg_match_all('!\d+!', $login, $matches);
           $login = implode("", $matches[0]);
           $login = ltrim($login,"0");
           $login = ltrim($login,'+');
           $login = ltrim($login,'63');

            if(is_numeric($login)){

                $ids = ContactNumber::where('number','like','%'.$login)->where('user_id','!=',null)->pluck('user_id');
                $user = User::whereIn('id', $ids)->get();
            }else{
                $user = NULL;
            }

        }
        else{
            $user = User::where('email', $login)->get();
        }

        $response = [];

        if( $validator->fails() ) {
            $response['status'] = 'Failed';
            $response['desc'] = $validator->errors();
            $httpStatusCode = 200; // Request Error
        }
        else{
            if($user) {
                foreach($user as $u){
                    $password = $request->password;
                    preg_match_all('!\d+!', $password, $matches);
                    $password = implode("", $matches[0]);
                    $password = ltrim($password,"0");
                    $password = ltrim($password,'+');
                    $password = ltrim($password,'63');
                    if($login === $password){
                        $password = '+63'.$password;
                    }else{
                        $password = $request->password;
                    }
                    if (Hash::check($password, $u->password)) {
                        $client = User::findorFail($u->id)->makeVisible('access_token');

                        Device::updateOrCreate(
                            ['user_id' => $client->id, 'device_type' => $request->device_type, 'device_token' => $request->device_token],
                            []
                        );
                        $token = $client->createToken('WYC Visa')->accessToken;

                        $is_new_user = 0;

                        $cnum = ContactNumber::where('user_id',$client->id)->where('is_primary',1)->first();
                        // $cnum = $user->contact_number;
                        if($cnum){
                            //get contact number
                            $cnum = $cnum->number;

                            $cnum = ltrim($cnum,"0");
                            $cnum = ltrim($cnum,'+');
                            $cnum = ltrim($cnum,'63');
                            $cnum = "+63".$cnum;
                            if (Hash::check($cnum,  $u->password)) {
                                $is_new_user = 1;
                            }
                        }

                        $numbers =  ContactNumber::where('user_id',$client->id)->get();

                        //response
                        $response['id'] = $client->id;
                        $response['email'] = $client->email;
                        $response['numbers'] = $numbers;
                        $response['token'] = $request->device_token;
                        $response['device_id'] = $request->device_id;
                        $response['device_type'] = $request->device_type;
                        $response['active'] = 1;
                        $response['access_token'] = $token;
                        $response['is_new_user'] = $is_new_user;

                        $admin = 0;
                        if($client->hasRole('cpanel-admin') || $client->hasRole('master') || $client->hasRole('employee')){
                            $admin = 1;
                        }
                        $vclient =0;
                        if($client->hasRole('visa-client')){
                            $vclient = 1;
                        }
                        $response['admin'] = $admin;
                        $response['client'] = $vclient;
                        $response['roles'] = $client->rolesname->pluck('name');
                        $response['status'] = 'Success';
                        $response['code'] = 200;

                        return Response::json($response);
                    }
                }
                    $response['status'] = 'Failed';
                    $response['desc'] = 'Invalid Username and Password';
                    $response['code'] = 422;

            } else {
                $response['status'] = 'Failed';
                $response['desc'] = 'Client authentication failed';
                $response['code'] = 422;
            }
        }
        return Response::json($response);

    }

    public function verifyUsername(Request $request) {

        $validator = Validator::make($request->all(), [
            'username' => 'required',

        ]);
        $login = $request->username;
        $result = filter_var( $login, FILTER_VALIDATE_EMAIL );

        if(!$result){

            preg_match_all('!\d+!', $login, $matches);
            $login = implode("", $matches[0]);
            $login = ltrim($login,"0");
            $login = ltrim($login,'+');
            $login = ltrim($login,'63');

            if(is_numeric($login)){
                $clients = ContactNumber::where('is_primary',1)->where('number','like', '%'.$login)->where('user_id','!=',null)->pluck('user_id');
                $binded = User::where('password','!=','')->whereIn('id', $clients)->get();
            }else{
                $binded = NULL;
            }
        }
        else{
            $binded = User::where('password','!=','')->where('email', $login)->get();
        }

        $response = [];

        if( $validator->fails() ) {
            $response['status'] = 'Failed';
            $response['desc'] = $validator->errors();
        }
        else{
            $response['total_bind'] = count($binded);
        }
        return Response::json($response);

    }

    public function checkClient(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required',
            'bday' => 'required',
        ]);

        if( $validator->fails() ) {
            $response['status'] = 'Failed';
            $response['desc'] = $validator->errors();
            $response['code'] = 400;
            return Response::json($response);
        }
        else{
            $fname = $request['first_name'];
            $lname = $request['last_name'];
            $gender = $request['gender'];
            $bday = $request['bday'];

                $client = User::where(function($q) use($fname,$lname){
                                    $q->where('first_name', $fname)->where('last_name', $lname);
                                    $q->orwhere('last_name', $fname)->where('first_name', $lname);
                                })
                            ->where('birth_date',$bday)
                            ->where('gender',$gender)
                            ->select('id')
                            ->get();

                if(count($client)<1){
                    $client = User::where(function($q) use($fname,$lname){
                                    $q->where('first_name', $fname)->where('last_name', $lname);
                                    $q->orwhere('last_name', $fname)->where('first_name', $lname);
                                })
                            ->where('birth_date',$bday)
                            ->select('id')
                            ->get();
                    if(count($client)<1){
                        $client = User::where(function($q) use($fname,$lname){
                                    $q->where('first_name', $fname)->where('last_name', $lname);
                                    $q->orwhere('last_name', $fname)->where('first_name', $lname);
                                })
                            ->where('gender',$gender)
                            ->select('id')
                            ->get();
                        if(count($client)<1){
                            return 0;
                        }
                    }

                }
                if(count($client)>1){
                    $data['warning'] = "Multiple users are using the details you entered.";
                    $data['users_found'] = array_pluck($client, 'id');
                    return Response::json($data);
                }
                else{
                    $ids= $client->pluck('id');
                    $client = $client->first();
                    $client = User::findorFail($client->id);
                    $data['client_id'] = $client->id;

                    $checkIfLeader = Group::where('leader_id',$client->id)->first();
                    $leaderctr = ($checkIfLeader ? 1 : 0);
                    if($leaderctr == 0){
                        $checkIfLeader = GroupUser::where('user_id',$client->id)->where('is_vice_leader',1)->first();
                        $leaderctr = ($checkIfLeader ? 1 : 0);
                    }
                    $data['is_leader'] = $leaderctr;

                    $checkIfBind = User::whereIn('id',$ids)->where('password','!=','')->first();
                    $bindctr = ($checkIfBind ? 1 : 0);
                    $data['is_bind'] = $bindctr;

                    $checkIfDetailsBind = User::where('id',$client->id)->whereIn('id',$ids)->where('password','!=','')->first();
                    $bindetctr = ($checkIfDetailsBind ? 1 : 0);
                    $data['details_bind'] = $bindetctr;

                    //getting of client info if bind
                    if($checkIfBind){
                        $userBind = User::where('id',$checkIfBind->id)->select('id','first_name','middle_name','last_name')->first();
                        $data['user_bind'] = $userBind;
                    }
                    else{
                        $data['user_bind'] = '';
                    }

                    //check empty details
                    $emp = null;
                    if($client->passport == null || $client->passport == '' || $client->passport == 'n/a' || $client->passport == 'N/A'){
                        $emp.="passport ";
                    }
                    if($client->height == null || $client->height == '' || $client->height == 'n/a' || $client->height == 'N/A'){
                        $emp.="height ";
                    }
                    if($client->weight == null || $client->weight == '' || $client->weight == 'n/a' || $client->weight == 'N/A'){
                        $emp.="weight ";
                    }
                    if($client->civil_status == null || $client->civil_status == '' || $client->civil_status == 'n/a' || $client->civil_status == 'N/A'){
                        $emp.="civil_status ";
                    }

                    if($client->address == NULL || $client->address == ''){
                        $emp.="local_address ";
                    }

                    if($emp == null){
                        $data['empty'] = [];
                    }
                    else{
                        $emp = trim($emp);
                        $data['empty'] = explode(" ",$emp);
                    }
                    $sv = 0;
                    if($client->birth_date == null){
                        $client->birth_date = $bday;
                        $sv++;
                    }
                    if($client->gender == null){
                        $client->gender = $gender;
                        $sv++;
                    }
                    if($sv>0){
                        $client->save();
                    }
                }
        }

        return Response::json($data);
    }

    public function checkPassport(Request $request) {
        $validator = Validator::make($request->all(), [
            'users_found' => 'required',
            'client_passport' => 'required',
        ]);

        if( $validator->fails() ) {
            $response['status'] = 'Failed';
            $response['desc'] = $validator->errors();
            $response['code'] = 400; // Request Error
            return Response::json($response);
        }
        else{
            $passport = $request['client_passport'];
            $clients = str_replace(array( '[', ']' ), '', $request['users_found']);
            $users_found = explode(',', $clients);
            $passUser = User::whereIn('id',$users_found)
                        ->where('passport',$passport)->first();

        if($passUser){
            $checkIfLeader = Group::where('leader_id',$passUser->id)->first();
            $leaderctr = ($checkIfLeader ? 1 : 0);
            if($leaderctr == 0){
                $checkIfLeader = GroupUser::where('user_id',$passUser->id)->where('is_vice_leader',1)->first();
                $leaderctr = ($checkIfLeader ? 1 : 0);
            }
            $data['client_id'] = $passUser->id;
            $data['is_leader'] = $leaderctr;
            $data['status'] = 'Success';
            $data['code'] = 200;
            return Response::json($data);
        }

        return 0;
        }

    }

    public function payQRCode($qr_id) {
        $key = "-----BEGIN PRIVATE KEY-----
        MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAIgDKJpeEZSWgP4B
        lkxnhoFKQ7K9jj9BrG41r4G1UzZQ0687wJtPckbUa/RZxveUS2H/32Uc7QskHZeB
        EbILzfopppeNbWbXkFnLd9kf5m3TbeBoOjcsVkdBgPFiycbWOglP8ZuWnknXNxgM
        rTOLph0AIX9auHTciwMV6tDygYhHAgMBAAECgYBmYAJC1wVikzo6dpVboxzR2kVE
        l3snT9ZrCgu1lPcyTfpXzqD2BgGdIKy1OpIRrlRjSkYrBG/D0AZaEDNykYISbBD7
        7xZa9aRluzs1LdCBaHUqDBZhlO/sQb/rYSLy5qSBCZr97rTr0zDk2TsNc1TUPjfM
        hWo7KQKW3HwTNF+kEQJBANM0D5aQxqxeDZczAKl6PEevWlSTOkqeg4LTAa7uVo//
        mdQF215yT26rM9JOTK3O/81G5u/ceDWeCv9tlEH8Hr8CQQCk3F5lFp/4twauMIf5
        7LgZFiCk00CERpEsZzdkXElxPOmggfusblSH+0l7hvewfI1A0v5whcNfaJD8Gvpa
        aQB5AkAfjMFfXpUvHoWtNoM8zfO/SaSWyb+FchR3MIop1ZS8whP6pj1U6IKRJ6YA
        Ho450JhJ0/OflTGn4MoHyhjBmqYFAkA9Zto9ekzAnKJ/VBIA8rqqlUQ5P3kjCwlc
        6WCHH5w28cHuBxuOYFVZhC0dNeqgr/MINs2PaTKYIWEGlKGz9LG5AkAkRhc9Mc0g
        qoEckJEUTfxm9d4jbxvnAfRRpoUyfRztIqv6nbwGPzV/UFS6wePJcx2nEdDpVSkL
        J00u3dHegsxq
        -----END PRIVATE KEY-----";

        $pubkey = "-----BEGIN PUBLIC KEY-----
        MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCqQp5pVXe5Gk5xU5X6VQ/Dr+TN
        GIOojTlg8Aon6SsFliZb20uGhcDfi4psQR+Tyir6Qdvnrsga6YQJS9E7g/DRspIW
        s5n8yErWKdqOJDgF77IW5mzhlQyNioIhDsYSytD3ef9nlwcPmFVUI7lOEtMP9xAB
        1WiWy2H5Ylass16/mwIDAQAB
        -----END PUBLIC KEY-----";

        $qr = QrCode::findorFail($qr_id);
        $service_ids = explode(',',$qr->service_ids);
        $total_amount = 0;
        foreach($service_ids as $id){
            $amt = 0;
            $cs = ClientService::findorFail($id);
            $discount =  ClientTransaction::where('client_service_id', $id)->where('type', 'Discount')->sum('amount');
            $amt = ($cs->charge + $cs->cost + $cs->tip + $cs->com_client + $cs->com_agent) - $discount;
            if($cs->payment_amount != 0){
                $amt -= $cs->payment_amount;
            }
            $total_amount += $amt;

        }
        $total_amount = $total_amount / 0.975;
        $total_amount = round($total_amount, 2);
        $timestamp = (time())*1000;
        $notifyUrl = URL::to('/').'/api/v1/app/update-service-payment/'.$qr_id;
        $data = array (
            "appId"  => "160152699158911",
            "mchId" => "698",
            // "notifyUrl" => (string)$notifyUrl,
            "returnUrl" => (string)$notifyUrl,
            "outTradeNo" => (string)$qr_id,
            "timestamp" => (string)$timestamp,
            "subject" => "Service Payment",
            "amount" => (string)$total_amount,
            "payment" => "qrcode",
            "ip" => "127.0.0.1",
            "timeOutMini" => "30"
        );

        $rsa = new RSA();

        ksort($data);
        $content = urldecode(http_build_query($data));
        // return $content;
        $rsa->loadKey($key);
        $rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);
        $signature = $rsa->sign($content); // Sign Data
        $ApiSignature = base64_encode($signature);

        $data['sign'] = $ApiSignature;

        try {
            $client = new ClientGuzzle([
                'Content-Type' => 'application/json'
            ]);

            $r = $client->request('POST',
                'https://openapi.juancash.com/pay',
                [
                    'json' => $data
                ]
            );
            // return $r;
            $r = json_decode($r->getBody(), true);
            // \Log::info($r);
            return Redirect::to($r['data']['content']);
        }
        catch (\Exception $ex) {

        }
    }

    public function updateServicePayment($qr_id) {
        $qr = QrCode::findorFail($qr_id);
        $service_ids = explode(',',$qr->service_ids);

        $amount = 0;
        $name = '';
        $group_id = null;
        $client_id = null;
        $type = '';
        if($qr->group_id != null){
            $name = Group::where('id',$qr->group_id)->first()->name;
            $group_id = $qr->group_id;
            $type = 'Group';
        }
        else{
            $name = User::where('id',$qr->client_id)->first();
            $name = $name->first_name.' '.$name->last_name;
            $client_id = $qr->client_id;
            $type = 'Client';
        }
        //collect total amount
        foreach($service_ids as $id){
            $cs = ClientService::findorFail($id);
            $discount =  ClientTransaction::where('client_service_id', $id)->where('type', 'Discount')->sum('amount');
            $amt = ($cs->charge + $cs->cost + $cs->tip + $cs->com_client + $cs->com_agent) - $discount;
            if($cs->is_full_payment == 1){
                if($amt == $cs->payment_amount){
                    $amt = 0;
                }
                else{
                    $amt -= $cs->payment_amount;
                }
            }
            $amount+=$amt;
        }

    if($amount > 0){
        $dp = new ClientEWallet;
        $dp->client_id = ($client_id == null ? 0 : $client_id);
        $dp->type = 'Deposit';
        $dp->amount = $amount;
        $dp->group_id = $group_id;
        // $dp->reason = "Generating DP";
        $dp->save();

        $total_amount = $amount / 0.975;
        $total_amount = round($total_amount, 2);

        $finance = new Financing;
        $finance->user_sn = 0;
        $finance->type = "deposit";
        $finance->record_id = $dp->id;
        $finance->cat_type = "other";
        $finance->cat_storage = 'bank';
        $finance->branch_id = 1;
        $finance->storage_type = 'juancash';
        $finance->trans_desc = 'Received juancash payment Php'.$total_amount.' from '.$type.' '.$name;
        $finance->bank_client_depo_payment = $amount;
        $finance->deposit_other = $total_amount - $amount;
        $finance->save();

        $detail = 'Receive juancash payment with an amount of Php'.$total_amount.'.';
        $detail_cn = '预存了款项 Php'.$total_amount.'.';
        $log_data = array(
            'client_service_id' => null,
            'client_id' => $client_id,
            'group_id' => $group_id,
            'log_type' => 'Ewallet',
            'log_group' => 'deposit',
            'detail'=> $detail,
            'detail_cn'=> $detail_cn,
            'amount'=> $total_amount,
        );

        LogController::save($log_data);

        $total = 0;
        foreach($service_ids as $id){
            $cs = ClientService::findorFail($id);
            $cs_client_id = $cs->client_id;
            $discount =  ClientTransaction::where('client_service_id', $id)->where('type', 'Discount')->sum('amount');
            $amt = ($cs->charge + $cs->cost + $cs->tip + $cs->com_client + $cs->com_agent) - $discount;
            if($cs->payment_amount != 0){
                $amt -= $cs->payment_amount;
            }

            $payment = ClientTransaction::where('type','Payment')->where('client_service_id',$id)->first();
             $rson = 'Paid Php'.$amount.' via Juancash ('.date('Y-m-d H:i:s').')<br><br>';
             if($payment){
                 $payment->amount += $amt;
                 $payment->reason = $rson.$payment->reason;
                 $payment->save();
             }
             else{
                 $payment = new ClientTransaction;
                 $payment->client_id = $client_id;
                 $payment->client_service_id = $id;
                 $payment->type = 'Payment';
                 $payment->group_id = $group_id;
                 $payment->amount = $amt;
                 $payment->reason = $rson;
                 $payment->save();
             }

            $cs->payment_amount = $amt;
            $cs->is_full_payment = 1;
            $cs->save();

            // save transaction logs
             $detail = 'Paid an amount of Php '.$amt.'.';
             $detail_cn = '已支付 Php'.$amt.'.';
             $log_data = array(
                 'client_service_id' => null,
                 'client_id' => $client_id,
                 'group_id' => $group_id,
                 'log_type' => 'Transaction',
                 'log_group' => 'payment',
                 'detail'=> $detail,
                 'detail_cn'=> $detail_cn,
                 'amount'=> $amt,
             );
             LogController::save($log_data);

             if($client_id != null){            
                $detail = 'Paid service with an amount of Php'.$amt.'.';
                $detail_cn = '已支付 Php'.$amt.'.';
                $log_data = array(
                    'client_service_id' => $id,
                    'client_id' => $client_id,
                    'group_id' => null,
                    'log_type' => 'Ewallet',
                    'log_group' => 'payment',
                    'detail'=> $detail,
                    'detail_cn'=> $detail_cn,
                    'amount'=> '-'.$amt,
                );
                LogController::save($log_data);
             }
             else{
                $label = null;
                $cl = User::findOrFail($cs_client_id);
                $datenow = (Carbon::now())->format('M d, Y H:i:s');

                $label = 'Payment Date : '.$datenow;
                $detail = '<br><div class="el-col el-col-11" style="padding-left: 10px; padding-right: 10px;"><b>'.$cs->detail.'</b></div>
                               <div class="el-col el-col-8" style="padding-left: 10px; padding-right: 10px;"><b>['.$cs_client_id.']'.$cl->first_name.' '.$cl->last_name.' : </b> Paid service Php'.$amt.'. </div>';
                $detail_cn = $detail;

                $checkLog = Log::where('log_type','Ewallet')->where('log_group','payment')
                        ->where('client_service_id',$id)->first();

                 if($checkLog){
                    $label = $checkLog->label;
                    $detail = '<div class="el-col el-col-11" style="padding-left: 10px; padding-right: 10px;"><b>'.'&nbsp;'.'</b></div>
                               <div class="el-col el-col-8" style="padding-left: 10px; padding-right: 10px;"><b>['.$cs_client_id.']'.$cl->first_name.' '.$cl->last_name.' : </b> Paid service Php'.$amt.'. </div>';
                    $detail_cn = $detail;
                 }

                 $log_data = array(
                     'client_service_id' => $id,
                     'client_id' => null,
                     'group_id' => $group_id,
                     'log_type' => 'Ewallet',
                     'log_group' => 'payment',
                     'detail'=> $detail,
                     'detail_cn'=> $detail_cn,
                     'amount'=> '-'.$amt,
                     'label'=> $label,
                 );
                 LogController::save($log_data);
             }

            $total += $amt;
        }
    }

        $data['status'] = 'Success';
        $data['code'] = 200;
        return Response::json($data);
    }


    public function saveNewPassword(Request $request){

        $client_id = $request['client_id'];
        $password = $request['password'];
        $old_password = $request['old_password'];

        $user = User::where('id',$client_id)->first();

        $contact = ContactNumber::where('is_primary',1)->where('user_id',$client_id)->first();


        if($user){

            $cnum = $contact->number;

            if(Hash::check($cnum, $password)) {
                $response['status'] = 'Failed';
                $response['desc'] = 'Please don\'t use your mobile number as your password.';
                $response['desc_cn'] = 'Please don\'t use your mobile number as your password.';
                $httpStatusCode = 200; // Client Authentication failed
                return Response::json($response, $httpStatusCode);
            }

            if(Hash::check($password, $user->password)) {
                $response['status'] = 'Failed';
                $response['desc'] = 'Please enter different password.';
                $response['desc_cn'] = 'Please enter different password.';
                $httpStatusCode = 200; // Client Authentication failed
                return Response::json($response, $httpStatusCode);
            }

            preg_match_all('!\d+!', $old_password, $matches);
            $old_password = ltrim($old_password,"0");
            $old_password = ltrim($old_password,'+');
            $old_password = ltrim($old_password,'63');
            $old_password = "+63".$old_password;

            if(Hash::check($old_password, $user->password)) {
                $user->password = bcrypt($password);
                $user->save();

                $response['status'] = 200;
                $response['client'] = $user;
                $httpStatusCode = 200; // Success

                return Response::json($response, $httpStatusCode);
            } else {
                $response['status'] = 'Failed';
                $response['desc'] = 'Incorrect old password.';
                $response['desc_cn'] = 'Incorrect old password.';
                $httpStatusCode = 200; // Client Authentication failed
                return Response::json($response, $httpStatusCode);
            }
        }

    }

}

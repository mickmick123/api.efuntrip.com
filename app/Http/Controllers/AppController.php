<?php

namespace App\Http\Controllers;

use App\User;

use App\ContactNumber;

use App\Device;

use App\ClientService;
use App\ClientTransaction;
use App\Client;
use App\Group;
use App\GroupUser;
use App\QrCode;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Redirect;

use Response;
use Validator;
use Hash, DB;
use Carbon\Carbon;

use GuzzleHttp\Client as ClientGuzzle;
use phpseclib\Crypt\RSA;

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
                    if (Hash::check($request->password, $u->password)) {
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
                            preg_match_all('!\d+!', $cnum, $matches);
                            $cnum = implode("", $matches[0]);
                            $cnum = ltrim($cnum,"0");
                            $cnum = ltrim($cnum,'+');
                            $cnum = ltrim($cnum,'63');
                            $cnum = "0".$cnum;
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
                    $response['desc'] = 'Client authentication failed';
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
            "notifyUrl" => (string)$notifyUrl,
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
                'https://openapi.gjob.ph/pay', 
                [
                    'json' => $data
                ]
            );
            // return $r;
            $r = json_decode($r->getBody(), true);
            \Log::info($r);
            return Redirect::to($r['data']['content']);
        }
        catch (\Exception $ex) {

        }
    }

    public function updateServicePayment($qr_id) {
        $qr = QrCode::findorFail($qr_id);
        $service_ids = explode(',',$qr->service_ids);
        foreach($service_ids as $id){
            $cs = ClientService::findorFail($id);
            $discount =  ClientTransaction::where('client_service_id', $id)->where('type', 'Discount')->sum('amount');
            $amt = ($cs->charge + $cs->cost + $cs->tip + $cs->com_client + $cs->com_agent) - $discount;
            if($cs->payment_amount != 0){
                $amt -= $cs->payment_amount;
            }
            $cs->payment_amount = $amt;
            $cs->is_full_payment = 1;
            $cs->save();
        }

        $data['status'] = 'Success';
        $data['code'] = 200;
        return Response::json($data);
    }

}

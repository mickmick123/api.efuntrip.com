<?php

namespace App\Http\Controllers\Api;

use App\Models\Cashflow;
use App\Http\Controllers\Controller;
use App\Models\ServiceFee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use function GuzzleHttp\Promise\all;

class CashflowController extends Controller
{
    //
    public function getTheTransactionLog(Request $request){
        if($request->identity=='administrator'){
            $data=Cashflow::where('currencies',$request->currency)->orderby('id','desc')->get();
            return $this->jsonReturn('200','Get The Transaction Log/获取交易日志',$data);
        }elseif($request->identity=='admin'){
            $data=Cashflow::where('currencies',$request->currency)->orderby('id','desc')->get(['id','user_id','action','transfer_role','currencies','amount','rate','profit','total_balance','created_at','balance','operator']);
            return $this->jsonReturn('200','Get The Transaction Log/获取交易日志',$data);
        }elseif($request->identity=='user'){
            $cashflow=Cashflow::where('currencies',$request->currency)->where('user_id',$request->id)->orderBy('id','desc')->get(['id','user_id','action','transfer_role','currencies','amount','rate','profit','source','created_at','updated_at','balance','nickname','operator','number']);
            return $this->jsonReturn('200','Get The Transaction Log/获取交易日志',$cashflow);
        }else{
            return $this->jsonReturn('401','Insufficient permissions/权限不足','Insufficient permissions/权限不足');
        }
    }
    public function getServiceFeeFlowLog(Request $request){
        if(Auth::user()['identity']=='administrator'){
            $serviceFee=ServiceFee::where('currencies',$request->currency)->orderBy('id','desc')->get();
            return $this->jsonReturn(200,'Successfully obtained service fee log/成功获取服务费日志',$serviceFee);
        }elseif(Auth::user()['identity']=='admin'){
            $serviceFee=ServiceFee::where('currencies',$request->currency)->orderby('id','desc')->get(['id','user_id','action','currencies','amount','rate','profit','total_ServiceFee','created_at','operator']);
            return $this->jsonReturn('200','Get The Transaction Log/获取交易日志',$serviceFee);
        }else{
            return $this->jsonReturn('401','Insufficient permissions/权限不足','Insufficient permissions/权限不足');
        }
    }
    public function addTheTransactionLog(Request $request){
        //radio = currency Translation error. Causes the field name to be incorrect. Sorry
        //radio = currency 翻译错误。导致字段名称不正确。抱歉
        if($request->identity=='admin'){
            return $this->jsonReturn(401,'Insufficient permissions/权限不足','Insufficient permissions/权限不足');
        }
        $validator=Validator::make($request->all(),[
            'id'        =>'required_if:action,deposit|required_if:action,withdraw',
            'action'    =>'required',
            'amount'    =>'required',
            'profit'    =>'required_if:action,deposit',
            'radio'     =>'required',
            'id1'       =>'required_if:action,transfer'
        ]);
        if($validator->fails()) {
            return response()->json(['error'=>$validator->errors()]);
        }
        $sources=[];
        if($request->source!=''){
            foreach($request->source as $source){
                $sources[]=$source['value'];
            };
        }
        $sources=implode("---",$sources);
        if($request->action!='withdraw service fee'){
            $user=User::where('id',$request->id)->first();
            $user1=User::where('id',$request->id1)->first();
            if(empty($user1)){
                $user1=$user;
            }
            $admin=User::where('identity','administrator')->first();
            if($request->radio=='cn'){
                $currencies='cn';
                $currenciesprofit='cnprofit';
                $balance=$user->cn;
                $balance1=$user1->cn;
                $profit=$admin->cnprofit;
                $balanceAdmin=$admin->cn;
            }elseif($request->radio=='ph'){
                $currencies='ph';
                $currenciesprofit='phprofit';
                $balance=$user->ph;
                $balance1=$user1->ph;
                $profit=$admin->phprofit;
                $balanceAdmin=$admin->ph;
            }elseif($request->radio=='us'){
                $currencies='us';
                $currenciesprofit='usprofit';
                $balance=$user->us;
                $balance1=$user1->us;
                $profit=$admin->usprofit;
                $balanceAdmin=$admin->us;
            }
        }else{
            $admin=User::where('identity','administrator')->first();
            if($request->radio=='cn'){
                $currenciesprofit='cnprofit';
                $profit=$admin->cnprofit;
            }elseif($request->radio=='ph'){
                $currenciesprofit='phprofit';
                $profit=$admin->phprofit;
            }elseif($request->radio=='us'){
                $currenciesprofit='usprofit';
                $profit=$admin->usprofit;
            }
        }
        if($request->action=='deposit'){
            DB::beginTransaction();
            try{
                Cashflow::create([
                    'user_id'      =>$request->id,
                    'action'       =>$request->action,
                    'currencies'   =>$request->radio,
                    'amount'       =>$request->amount,
                    'rate'         =>$request->rate,
                    'profit'       =>$request->profit,
                    'total_balance'=>$request->amount+$balanceAdmin-$request->profit,
                    'source'       =>$sources,
                    'balance'      =>$balance+$request->amount-$request->profit,
                    'nickname'     =>$user->name,
                    'operator'     =>Auth::user()->name,
                    'number'       =>$user->number,
                ]);
                ServiceFee::create([
                    'user_id'      =>$request->id,
                    'action'       =>$request->action,
                    'currencies'   =>$request->radio,
                    'amount'       =>$request->amount,
                    'rate'         =>$request->rate,
                    'profit'       =>$request->profit,
                    'total_ServiceFee'=>$profit+$request->profit,
                    'source'       =>$sources,
                    'nickname'     =>$user->name,
                    'operator'     =>Auth::user()->name,
                    'number'       =>$user->number,
                ]);
                User::where('id',$request->id)
                    ->update([
                        $currencies=>$balance+$request->amount-$request->profit,
                    ]);
                User::where('identity','administrator')
                    ->update([
                        $currenciesprofit=>$request->profit+$profit,
                        $currencies=>$balanceAdmin+$request->amount-$request->profit,
                    ]);
                DB::commit();
                return $this->jsonReturn(200,'Deposit successful/存款成功','Deposit successful/存款成功');
            }catch(\Exception $e){
                DB::rollBack();
            }
        }elseif($request->action=='withdraw'){
            if($balance<$request->amount){
                return $this->jsonReturn('402','Insufficient amount/金额不足','Insufficient amount/金额不足');
            }
            DB::beginTransaction();
            try{
                Cashflow::create([
                    'user_id'      =>$request->id,
                    'action'       =>$request->action,
                    'currencies'   =>$request->radio,
                    'amount'       =>-$request->amount,
                    'rate'         =>0,
                    'profit'       =>0,
                    'total_balance'=>$balanceAdmin-$request->amount,
                    'source'       =>$sources,
                    'balance'      =>$balance-$request->amount,
                    'nickname'     =>$user->name,
                    'operator'     =>Auth::user()->name,
                    'number'       =>$user->number,
                ]);
                User::where('id',$request->id)
                    ->update([
                        $currencies=>$balance-$request->amount,
                    ]);
                User::where('identity','administrator')
                    ->update([
                        $currencies=>$balanceAdmin-$request->amount,
                    ]);
                DB::commit();
                return $this->jsonReturn(200,'Withdrawal successful/取款成功','Withdrawal successful/取款成功');
            }catch(\Exception $e){
                DB::rollBack();
            }
        }elseif($request->action=='withdraw service fee'){
            if($profit<$request->amount){
                return $this->jsonReturn('402','Insufficient service fee/服务费不足','Insufficient service fee/服务费不足');
            }
            DB::beginTransaction();
            try{
                ServiceFee::create([
                    'user_id'      =>$admin->id,
                    'action'       =>$request->action,
                    'currencies'   =>$request->radio,
                    'amount'       =>-$request->amount,
                    'rate'         =>0,
                    'profit'       =>-$request->amount,
                    'total_ServiceFee'=>$profit-$request->amount,
                    'source'       =>$sources,
                    'nickname'     =>Auth::user()->name,
                    'operator'     =>Auth::user()->name,
                    'number'       =>Auth::user()->number,
                ]);
                User::where('identity','administrator')
                    ->update([
                        $currenciesprofit=>$profit-$request->amount,
                    ]);
                DB::commit();
                return $this->jsonReturn(200,'Successfully withdrawn fee/提取手续费成功','Successfully withdrawn fee/提取手续费成功');
            }catch(\Exception $e){
                DB::rollBack();
            }
        }elseif($request->action=='transfer'){
            if($balance<$request->amount){
                return $this->jsonReturn('402','Insufficient balance/余额不足','Insufficient balance/余额不足');
            }
            $source=$user->name.'转账给用户'.$user1->name.'---金额'.$request->amount.$request->radio.'---'.$user1->name.'余额增加'.$request->amount.$request->radio;
            $sources=$source.'---'.$sources;
            if($request->pass){
                if(Hash::check($request->pass,Auth::user()->password)){
                    DB::beginTransaction();
                    try{
                        Cashflow::create([
                            'user_id'      =>$request->id,
                            'action'       =>$request->action,
                            'transfer_role'=>'sender',
                            'currencies'   =>$request->radio,
                            'amount'       =>-$request->amount,
                            'rate'         =>0,
                            'profit'       =>0,
                            'total_balance'=>$balanceAdmin,
                            'source'       =>$sources,
                            'balance'      =>$balance-$request->amount,
                            'nickname'     =>$user->name,
                            'operator'     =>Auth::user()->name,
                            'number'       =>$user->number,
                        ]);
                        Cashflow::create([
                            'user_id'      =>$request->id1,
                            'action'       =>$request->action,
                            'transfer_role'=>'recipient',
                            'currencies'   =>$request->radio,
                            'amount'       =>$request->amount,
                            'rate'         =>0,
                            'profit'       =>0,
                            'total_balance'=>$balanceAdmin,
                            'source'       =>$sources,
                            'balance'      =>$balance1+$request->amount,
                            'nickname'     =>$user1->name,
                            'operator'     =>Auth::user()->name,
                            'number'       =>$user1->number,
                        ]);
                        User::where('id',$request->id)
                            ->update([
                                $currencies=>$balance-$request->amount,
                            ]);
                        User::where('id',$request->id1)
                            ->update([
                                $currencies=>$balance1+$request->amount,
                            ]);
                        DB::commit();
                        return $this->jsonReturn('200','Successful transfer/转账成功','Successful transfer/转账成功');
                    }catch(\Exception $e){
                        DB::rollBack();
                    }
                }else{
                    return $this->jsonReturn('402','wrong password/密码错误','wrong password/密码错误');
                }
            }else{
                DB::beginTransaction();
                try{
                    Cashflow::create([
                        'user_id'      =>$request->id,
                        'action'       =>$request->action,
                        'transfer_role'=>'sender',
                        'currencies'   =>$request->radio,
                        'amount'       =>-$request->amount,
                        'rate'         =>0,
                        'profit'       =>0,
                        'total_balance'=>$balanceAdmin,
                        'source'       =>$sources,
                        'balance'      =>$balance-$request->amount,
                        'nickname'     =>$user->name,
                        'operator'     =>Auth::user()->name,
                        'number'       =>$user->number,
                    ]);
                    Cashflow::create([
                        'user_id'      =>$request->id1,
                        'action'       =>$request->action,
                        'transfer_role'=>'recipient',
                        'currencies'   =>$request->radio,
                        'amount'       =>$request->amount,
                        'rate'         =>0,
                        'profit'       =>0,
                        'total_balance'=>$balanceAdmin,
                        'source'       =>$sources,
                        'balance'      =>$balance1+$request->amount,
                        'nickname'     =>$user1->name,
                        'operator'     =>Auth::user()->name,
                        'number'       =>$user1->number,
                    ]);
                    User::where('id',$request->id)
                        ->update([
                            $currencies=>$balance-$request->amount,
                        ]);
                    User::where('id',$request->id1)
                        ->update([
                            $currencies=>$balance1+$request->amount,
                        ]);
                    DB::commit();
                    return $this->jsonReturn('200','Successful transfer/转账成功','Successful transfer/转账成功');
                }catch(\Exception $e){
                    DB::rollBack();
                }
            }
        }
    }
}

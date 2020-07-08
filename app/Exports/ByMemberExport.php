<?php

namespace App\Exports;

use App\Group;
use App\ClientService;
use App\User;
use App\ClientTransaction;
use App\Service;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;


use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use DB, Response;
use DateTime;

class ByMemberExport implements FromView, WithEvents, ShouldAutoSize
{

  public function __construct(int $id, string $lang, array $data, array $group)
  {
      $this->id = $id;
      $this->lang = $lang;
      //$this->ids = $ids;
      $this->users = [];
      $this->services = [];
      $this->group = $group;
      $this->data = $data;
  }

  public function registerEvents(): array
  {

      return [

          BeforeExport::class => function(BeforeExport $event) {
            $event->writer->getProperties()->setCreator('4ways')
                ->setTitle("Group By Members")
                ->setSubject("Office 2007 XLSX Test Document");

          },

          AfterSheet::class    => function(AfterSheet $event) {
              $cellRange = 'A1:E1'; // All headers

              $sheet = $event->sheet->getDelegate();


          },
      ];
  }

  private function DateChinese($date){
        $d = explode(" ",strtolower($date));
        switch($d[0]){
            case "jan":
                return "一月"." ".$d[1];
                break;
            case "feb":
                return "二月"." ".$d[1];
                break;
            case "mar":
                return "三月"." ".$d[1];
                break;
            case "apr":
                return "四月"." ".$d[1];
                break;
            case "may":
                return "五月"." ".$d[1];
                break;
            case "jun":
                return "六月"." ".$d[1];
                break;
            case "jul":
                return "七月"." ".$d[1];
                break;
            case "aug":
                return "八月"." ".$d[1];
                break;
            case "sep":
                return "九月"." ".$d[1];
                break;
            case "oct":
                return "十月"." ".$d[1];
                break;
            case "nov":
                return "十一月"." ".$d[1];
                break;
            case "dec":
                return "十二月"." ".$d[1];
                break;
            default:
                return $date;
        }
  }

  private function statusChinese($status){
        $s = strtolower(trim($status," "));
        $stat = '';
        if($s == 'complete'){
            $stat = '已完成';
        }
        if($s == 'on process'){
            $stat = '办理中';
        }
        if($s == 'pending'){
            $stat=  '待办';
        }
        return $stat;
  }

  private function typeChinese($type){
        $s = strtolower(trim($type," "));
        $dtype = '';

        if($s == 'deposit'){
            $dtype = '预存款';
        }
        if($s == 'discount'){
            $dtype = '折扣';
        }
        if($s == 'payment'){
            $dtype = '已付款';
        }
        if($s == 'refund'){
            $dtype = '退款';
        }
        return $dtype;
  }



  public function members($id) {

    /* $mems = DB::table('group_user as g_u')
                  ->where('g_u.group_id', $id)
                  ->get();

      $gids = $mems->pluck('user_id');

      $groups = DB::table('users as u')->select(DB::raw('u.id, CONCAT(u.first_name, " ", u.last_name) as name, g_u.is_vice_leader, g_u.total_service_cost, g_u.id as guid'))
                      ->leftjoin(DB::raw('(select * from group_user) as g_u'),'g_u.user_id','=','u.id')
                      ->whereIn('u.id', $gids)->get();

      $response = $groups;

        $ctr=0;
        $temp = [];

        $chrg = 0;
        $tempTotal = 0;
        $bal = 0;

        foreach($groups as $g){
           $packs = DB::table('packages as p')->select(DB::raw('p.*,g.name as group_name'))
                      ->leftjoin(DB::raw('(select * from groups) as g'),'g.id','=','p.group_id')
                       ->where('client_id', $g->id)
                       ->where('group_id', $id)
                      ->orderBy('id', 'desc')
                      ->get();

          $totalServiceCost = 0;


          if(count($packs) > 0){

          //  foreach($packs as $p){

                $services = DB::table('client_services as cs')
                    ->select(DB::raw('cs.*'))
                    ->where('client_id',$g->id)
                    ->where('group_id',$id)
                    ->orderBy('id', 'desc')
                    ->get();

                  $ctr2 = 0;

                    foreach($services as $s){

                      $s->package_cost = $s->cost+ $s->charge + $s->tip + $s->com_agent + $s->com_client;
                      $chrg = ($s->active == 0 || $s->status !== 'complete') ? 0 : ($s->charge + $s->cost + $s->tip);

                      $translated = Service::where('id',$s->service_id)->first();

                      $s->detail =  $s->detail;
                      if($translated){
                            if($this->lang === 'CN'){
                              $s->detail = (($translated->detail_cn != '' && $translated->detail_cn != 'NULL') ? $translated->detail_cn : $s->detail);
                            }
                      }

                      $datetime = new DateTime($s->created_at);
                      $getdate = $datetime->format('M d,Y');
                      $gettime = $datetime->format('h:i A');


                      $s->discount =  ClientTransaction::where('client_service_id', $s->id)->where('type', 'Discount')->sum('amount');
                      if($s->active !== 0){
                          $totalServiceCost += ($s->package_cost - $s->discount);
                      }


                      if($this->lang === 'EN'){
                          $s->datetime = $getdate;
                          $s->status = ucfirst($s->status);
                      }else{
                          $s->datetime = $this->DateChinese($getdate);
                          $s->status = $this->statusChinese($s->status);
                      }


                      //Subtotal
                      $sub = $chrg;

                      //Per Person Balance
                      if($s->active == 0){
                          $sub = 0;
                      }

                      $bal += $sub;

                      $tempTotal +=$sub;

                      $s->total_service_cost = $tempTotal;


                    }
                    $packs = $services;
          //  }
            $temp['packages'] = $packs;
          }else{
            $temp['packages'] = [];
          }


          $temp['id'] = $g->guid;
          $temp['name'] = $g->name;
          $temp['is_vice_leader'] = $g->is_vice_leader;
          $temp['user_id'] = $g->id;
          $temp['total_service_cost'] = $totalServiceCost;
          $response[$ctr] =  $temp;
          $ctr++;
        }


        $this->group['total_complete_service_cost'] = $this->group['total_cost'];

      */
      $ctr = 0;
      $temp = [];
      $response = [];
      $i = 0;

      $chrg = 0;
      $tempTotal = 0;
      $bal = 0;


      foreach($this->data as $data){
          $temp['id'] = $data['user_id'];
          $temp['name'] = $data['name'];
          $temp['is_vice_leader'] = $data['is_vice_leader'];
          $temp['user_id'] = $data['id'];

          $temp['packages'] = [];
          $temPackage = [];
          $j = 0;

          $totalServiceCost = 0;

          foreach($data['packages'] as $p){

            $datetime = new DateTime($p['created_at']);
            $getdate = $datetime->format('M d,Y');
            $gettime = $datetime->format('h:i A');

            $chrg = ($p['active'] == 0 || $p['status'] !== 'complete') ? 0 : ($p['charge'] + $p['cost'] + $p['tip']);

            if($p['active'] == 0){
                 $sub = 0;
            }
            //
            if($p['active'] !== 0){
                $totalServiceCost += ($p['package_cost'] - $p['discount']);
            }
            //
            //Subtotal
            $sub = $chrg;

            //Per Person Balance
            if($p['active'] == 0){
                $sub = 0;
            }

            $bal += $sub;

            $tempTotal +=$sub;

            $p['total_service_cost'] = $tempTotal;

            if($this->lang === 'EN'){
                $p['datetime'] = $getdate;
                $p['status'] = ucfirst($p['status']);
            }else{
                $p['datetime'] = $this->DateChinese($getdate);
                $p['status'] = $this->statusChinese($p['status']);
            }


            $temPackage[$j] = $p;
            $j++;
          }

          $temp['packages'] =  $temPackage;
          $temp['total_service_cost'] = $totalServiceCost;
          $response[$ctr] =  $temp;
          $ctr = $ctr + 1;

        }
      //  $response = $this->data;
        return $response;
  }


  public function transactions($id){

    $response = DB::table('client_transactions as a')
                  ->select(DB::raw('
                      a.amount,a.type, a.created_at'))
                      ->where('group_id', $id)
                      ->orderBy('created_at','DESC')
                      ->get();


    foreach($response as $s){
      $datetime = new DateTime($s->created_at);
      $getdate = $datetime->format('M d,Y');
      $s->amount = number_format($s->amount,2);

      if($this->lang === 'EN'){
          $s->created_at = $getdate;

      }else{
          $s->created_at = $this->DateChinese($getdate);
          $s->type = $this->typeChinese($s->type);
      }
    }


    return $response;
  }

  private function getGroupTotalCompleteServiceCost($id) {
      $group_cost = DB::table('client_services')
          ->select(DB::raw('sum(cost+charge+tip  + com_client + com_agent) as total_cost'))
          ->where('group_id', '=', $id)
          ->where('active', '=', 1)
          ->where('status', '=', 'complete')
          ->first();

      return $group_cost->total_cost;
  }




  public function view(): View
  {

    $lang = [];
    //  $group_summary = ($lang == 'EN' ? 'Group Summary' : '总结报告');




    if($this->lang === 'EN'){
        $lang['_date'] = 'Date';
        $lang['_service'] = 'Service';
        $lang['_status'] = 'Status';
        $lang['_charge'] = 'Charge';
        $lang['_group_total'] = 'Group Total Cost';
        $lang['_group_summary'] = 'Group Summary';
        $lang['_member_subtotal'] = '-- Member Subtotal --';
        $lang['_total_deposit'] = 'Total Deposit : ';
        $lang['_total_cost'] = 'Total Cost : ';
        $lang['_total_promo'] = 'Total Promo : ';
        $lang['_total_refund'] = 'Total Refund : ';
        $lang['_total_balance'] = 'Total Balance : ';
        $lang['_total_collectables'] = 'Total Collectables : ';
        $lang['_total_complete_cost'] = 'Total Complete Cost : ';
        $lang['_transcation_history'] = 'Transactions History : ';
        $lang['_amount'] = 'Amount';
        $lang['_type'] = 'Type';
        $lang['_deposit'] = 'Deposit';

    }else{
        $lang['_date'] = '建立日期';
        $lang['_service'] = '服务';
        $lang['_status'] = '状态';
        $lang['_charge'] = '收费';
        $lang['_group_total'] = '总余额' ;
        $lang['_group_summary'] = '总结报告';
        $lang['_member_subtotal'] = '-- 成员小计 --';
        $lang['_total_deposit'] = '总已付款 : ';
        $lang['_total_cost'] = '总花费 : ';
        $lang['_total_promo'] = '总促销 : ';
        $lang['_total_refund'] = '总退款 : ';
        $lang['_total_balance'] = '总余额 : ';
        $lang['_total_collectables'] = '总应收款 : ';
        $lang['_total_complete_cost'] = '总服务费 : ';
        $lang['_transcation_history'] = '交易记录 : ';
        $lang['_amount'] = '共计';
        $lang['_type'] = '类型';
        $lang['_deposit'] = '预存款';
    }


      $data = $this->members($this->id);

      $transactions = $this->transactions($this->id);

      return view('export.user', [
          'transactions' => $transactions,
          'members' => $data,
          'group' => $this->group,
          'lang' => $lang
      ]);
  }



}

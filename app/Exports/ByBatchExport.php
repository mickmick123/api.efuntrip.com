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

use DB, Response, DateTime;

class ByBatchExport implements FromView, WithEvents, ShouldAutoSize
{

  public function __construct(int $id, string $lang, array $ids, array $group)
  {
      $this->id = $id;
      $this->lang = $lang;
      $this->ids = $ids;
      $this->users = [];
      $this->services = [];
      $this->group = $group;
  }

  public function registerEvents(): array
  {

      return [

          BeforeExport::class => function(BeforeExport $event) {
            $event->writer->getProperties()->setCreator('4ways')
                ->setTitle("Group By Batch")
                ->setSubject("Office 2007 XLSX Test Document");

          },

          AfterSheet::class    => function(AfterSheet $event) {
              // $cellRange = 'A1:F1'; // All headers
              //
              // $sheet = $event->sheet->getDelegate();
              //
              // $sheet->getStyle('A1:F1')->getFont()
              //       ->setSize(14)
              //       ->setBold(true)
              //       ->getColor()->setRGB('000000');
              //
              // $sheet->getStyle('A1:F1')->getFill()
              //           ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              //           ->getStartColor()->setARGB('6fcddc');

          },
      ];
  }


  public function byBatch($groupId){

        $clientServices = DB::table('client_services')
          ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, id, detail, created_at, service_id'))
          ->where('active',1)->where('group_id',$groupId)
          ->groupBy(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'))
          ->orderBy('id','DESC')
          ->get();

        $ctr = 0;
        $temp = [];
        $response = $clientServices;


        foreach($clientServices as $s){

          $query = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId);


          $temp['detail'] = $s->detail;

          $temp['service_date'] = $s->sdate;
          $temp['sdate'] = $s->sdate;
          $temp['group_id'] = $groupId;


          $datetime = new DateTime($s->sdate);
          //$getdate = $datetime->format('M d,Y');
          $getdate = $datetime->format('Y-m-d');

        //  if($this->lang === 'EN'){
             $temp['sdate'] =  $getdate;
             $temp['service_date'] = $getdate;
        //  }else{
          //    $temp['sdate'] =  $this->DateChinese($getdate);
          //    $temp['service_date'] = $this->DateChinese($getdate);
        //  }


          $queryMembers = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->orderBy('created_at','DESC')->orderBy('client_id')->groupBy('client_id')->get();

          $ctr2 = 0;
          $members = [];
          $discountCtr = 0;
          $totalCost = 0;

          //for total complete cost
          $chrg = 0;
          $tempTotal = 0;
          $bal = 0;

          foreach($queryMembers as $m){
                $ss =  ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$s->sdate)->where('group_id', $groupId)->where('client_id',$m->client_id)->get();

                $clientServices = [];
                $tmpCtr = 0;

                foreach($ss as $cs){
                  $cs->discount =  ClientTransaction::where('client_service_id', $cs->id)->where('type', 'Discount')->sum('amount');
                  if($cs->active !== 0){
                    $discountCtr += $cs->discount;
                    $totalCost += (($cs->cost + $cs->charge + $cs->tip + $cs->com_client + $cs->com_agent)) - $cs->discount;
                  }

                  $translated = Service::where('id',$cs->service_id)->first();

                  $cs->detail =  $cs->detail;
                  if($translated){
                        if($this->lang === 'CN'){
                          $cs->detail = (($translated->detail_cn != '' && $translated->detail_cn != 'NULL') ? $translated->detail_cn : $cs->detail);
                        }
                  }

                  if($this->lang === 'EN'){
                      $cs->status = ucfirst($cs->status);
                  }else{
                      $cs->status = $this->statusChinese($cs->status);
                  }


                  $chrg = ($cs->active == 0 || strtolower($cs->status) !== 'complete') ? 0 : ($cs->charge + $cs->cost + $cs->tip);

                  $sub = $chrg;

                  //Per Person Balance
                  if($cs->active == 0){
                      $sub = 0;
                  }

                  $bal += $sub;

                  $tempTotal +=$sub;

                  $cs->total_service_cost = $tempTotal;
                  $cs->total_charge = (($cs->cost + $cs->charge + $cs->tip + $cs->com_client + $cs->com_agent)) - $cs->discount;

                  $clientServices[$tmpCtr] = $cs;
                  $tmpCtr++;
                }

                //$member = User::where('id',$m->client_id)->select('first_name','last_name')->first();

                $member = ($members[$ctr2] = User::where('id',$m->client_id)->select('first_name','last_name')->first());
                //  $members[$ctr2]['tcost']
                $members[$ctr2]['name'] =  $member->first_name ." ". $member->last_name;
                $members[$ctr2]['tcost'] = $query->where('client_id',$m->client_id)->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)"));
                $members[$ctr2]['services'] = $clientServices;
              $ctr2++;
          }
          $temp['total_service_cost'] = $totalCost;
          $temp['members'] = $members;
          $response[$ctr] = $temp;
          $ctr++;
        }

        $this->group['total_complete_service_cost'] = $this->group['total_cost'];

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



  public function view(): View
  {
      $data = $this->byBatch($this->id);
      $app = app();


      $transactions = $this->transactions($this->id);

      $response = DB::table('client_transactions as a')
                    ->select(DB::raw('
                        a.amount,a.type, a.created_at'))
                        ->where('group_id', $this->id)
                        ->orderBy('created_at','DESC')
                        ->get();

      $temp = [];
      $ctr = 0;
      foreach($response as $s){
        //$tempObj = {};
        $members = [];

        $services = [];

        $tempObj['detail'] = $s->type;
        $tempObj['group_id'] = $this->id;

        $datetime = new DateTime($s->created_at);
        $tempObj['sdate'] = $datetime->format('Y-m-d');
        $tempObj['service_date'] = $datetime->format('Y-m-d');
        $tempObj['total_service_cost'] = $s->amount;

        $services[0] =  User::where('id',1)->select('first_name','last_name')->first();

        $services[0]['tracking']=  "";
        $services[0]['status']=  "";
        $services[0]['name']= "";


        if($this->lang === 'EN'){
           $services[0]['detail'] = $s->type;
        }else{
           $services[0]['detail'] = $this->typeChinese($s->type);
        }

        $services[0]['total_charge'] = $s->amount;
        $services[0]['total_service_cost'] = $s->amount;
        $services[0]['remarks'] = "";

        $members[0] = User::where('id',1)->select('first_name','last_name')->first();

        $members[0]['name'] = '';
        $members[0]['services'] = $services;
        $tempObj['members'] = $members;

        $temp[$ctr] = $tempObj;
        $ctr++;
      }

      $merged = $data->merge($temp);
      $result = $merged->all();

      $result2 = collect($result)->sortBy('service_date')->reverse()->toArray();
      $ctr = 0;
      foreach($result2 as $r){
        $datetime = new DateTime($r['service_date']);
        $getdate = $datetime->format('M d,Y');

        if($this->lang === 'EN'){
            $result2[$ctr]['service_date'] = $getdate;
        }
        else{
            $result2[$ctr]['service_date'] = $this->DateChinese($getdate);
        }
        $ctr++;
      }


      $lang = [];

      if($this->lang === 'EN'){
          $lang['_date'] = 'Date';
          $lang['_service'] = 'Service';
          $lang['_charge'] = 'Charge';

          $lang['_total_deposit'] = 'Total Deposit : ';
          $lang['_total_cost'] = 'Total Cost : ';
          $lang['_total_promo'] = 'Total Promo : ';
          $lang['_total_refund'] = 'Total Refund : ';
          $lang['_total_balance'] = 'Total Balance : ';
          $lang['_total_collectables'] = 'Total Collectables : ';
          $lang['_total_complete_cost'] = 'Total Complete Cost : ';

          $lang['_servic_name'] = 'Service Name';
          $lang['_latest_date'] = 'Latest Date';
          $lang['_total_service_cost'] = 'Total Service Cost';


          $lang['_transcation_history'] = 'Transactions History : ';

          $lang['_amount'] = 'Amount';
          $lang['_type'] = 'Type';
          $lang['_deposit'] = 'Deposit';

          $lang['_service_date'] = 'Service Date';
          $lang['_package'] = 'Package';
          $lang['_status'] = 'Status';
          $lang['_details'] = 'Details';
          $lang['_charge'] = 'Charge';
          $lang['_group_total_bal'] = 'Group Total Balance' ;

      }else{
          $lang['_date'] = '建立日期';
          $lang['_service'] = '服务';

          $lang['_charge'] = '收费';
          $lang['_group_total_bal'] = '总余额';
          $lang['_total_deposit'] = '总已付款 : ';
          $lang['_total_cost'] = '总花费 : ';
          $lang['_total_promo'] = '总促销 : ';
          $lang['_total_refund'] = '总退款 : ';
          $lang['_total_balance'] = '总余额 : ';
          $lang['_total_collectables'] = '总应收款 : ';
          $lang['_total_complete_cost'] = '总服务费 : ';

          $lang['_servic_name'] = '服务明细';
          $lang['_latest_date'] = '最近的服务日期';
          $lang['_total_service_cost'] = '总服务费';
          $lang['_transcation_history'] = '交易记录 : ';

          $lang['_amount'] = '共计';
          $lang['_type'] = '类型';
          $lang['_deposit'] = '预存款';

          $lang['_service_date'] = '服务日期';
          $lang['_package'] = '查询编号';
          $lang['_status'] = '状态';

          $lang['_details'] = '服务明细';
          $lang['_charge'] = '收费';
          $lang['_group_total_bal'] = '总余额';
      }


      return view('export.batch', [
          'transactions' => $transactions,
          'services' => $result2,
          'group' => $this->group,
          'lang' => $lang
      ]);
  }



}

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

class ByServiceExport implements FromView, WithEvents, ShouldAutoSize
{

  public function __construct(int $id, string $lang, array $ids, array $group, object $req)
  {
      $this->id = $req->id;
      $this->lang = $req->lang;
      $this->ids = $ids;
      $this->users = [];
      $this->group = $group;
      $this->year = $req->year;
      $this->month = $req->month;
      $this->services = $req->services;
  }

  public function registerEvents(): array
  {

      return [

          BeforeExport::class => function(BeforeExport $event) {
            $event->writer->getProperties()->setCreator('4ways')
                ->setTitle("Group By Service")
                ->setSubject("Office 2007 XLSX Test Document");

          },

          AfterSheet::class    => function(AfterSheet $event) {
              $cellRange = 'A1:E1'; // All headers
              $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

              $sheet = $event->sheet->getDelegate();

            //  $sheet->getStyle($cellRange)->getFont()->setSize(14);
              foreach ($columns as $col) {
                  $sheet->getColumnDimension($col)->setWidth(30);
              }

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


  public function service($id){

    //$search = $request->input('search');

    $clientServices = DB::table('client_services')
      ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, detail, created_at'))
      ->where('group_id',$id)
      //->whereIn('service_id', $this->services)
      ->groupBy('service_id')
      ->orderBy('created_at','DESC')
      ->get();

    $ctr = 0;
    $temp = [];
    $response = $clientServices;

    $chrg = 0;
    $tempTotal = 0;
    $bal = 0;
    foreach($clientServices as $s){

        $query = ClientService::where('created_at', $s->created_at)->where('service_id',$s->service_id)->where('group_id', $id)->where('active', 1);

        $servicesByDate = DB::table('client_services')
          ->select(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y") as sdate, service_id, id, detail, created_at, client_id'))
          ->where('group_id',$id)
          ->where('service_id',$s->service_id)
          ->groupBy(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'))
          ->orderBy('created_at','DESC')
          ->get();

        //$temp['detail'] = $s->detail;

        $translated = Service::where('id',$s->service_id)->first();

        $temp['detail'] =  $s->detail;
        if($translated){
              if($this->lang === 'CN'){
                $temp['detail'] = (($translated->detail_cn != '' && $translated->detail_cn != 'NULL') ? $translated->detail_cn : $s->detail);
              }
        }



        $temp['service_date'] = $s->sdate;
        //$temp['sdate'] = $s->sdate;

        $datetime = new DateTime($s->sdate);
        $getdate = $datetime->format('M d,Y');

        if($this->lang == 'EN'){
            $temp['sdate'] =  $getdate;
            $temp['service_date']=  $getdate;
        }else{
            $temp['sdate'] =  $this->DateChinese($getdate);
            $temp['service_date']=  $this->DateChinese($getdate);
        }


        $temp['group_id'] = $id;

        $discountCtr = 0;
        $totalServiceCount = 0;


        foreach($servicesByDate as $sd){

          $queryClients = ClientService::where('service_id', $sd->service_id)->where('created_at', $sd->created_at)->where('group_id', $id)->orderBy('created_at','DESC')->orderBy('client_id')->groupBy('client_id')->get();

          $memberByDate = [];
          $ctr2 = 0;





          foreach($queryClients as $m){

            $clientServices = [];
            $tmpCtr = 0;

            $m->discount = ClientTransaction::where('client_service_id', $m->id)->where('type', 'Discount')->sum('amount');
            $discountCtr += $m->discount;


            $memberByDate[$ctr2] = User::where('id',$m->client_id)->select('first_name','last_name')->first();
            $memberByDate[$ctr2]['tcost'] = ClientService::where(DB::raw('date_format(STR_TO_DATE(created_at, "%Y-%m-%d"),"%m/%d/%Y")'),$sd->sdate)->where('group_id', $id)->where('client_id',$m->client_id)->value(DB::raw("SUM(cost + charge + tip +com_client + com_agent)"));
            $memberByDate[$ctr2]['service'] = $m;

            $memberByDate[$ctr2]['created_at'] = $m->created_at;

            if($this->lang === 'EN'){
                $m->status = ucfirst($m->status);
            }else{
                $m->status = $this->statusChinese($m->status);
            }

            $chrg = ($m->active == 0 || strtolower($m->status) !== 'complete') ? 0 : ($m->charge + $m->cost + $m->tip);

            $sub = $chrg;

            //Per Person Balance
            if($m->active == 0){
                $sub = 0;
            }

            $bal += $sub;

            $tempTotal +=$sub;

            $m->total_service_cost = $tempTotal;


            $ctr2++;

            if($m->active && $m->status != "cancelled")
              $totalServiceCount++;
         }

         $sd->members = $memberByDate;
      }


        $temp['total_service_cost'] = ($query->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)"))) - $discountCtr;
        $temp['total_service'] = ($query->value(DB::raw("SUM(cost + charge + tip + com_client + com_agent)")));
        $temp['service_count'] = $totalServiceCount;


        $temp['bydates'] = $servicesByDate;
        $response[$ctr] = $temp;
        $ctr++;
    }
    $this->group['total_complete_service_cost'] = $tempTotal;

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

      if($this->lang === 'EN'){
          $s->created_at = $getdate;

      }else{
          $s->created_at = $this->DateChinese($getdate);
          $s->type = $this->typeChinese($s->type);
      }
    }


    return $response;
  }



  public function view(): View
  {

      $data = $this->service($this->id);

      $transactions = $this->transactions($this->id);

      $lang = [];

      if($this->lang === 'EN'){
          $lang['_date'] = 'Date';
          $lang['_service'] = 'Service';
          $lang['_status'] = 'Status';
          $lang['_charge'] = 'Charge';
          $lang['_group_total_bal'] = 'Group Total Balance' ;
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
          $lang['_package'] = 'Package';

          $lang['_transcation_history'] = 'Transactions History : ';

          $lang['_amount'] = 'Amount';
          $lang['_type'] = 'Type';
          $lang['_deposit'] = 'Deposit';

      }else{
          $lang['_date'] = '建立日期';
          $lang['_service'] = '服务';
          $lang['_status'] = '状态';
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
          $lang['_package'] = '查询编号';

          $lang['_transcation_history'] = '交易记录 : ';

          $lang['_amount'] = '共计';
          $lang['_type'] = '类型';
          $lang['_deposit'] = '预存款';
      }


      return view('export.service', [
          'transactions' => $transactions,
          'services' => $data->toArray(),
          'group' => $this->group,
          'lang' => $lang
      ]);

  }



}
<?php

namespace App\Exports;

use App\Group;
use App\ClientService;
use App\User;
use App\ClientTransaction;
use App\Service;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Collection;

use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use DB, Response, DateTime;

class ByBatchExport implements FromView, WithEvents, ShouldAutoSize
{

  public function __construct(int $id, string $lang, array $data, array $group, object $req)
  {
      $this->id = $id;
      $this->lang = $lang;
      //$this->ids = $ids;
      $this->data = $data;
      $this->users = [];
      $this->services = [];
      $this->group = $group;
      $this->year = $req->year;
      $this->month = $req->month;
      $this->date = $req->date;
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
              //
               $sheet = $event->sheet->getDelegate();
               $sheet->getColumnDimension('A')->setAutoSize(false);
               $sheet->getColumnDimension('A')->setWidth(30);

               $sheet->getColumnDimension('B')->setAutoSize(false);
               $sheet->getColumnDimension('B')->setWidth(30);

               $sheet->getColumnDimension('C')->setAutoSize(false);
               $sheet->getColumnDimension('C')->setWidth(20);

               $sheet->getColumnDimension('E')->setAutoSize(false);
               $sheet->getColumnDimension('E')->setWidth(20);

               $sheet->getColumnDimension('F')->setAutoSize(false);
               $sheet->getColumnDimension('F')->setWidth(20);
          },
      ];
  }


  public function byBatch($groupId){

    $temp = [];
    $ctr = 0;
    $response = [];

    foreach($this->data as $data){

      //  $temp['sdate'] = $data['sdate'];

       $datetime = new DateTime($data['sdate']);
       $getdate = $datetime->format('M d,Y');

        $temp['sdate'] = strtotime($data['sdate']);
        $temp['total_service_cost'] = $data['total_service_cost'];
        $temp['group_id'] = $data['group_id'];
        $temp['detail'] = $data['detail'];
        $temp['service_date'] = $data['service_date'];

        if($this->lang === 'EN'){
            $temp['service_date'] = $getdate;
        }
        else{
            $temp['service_date'] = $this->DateChinese($getdate);
        }

        $temPackage = [];
        $j = 0;
        $members = [];
       foreach($data['members'] as $p){
          if(isset($p['first_name'])){
            $p['name'] = $p['first_name']. " " . $p['last_name'];
          }else{
            $p['name'] = "";
          }
          $members[$j] =  $p;
          $j++;
        }

        $temp['members'] =  $members;
        $response[$ctr] =  $temp;
        $ctr = $ctr + 1;

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

      $result2 = collect($data)->sortBy('sdate')->reverse()->toArray();

      usort($data, function($a, $b)
      {
               if ($a["sdate"] == $b["sdate"])
                 return (0);
               return (($a["sdate"] > $b["sdate"]) ? -1 : 1);
      });

      $ctr = 0;
      $totalBal = 0;
      $totalPre = 0;

      foreach($result2 as $r){
        $members = [];
        $j = 0;

        foreach($r['members'] as $member){
            $services = [];
            $i = 0;


            foreach($member['services'] as $s){

                if($s["active"] == -1){
                   $totalBal = ((float) $totalBal) - ((float) $s["total_charge"]);
                }else{
                  if($s["active"] == 1 && strtolower($s['status']) == 'complete'){
                    $totalBal = ((float) $totalBal) - ((float) $s["total_charge"]);
                  }
                }

              $s["total_service_cost"] = $totalPre;
              $totalPre = $totalBal;

              $services[$i] = $s;
              $i++;
            }
            $member['services'] = $services;
            $members[$j] = $member;
            $j++;
        }

        $result2[$ctr]['members'] = $members;
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

          $lang['_discount'] = 'Discount';
          $lang['_service_sub'] = 'Service Sub Total';


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

          $lang['_service_sub'] = 'Service Sub Total';
          $lang['_discount'] = '折扣';
      }


      return view('export.batch', [
          'services' => $result2,
          'group' => $this->group,
          'lang' => $lang
      ]);
  }



}

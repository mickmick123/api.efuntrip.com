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
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

//use App\Exports\Sheets\ServiceSheet;
//, WithMultipleSheets

use DB, Response;
use DateTime;

class ByServiceExport implements FromView, WithEvents, ShouldAutoSize
{

  public function __construct(int $id, string $lang, array $data, array $group, object $req)
  {
      $this->id = $req->id;
      $this->lang = $req->lang;
      //$this->ids = $ids;
      $this->data = $data;
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

              $sheet = $event->sheet->getDelegate();
              $sheet->getColumnDimension('A')->setAutoSize(false);
              $sheet->getColumnDimension('A')->setWidth(20);

              $sheet->getColumnDimension('B')->setAutoSize(false);
              $sheet->getColumnDimension('B')->setWidth(20);

              $sheet->getColumnDimension('C')->setAutoSize(false);
              $sheet->getColumnDimension('C')->setWidth(20);

              $sheet->getColumnDimension('D')->setAutoSize(false);
              $sheet->getColumnDimension('D')->setWidth(20);

              $sheet->getColumnDimension('E')->setAutoSize(false);
              $sheet->getColumnDimension('E')->setWidth(15);

              $sheet->getColumnDimension('F')->setAutoSize(false);
              $sheet->getColumnDimension('F')->setWidth(15);

              $sheet->getColumnDimension('G')->setAutoSize(false);
              $sheet->getColumnDimension('G')->setWidth(15);

             // $ctr = 2;
             //
             // foreach($this->data as $data){
             //    $ctr = $ctr+3;
             //    foreach($data['bydates'] as $p){
             //        $ctr++;
             //         foreach($p['members'] as $m){
             //           $ctr = $ctr+2;
             //           $sheet->mergeCells('A'.$ctr.':B'.$ctr);
             //           //$sheet->mergeCells('D'.$ctr.':E'.$ctr);
             //          // $sheet->mergeCells('F'.$ctr.':G'.$ctr);
             //           $ctr++;
             //         }
             //    }
             //    $ctr++;
             // }

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


   $temp = [];
   $ctr = 0;
   $response = [];


   foreach($this->data as $data){

       $temp['total_service'] = $data['total_service'];
       $temp['total_service_cost'] = $data['total_service_cost'];
       $temp['service_count'] = $data['service_count'];
       $temp['group_id'] = $data['group_id'];
       $temp['detail'] = $data['detail'];

       $datetime = new DateTime($data['service_date']);
       $getdate = $datetime->format('M d,Y');

       if($this->lang == 'EN'){
           $temp['service_date']=  $getdate;
       }else{
           $temp['service_date']=  $this->DateChinese($getdate);
       }

       $temPackage = [];
       $j = 0;

       foreach($data['bydates'] as $p){

         $datetime = new DateTime($p['sdate']);
         $getdate = $datetime->format('M d,Y');

         if($this->lang == 'EN'){
           $p['sdate'] =  $getdate;
         }else{
           $p['sdate'] = $this->DateChinese($getdate);
         }

         $members = [];
         $ctrM = 0;
         foreach($p['members'] as $m){
           $clientServices = [];
           $tmpCtr = 0;

           $m['name'] = $m['first_name']. " " . $m['last_name'];
           $m['total_charge'] = (($m['service']['cost']) + ($m['service']['charge']) + ($m['service']['tip'])) - $m['service']['discount'];

           // if($this->lang === 'EN'){
           //     $m['service']->status = ucfirst($m['service']->status);
           // }else{
           //     $m['service']->status = $this->statusChinese($m['service']->status);
           // }

           $members[$ctrM] = $m;
           $ctrM++;
         }
         $p['members'] =  $members;
         $temPackage[$j] = $p;
         $j++;
       }

       $temp['bydates'] =  $temPackage;
       //$temp['total_service_cost'] = $totalServiceCost;
       $response[$ctr] =  $temp;
       $ctr = $ctr + 1;

   }
    return $response;
    //return  $this->data;


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
          'services' => $data,
          'group' => $this->group,
          'lang' => $lang
      ]);

  }

  // public function sheets(): array
  //   {
  //       $sheets = [];
  //
  //
  //
  //       foreach ($this->logs as $key => $value) {
  //           $name = $value['first_name'];
  //           $sheets[] = new ServiceSheet(, $name);
  //       }
  //
  //       return $sheets;
  //   }



}

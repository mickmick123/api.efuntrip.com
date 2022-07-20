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

class ServiceByClient implements FromView, WithEvents, ShouldAutoSize
{

  public function __construct(int $id, string $lang, array $data)
  {
      $this->id = $id;
      $this->lang = $lang;
      $this->services = [];
      $this->data = $data;
  }

  public function registerEvents(): array
  {

      return [

          BeforeExport::class => function(BeforeExport $event) {
            $event->writer->getProperties()->setCreator('4ways')
                ->setTitle("Transaction History")
                ->setSubject("Office 2007 XLSX Test Document");

          },

          AfterSheet::class => function(AfterSheet $event) {

              $sheet = $event->sheet->getDelegate();
              $sheet->getColumnDimension('A')->setAutoSize(false);
              $sheet->getColumnDimension('A')->setWidth(25);
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
        if($s == 'released'){
            $stat = '已发行';
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


  public function services(){
    $response = $this->data;
    return $response;

  }



  public function view(): View
  {

    $lang = [];

    if($this->lang === 'EN'){
        $lang['_date_time'] = 'Date and Time';
        $lang['_load'] = 'Load';
        $lang['_client_name'] = 'Client Name';
        $lang['_service_name'] = 'Service Name';
        $lang['_amount_paid'] = 'Amount Paid';
        $lang['_sub_total'] = 'Sub Total';
        $lang['_previous_balance'] = 'Previous Balance';
        $lang['_current_balance'] = 'Current Balance';
        $lang['_operator'] = 'Operator';
        $lang['_source'] = 'Source';
    }else{
        $lang['_date_time'] = 'Date and Time';
        $lang['_load'] = 'Load';
        $lang['_client_name'] = 'Client Name';
        $lang['_service_name'] = 'Service Name';
        $lang['_amount_paid'] = 'Amount Paid';
        $lang['_sub_total'] = 'Sub Total';
        $lang['_previous_balance'] = 'Previous Balance';
        $lang['_current_balance'] = 'Current Balance';
        $lang['_operator'] = 'Operator';
        $lang['_source'] = 'Source';
    }


      $services = $this->services();

      return view('export.services', [
          'services' => $services,
          'lang' => $lang
      ]);
  }



}

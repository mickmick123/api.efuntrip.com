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

  public function __construct( string $lang, array $services, array $user)

      $this->lang = $lang;
      $this->services = $services;
      $this->user = $user;
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

              $sheet = $event->sheet->getDelegate();
              $sheet->getColumnDimension('A')->setAutoSize(false);
              $sheet->getColumnDimension('A')->setWidth(25);

              $sheet->getColumnDimension('C')->setAutoSize(false);
              $sheet->getColumnDimension('C')->setWidth(20);

              $sheet->getColumnDimension('D')->setAutoSize(false);
              $sheet->getColumnDimension('D')->setWidth(15);

              $sheet->getColumnDimension('E')->setAutoSize(false);
              $sheet->getColumnDimension('E')->setWidth(15);

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
        $lang['_payment'] = 'Payment';
        $lang['_discount'] = 'Discount';
        $lang['_service_sub'] = 'Service Sub Total';
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
        $lang['_payment'] = '付款';
        $lang['_service_sub'] = '服务小计';
        $lang['_discount'] = '折扣';
    }


    //   $data = $this->members($this->id);

    //   $transactions = $this->transactions($this->id);

      return view('export.user', [
        'services' => $this->services,
        'user' => $this->user,
        'lang' => $lang
      ]);
  }



}

<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FinancialExport implements FromView, WithEvents, ShouldAutoSize
{
    protected $data;
    protected $data2;
    protected $lang;

    public function __construct(array $data, array $data2, string $lang)
    {
        $this->data = $data;
        $this->data2 = $data2;
        $this->lang = $lang;
    }

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function(BeforeExport $event) {
                $event->writer->getProperties()->setCreator('4ways')
                    ->setTitle("Financial Monitor")
                    ->setSubject("Office 2007 xlsx Document");
            },

            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells("A1:B3");

                for($i=5;$i<count($this->data) + 6;$i++){
                    $sheet->mergeCells("B$i:C$i");
                    $sheet->getColumnDimension("B")->setAutoSize(false);
                    $sheet->getColumnDimension("B")->setWidth(25);
                    $sheet->getColumnDimension("C")->setAutoSize(false);
                    $sheet->getColumnDimension("C")->setWidth(25);
                }
                $sheet->getColumnDimension("D")->setAutoSize(false);
                $sheet->getColumnDimension("D")->setWidth(15);
                $sheet->getColumnDimension("E")->setAutoSize(false);
                $sheet->getColumnDimension("E")->setWidth(10);
                $sheet->mergeCells("D1:E1");
                $sheet->mergeCells("D2:E2");
                $sheet->mergeCells("D3:E3");
                $sheet->getColumnDimension("F")->setAutoSize(false);
                $sheet->getColumnDimension("F")->setWidth(25);
                $sheet->getColumnDimension("G")->setAutoSize(false);
                $sheet->getColumnDimension("G")->setWidth(25);
                $sheet->getColumnDimension("H")->setAutoSize(false);
                $sheet->getColumnDimension("H")->setWidth(25);
                $sheet->getColumnDimension("I")->setAutoSize(false);
                $sheet->getColumnDimension("I")->setWidth(25);
            },
        ];
    }

    public function view(): View
    {
        return view('export.financial_monitor', [
            'lang' => self::getLang($this->lang),
            'data' => self::formatData($this->data, $this->lang),
            'data2' => $this->data2
        ]);
    }

    public static function getLang($_lang){
        $lang = [];
        if($_lang === 'en'){
            $lang['_row'] = 'Row';
            $lang['_dateCreated'] = 'Date Created';
            $lang['_dateUpdated'] = 'Date Updated';
            $lang['_transactionDesc'] = 'Transaction Description';
            $lang['_type'] = 'Type';
            $lang['_storage'] = 'Storage';
            $lang['_cashDeposit'] = 'Cash Deposit';
            $lang['_refund'] = 'Refund';
            $lang['_processBudgetReturn'] = 'Process Budget Return';
            $lang['_processCost'] = 'Process Cost';
            $lang['_adminBudgetReturn'] = 'Admin Budget Return';
            $lang['_borrowedAdminCost'] = 'Borrowed Admin Cost';
            $lang['_adminCost'] = 'Admin Cost';
            $lang['_bankDepositPayment'] = 'Bank Deposit/Payment';
            $lang['_bankCost'] = 'Bank Cost';
            $lang['_depositOtherMatter'] = 'Deposit Other Matter';
            $lang['_costOtherMatter'] = 'Cost Other Matter';
            $lang['_additionalBudget'] = 'Additional Budget';
            $lang['_adminTotal'] = 'Admin Total';
            $lang['_cashBalance'] = 'Cash Balance';
            $lang['_totalBankBalance'] = 'Total Bank Balance';
            $lang['_metroBankBalance'] = 'Metro Bank Balance';
            $lang['_securityBankBalance'] = 'Security Bank Balance';
            $lang['_aubBalance'] = 'AUB Balance';
            $lang['_eastWestBalance'] = 'East West Balance';
            $lang['_chinaBankBalance'] = 'China Bank Balance';
            $lang['_pnbBalance'] = 'PNB Balance';
            $lang['_total'] = 'Total';
            $lang['_checks'] = 'Checks';
            $lang['_financialMonitor'] = 'Financial Monitor';
            $lang['_metroBank'] = 'Metro Bank';
            $lang['_securityBank'] = 'Security Bank';
            $lang['_eastWest'] = 'Eastwest';
            $lang['_chinaBank'] = 'China Bank';
            $lang['_totalCashBalance'] = 'Total Cash Balance';
            $lang['_credit'] = 'Credit';
            $lang['_debit'] = 'Debit';
        }else{
            $lang['_row'] = '行';
            $lang['_dateCreated'] = '创建日期';
            $lang['_dateUpdated'] = '更新日期';
            $lang['_transactionDesc'] = '交易说明';
            $lang['_type'] = '类型';
            $lang['_storage'] = '存储';
            $lang['_cashDeposit'] = '现金存款';
            $lang['_refund'] = '退款';
            $lang['_processBudgetReturn'] = '流程预算报表';
            $lang['_processCost'] = '流程成本';
            $lang['_adminBudgetReturn'] = '管理员预算报表';
            $lang['_borrowedAdminCost'] = '借用管理费用';
            $lang['_adminCost'] = '管理员费用';
            $lang['_bankDepositPayment'] = '银行存款/付款';
            $lang['_bankCost'] = '银行费用';
            $lang['_depositOtherMatter'] = '存款其他事项';
            $lang['_costOtherMatter'] = '成本其他事项';
            $lang['_additionalBudget'] = '额外预算';
            $lang['_adminTotal'] = '管理员总计';
            $lang['_cashBalance'] = '现金余额';
            $lang['_totalBankBalance'] = '银行总余额';
            $lang['_metroBankBalance'] = '大都会银行余额';
            $lang['_securityBankBalance'] = '担保银行余额';
            $lang['_aubBalance'] = 'AUB余额';
            $lang['_eastWestBalance'] = '东西平衡';
            $lang['_chinaBankBalance'] = '中国银行余额';
            $lang['_pnbBalance'] = 'PNB余额';
            $lang['_total'] = '总';
            $lang['_checks'] = '支票';
            $lang['_financialMonitor'] = '财务监控';
            $lang['_metroBank'] = '都市银行';
            $lang['_securityBank'] = '担保银行';
            $lang['_eastWest'] = '东西';
            $lang['_chinaBank'] = '中国银行';
            $lang['_totalCashBalance'] = '现金余额合计';
            $lang['_credit'] = '信用';
            $lang['_debit'] = '借方';
        }
        return $lang;
    }

    public static function formatData($data, $lang) {
        if (!$data) {
            return array();
        }
        $re = array();
        foreach ($data as $k => $v) {
            $v = self::formatOneData($v, $lang);
            $re[$k] = $v;
        }
        return $re;
    }

    public static function formatOneData($item, $lang) {
        if (!$item) {
            return array();
        }

        $item["credit"] = $item["cash_client_depo_payment"]
                        + $item["cash_client_process_budget_return"]
                        + $item["cash_admin_budget_return"]
                        + $item["bank_client_depo_payment"]
                        + $item["deposit_other"];
        $item["debit"] = $item["cash_client_refund"]
                        + $item["cash_process_cost"]
                        + $item["cash_admin_cost"]
                        + $item["borrowed_admin_cost"]
                        + $item["bank_cost"]
                        + $item["cost_other"]
                        + $item["additional_budget"];

        if($lang !== "en"){
            if($item["cat_type"] == "initial"){
                $item["cat_type"] = "初始";
            }else if($item["cat_type"] == "process"){
                $item["cat_type"] = "处理";
            }else if($item["cat_type"] == "admin"){
                $item["cat_type"] = "管理员";
            }else if($item["cat_type"] == "other"){
                $item["cat_type"] = "其他";
            }

            if($item["cat_storage"] == "bank"){
                $item["cat_storage"] = "银行";
            }else if($item["cat_storage"] == "cash"){
                $item["cat_storage"] = "现金";
            }else if($item["cat_storage"] == "alipay"){
                $item["cat_storage"] = "支付宝";
            }
        }else{
            $item["cat_type"] = ucfirst($item["cat_type"]);
            $item["cat_storage"] = ucfirst($item["cat_storage"]);
        }

        return $item;
    }

}

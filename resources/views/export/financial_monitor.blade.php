<table>
    <thead>
        <tr>
            <td style="text-align:center;font-size: 16px;font-weight: bold" valign="center">{{ $lang['_financialMonitor'] }}</td>
            <td></td>
            <td style="text-align:right;">{{ $lang['_metroBank'] }}</td>
            <td style="font-weight: bold">{{ $data2["metroBank"] }}</td>
            <td></td>
            <td style="text-align:right;">{{ $lang['_eastWest'] }}</td>
            <td style="font-weight: bold">{{ $data2["eastWest"] }}</td>
            <td style="text-align:right;">{{ $lang['_totalBankBalance'] }}</td>
            <td style="font-weight: bold">{{ $data2["bank_balance"] }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="text-align:right;">{{ $lang['_securityBank'] }}</td>
            <td style="font-weight: bold">{{ $data2["securityBank"] }}</td>
            <td></td>
            <td style="text-align:right;">{{ $lang['_chinaBank'] }}</td>
            <td style="font-weight: bold">{{ $data2["chinaBank"] }}</td>
            <td style="text-align:right;">{{ $lang['_totalCashBalance'] }}</td>
            <td style="font-weight: bold">{{ $data2["cash_balance"] }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="text-align:right;">AUB</td>
            <td style="font-weight: bold">{{ $data2["aub"] }}</td>
            <td></td>
            <td style="text-align:right;">PNB</td>
            <td style="font-weight: bold">{{ $data2["pnb"] }}</td>
            <td style="text-align:right;">{{ $lang['_total'] }}</td>
            <td style="font-weight: bold">{{ $data2["total_balance"] }}</td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr> <!--
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_row'] }}</b></th> -->
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_dateCreated'] }}</b></th> <!--
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_dateUpdated'] }}</b></th> -->
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_transactionDesc'] }}</b></th>
            <th style="border: 1px solid #6c757d"></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_type'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_storage'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_cashDeposit'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_refund'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_processBudgetReturn'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_processCost'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_adminBudgetReturn'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_borrowedAdminCost'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_adminCost'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_bankDepositPayment'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_bankCost'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_depositOtherMatter'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_costOtherMatter'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_additionalBudget'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_adminTotal'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_cashBalance'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_totalBankBalance'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_metroBankBalance'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_securityBankBalance'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_aubBalance'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_eastWestBalance'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_chinaBankBalance'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_pnbBalance'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_total'] }}</b></th>
            <th style="text-align:center; background-color:#63b8d5;border: 1px solid #6c757d"><b>{{ $lang['_checks'] }}</b></th>
        </tr>
    </thead>
    <tbody>
    {{$i=1}}
    @foreach($data as $d)
        <tr> <!--
            <td style="text-align:center;border: 1px solid #6c757d">{{$i}}</td> -->
            <td style="text-align:center;border: 1px solid #6c757d">{{$d['created_at']}}</td> <!--
            <td style="text-align:center;border: 1px solid #6c757d">{{$d['updated_at']}}</td> -->
            <td style="border: 1px solid #6c757d">{{$d['trans_desc']}}</td>
            <td style="border: 1px solid #6c757d"></td>
            <td style="text-align:center;border: 1px solid #6c757d">{{$d['cat_type']}}</td>
            <td style="text-align:center;border: 1px solid #6c757d">{{$d['cat_storage']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['cash_client_depo_payment']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['cash_client_refund']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['cash_client_process_budget_return']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['cash_process_cost']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['cash_admin_budget_return']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['borrowed_admin_cost']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['cash_admin_cost']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['bank_client_depo_payment']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['bank_cost']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['deposit_other']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['cost_other']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['additional_budget']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['admin_total']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['cash_balance']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['bank_balance']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['bank_metrobank']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['bank_security']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['bank_aub']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['bank_ew']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['bank_chinabank']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['bank_pnb']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['total']}}</td>
            <td style="border: 1px solid #6c757d">{{$d['postdated_checks']}}</td>
        </tr>
        {{$i++}}
    @endforeach
    </tbody>
</table>

<style>
    .border {
        border: 1px solid #6c757d
    }
</style>

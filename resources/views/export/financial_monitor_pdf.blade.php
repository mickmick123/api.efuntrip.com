<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    <meta charset="UTF-8">
    <style type="tet/css">

        @font-face {
            font-family: SimHei;
            src: url('{{base_path().'/public/assets/'}}fonts/simhei.ttf') format('truetype');
        }

        * {
            font-family: SimHei !important;
        }

        .header-text {
            font-size: 16px;
            font-family: Bahnschrift, Arial, Helvetica, sans-serif !important;
            src: url('{{base_path().'/public/assets/'}}fonts/BAHNSCHRIFT.ttf') format('truetype');
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
<div id="watermark">
    <img src="{{ $watermark }}" height="100%" width="100%" />
    <img src="{{ $logo }}" id="logo" />
    <div id="header">
        <div><label class="header-text">ALL DIRECTIONS TRAVEL AND TOUR INC.</label></div>
        <div><label class="address-text">U110-111 Balagtas St., Balagtas Villas, Brgy. 15 San Isidro, Pasay City, Metro Manila</label><br/></div>
        <div><label class="address-text">(02)8-354-8021</label></div>
        <div><label class="address-text">alldirections.travelandtour@gmail.com</label></div>
    </div>
</div>

<div id="footer">
    <div id="footer-info">
        <div><label class="footer-header-text">ALL DIRECTIONS TRAVEL AND TOUR INC.</label></div>
        <div><label class="address-text">U110-111 Balagtas St., Balagtas Villas, Brgy. 15 San Isidro, Pasay City, Metro Manila</label><br/></div>
        <div><label class="address-text">(02)8-354-8021</label></div>
        <div><label class="address-text">alldirections.travelandtour@gmail.com</label></div>
    </div>
</div>
<table><tr><td style="font-size: 16px; font-weight: bold">{{$lang['_financialMonitor']}}</td></tr></table>
<table>
    <thead>
        <tr>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_dateCreated']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;" colspan="2">{{$lang['_transactionDesc']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_storage']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_credit']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_debit']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_cashBalance']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_totalBankBalance']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_metroBankBalance']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_securityBankBalance']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_aubBalance']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_eastWestBalance']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_chinaBankBalance']}}</th>
            <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;">{{$lang['_pnbBalance']}}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $d)
        <tr>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['created_at']}}</td>
            <td class="borderBottom" style="border-right: 1px solid #e0e0e0;" colspan="2">{{$d['trans_desc']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['cat_storage']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['credit']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['debit']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['cash_balance']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['bank_balance']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['bank_metrobank']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['bank_security']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['bank_aub']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['bank_ew']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['bank_chinabank']}}</td>
            <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{$d['bank_pnb']}}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>

<style type="text/css">
    label{
        font-size: 11px;
    }

    table{
        width:100%;
        font-size: 11px;
    }

    table:last-child {
        margin-top: -80px;
    }

    table:first-child {
        margin-top: -25px;
    }

    td{
        font-size: 10px;
        padding:2px;
        width: 33.33%;
    }

    span {
        margin-left: 20px;
    }

    .borderBottom{
        border-bottom: 1px solid #e0e0e0;
    }

    .borderBottom:first-child{
        border-left: 1px solid #e0e0e0;
    }

    @page {
        margin: 0 0;
    }

    body {
        margin: 4.4cm 1cm 2.2cm 1cm;
    }

    #watermark {
        position: fixed;
        bottom:   0;
        left:     0;
        top: 0;
        width:    100%;
        height:   29.73cm;
        z-index:  -1000;
    }
    #header {
        position: fixed;
        top: 30px;
        left: 150px;
    }

    #footer {
        bottom: 0;
        position: fixed;
        /*width:    21.1cm;*/
        width:    29.7cm;
        /*height:   29.73cm;*/
        margin-right: auto;
        margin-left: auto;
        z-index:  -900;
    }

    #footer > div > div {
        text-align: center;
    }
    #footer-info {
        position: fixed;
        bottom: 60px;
        width: 29.7cm;
    }

    #header > div {
        text-align: center;
    }

    #logo{
        width:80px;
        height: 82px;
        position: fixed;
        top: 28px;
        left: 80px;
    }

    .footer-header-text {
        font-size: 11px;
        font-family: Arial, Helvetica, sans-serif !important;;
        font-weight: bold;
        text-align: center;
    }

    .address-text {
        font-size: 8px;
        font-family: Arial, Helvetica, sans-serif !important;;
        text-align: center;
        width: 100%;

    }
</style>

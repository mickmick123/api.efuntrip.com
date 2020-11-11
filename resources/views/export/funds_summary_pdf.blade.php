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
            font-weight: "bold";
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

<div><label class="title-text">FUNDS LOGS</label></div>
<table>
    <thead>
    <tr>
        <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;" colspan="8"><b>Type</b></th>
        <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;" colspan="8"><b>Amount</b></th>
        <th class="borderBottom" style="text-align:center; background-color:#63b8d5; border-right: 1px solid #e0e0e0;" colspan="8"><b>Date</b></th>
    </tr>
    </thead>
    <tbody>
    @foreach( $result["list"] as $row )
    <tr style="{{$row["type"]=='Refund'?'color: #d9534f;':''}}">
        <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;" colspan="8">{{ $row["type"] }}</td>
        <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;" colspan="8">{{ number_format($row["amount"],2) }}</td>
        <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;" colspan="8">{{ $row["created_at"] }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
<br />
<table>
    <tr>
        <td></td>
        <td style="text-align: right; border-bottom: 1px solid #e0e0e0;">TOTAL PAYMENTS</td>
        <td style="border-bottom: 1px solid #e0e0e0;"><span>{{number_format($result["total_payment"],2)}}</span></td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align: right; border-bottom: 1px solid #e0e0e0;">REMAINING EWALLET</td>
        <td style="border-bottom: 1px solid #e0e0e0;"><span>{{number_format($result["remainingEwallet"],2)}}</span></td>
    </tr>
    <tr><td></td></tr>
    <tr>
        <td></td>
        <td style="text-align: right; color: #d9534f; border-style: none none double none;">TOTAL PAYMENT DONE</td>
        <td style="color: #d9534f; border-style: none none double none;"><span>{{number_format($result["total_payment"]-$result["remainingEwallet"],2)}}</span></td>
    </tr>
</table>

</body>

<style type="text/css">
    label{
        font-size: 11px;
    }

    table{
        /*border: 1px solid #e0e0e0;*/
        width:100%;
        font-size: 11px;
        /*margin-left: 10px;*/
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
        margin: 0cm 0cm;
    }

    body {
        margin-top:    4.4cm;
        margin-bottom: 2.2cm;
        margin-left:   1cm;
        margin-right:  1cm;
    }

    #watermark {
        position: fixed;
        bottom:   0px;
        left:     0px;
        top: 0px;
        width:    21.1cm;
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
        width:    21.1cm;
        height:   29.73cm;
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
        width:    21.1cm;
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

    .footer-header-text, .title-text {
        font-size: 11px;
        font-family: Arial, Helvetica, sans-serif !important;;
        font-weight: "bold";
        text-align: center;
    }

    .address-text {
        font-size: 8px;
        font-family: Arial, Helvetica, sans-serif !important;;
        text-align: center;
        width:'100%';

    }
</style>

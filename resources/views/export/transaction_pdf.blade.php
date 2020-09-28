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

<?php
    $currentDate = "";
?>

@foreach($transactions as $t)

<?php
 $hasSameDate = false;
  if($currentDate == $t['display_date']){
      $hasSameDate = true;
  }
  $currentDate = $t['display_date'];
?>

<div class="container">


    @if(!$hasSameDate)
      <div class="title"><b style="color:#1d8ce0">{{ $t['display_date'] }}</b></div>
    @endif

      <div class="content">
            <div class="col1">
                <div><label>{{ $lang['_current_balance'] }} : <b>Php {{ (($t['data']['balance'] > 0) ? "+" : "") . number_format($t['data']['balance'],2) }}</b></label></div>
                <div><label>{{ $lang['_previous_balance'] }} : <b>Php {{ (($t['data']['prevbalance'] > 0) ? "+" : ""). number_format($t['data']['prevbalance'],2) }}</b></label></div>
                <div><label style="color: {{ ($t['data']['type'] != 'payment') ? '#0db502' : 'red' }}">{{ ($t['data']['type'] != 'payment') ? ($lang['_load'] .": Php +". number_format($t['data']['amount'],2)) : ($lang['_amount_paid'] .": Php ". number_format($t['data']['amount'],2)) }}</label></div>
                <div><label>{{ $lang['_type'] }} : <b>{{ ucfirst($t['data']['type']) }}</b></label></div>
            </div>
            <div class="col2">
              <div style="padding:5px; padding-top: 0px;">


                <div style="padding:5px; padding-top: 0px;"><label><b>{{ $t['data']['head'] }}</b></label></div>

                <div class="divider"></div>


                @if($t['data']['type'] == 'payment')
                  @foreach($t['data']['body'] as $s)
                      <table>
                        <tr>
                          <td  style="text-align:center;"><label><b>{{ $s['services']['name'] }}</b></label></td>
                          <td  style="text-align:center;"><label><b>{{ $s['services']['detail'] }}</b></label></td>
                          <td  style="text-align:center;"><label><b>Php {{ number_format($s['amount'],2) }}</b></label></td>
                        </tr>
                     </table>
                  @endforeach


                @endif

                <div style="padding:5px; text-align: right;">
                    <label style="padding-right:20px;">{{ $lang['_source'] }} : <b style="text-align: right; color: #0db502">{{ ($t['data']['body'] != '') ? $t['data']['body'][0]['log_type'] : $t['data']['storage'] }}  </b></label>
                    <label>{{ $lang['_operator'] }} : <b> {{ $t['data']['processor'] }}</b></label>
                </div>

              </div>
            </div>
      </div>

</div>

@endforeach

<!--<table>
    <thead>
    <tr>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_date_time'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_load'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_client_name'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_service_name'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_amount_paid'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_sub_total'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_previous_balance'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_current_balance'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_operator'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_source'] }}</b></th>
    </tr>
    </thead>
    <tbody>
    @foreach($transactions as $t)

    <tr>
        <td colspan="10"></td>
    </tr>



    <tr>
        <td  style="text-align:center; background-color:#cccccc;" ><b>{{ $t['display_date'] }}</b></td>
        <td  style="text-align:center; background-color:#cccccc;">{{ ($t['data']['type'] != 'payment') ? $t['data']['amount'] : '' }}</td>
        <td  style="text-align:center; background-color:#cccccc;"></td>
        <td  style="text-align:center; background-color:#cccccc;"></td>
        <td  style="text-align:center; background-color:#cccccc;"></td>
        <td  style="text-align:center; background-color:#cccccc;">{{ $t['data']['amount'] }}</td>
        <td  style="text-align:center; background-color:#cccccc;">{{ $t['data']['prevbalance'] }}</td>
        <td  style="text-align:center; background-color:#cccccc;">{{ $t['data']['balance'] }}</td>
        <td  style="text-align:center; background-color:#cccccc;">{{ $t['data']['processor'] }}</td>
        <td  style="text-align:center; background-color:#cccccc;">{{ ($t['data']['body'] != '') ? $t['data']['body'][0]['log_type'] : $t['data']['storage'] }}</td>
   </tr>


      @if($t['data']['type'] == 'payment')

        @foreach($t['data']['body'] as $s)
        <tr>
            <td  class="borderBottom" style="text-align:center;"></td>
            <td  class="borderBottom" style="text-align:center;"></td>
            <td  class="borderBottom" style="text-align:center;"><b>{{ $s['services']['name'] }}</b></td>
            <td  class="borderBottom" style="text-align:center;"><b>{{ $s['services']['detail'] }}</b></td>
            <td  class="borderBottom" style="text-align:center;"><b>{{ $s['amount'] }}</b></td>
            <td  class="borderBottom" style="text-align:center;"></td>
            <td  class="borderBottom" style="text-align:center;"></td>
            <td  class="borderBottom" style="text-align:center;"></td>
            <td  class="borderBottom" style="text-align:center;"></td>
            <td  class="borderBottom" style="text-align:center;"></td>
        </tr>
        @endforeach

      @endif

    @endforeach

    </tbody>
</table> -->

</body>

<style type="text/css">

.container{
  /*padding:20px;*/
}

.title{
   background-color: #e0e0e0;
   padding:5px;
   width:100%;
   font-size: 14px;
}

.red{
  color: red;
}

.content:after {
  content: "";
  display: table;
  clear: both;
  padding:5px;
}

.col1 {
  float: left;
  width: 27%;
  margin-top:2%;
  border: 2px solid #e0e0e0;
  padding:5px;
}

.col2 {
  float: left;
  width: 68%;
  border: 2px solid #e0e0e0;
  padding:5px;
  margin-left: 1%;
}

label{
  font-size: 11px;
}

.divider{
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 5px;
}

table{
  border: 1px solid #e0e0e0;
  width:100%;
  font-size: 11px;
}

td{
  font-size: 10px;
  padding:2px;
}

.borderBottom{
    border-bottom: 1px solid #e0e0e0;
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

 .footer-header-text {
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

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

<table>
    <thead>
    <tr>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_date'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_service'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_status'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_charge'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_discount'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_service_sub'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_group_total'] }}</b></th>
    </tr>
    </thead>
    <tbody>
    @foreach($members as $member)
        <tr>
            <td colspan="7"></td>
        </tr>
        <tr >
            <td colspan="7" style="text-align:center;  background-color:#63b8d5"><b>{{ $member['name'] }}</b></td>
        </tr>


          @foreach($member['packages'] as $service)
          <tr>
                <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0; ">{{ $service['datetime'] }}</td>
                <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $service['detail'] }}</td>
                <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $service['status'] }}</td>
                <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $service['package_cost'] }}</td>
                <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $service['discount']  }}</td>
                <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $service['service_cost']  }}</td>
                <td class="borderBottom" style="text-align:center">{{ $service['total_service_cost'] }}</td>
          </tr>

          @endforeach

          <tr>
              <td class="borderBottom" colspan="3" style="border-right: 1px solid #e0e0e0; text-align:center"><b> {{ $lang['_member_subtotal'] }} </b></td>
              <td class="borderBottom" colspan="4" style="text-align:center"><b>{{ $member['total_service_cost'] }}</b></td>
          </tr>

          <tr>
              <td></td>
          </tr>
    @endforeach


    </tbody>
</table>
</body>


<style>
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

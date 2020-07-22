<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style type="tet/css">
  @font-face {
     font-family: SimHei;
     src: url('{{base_path().'/public/assets/'}}fonts/simhei.ttf') format('truetype');
  }

  * {
    font-family: SimHei !important;
  }
</style>
</head>
<body>
<div id="watermark">
            <img src="{{ $watermark }}" height="100%" width="100%" />
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
                <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $service['service_cost']  }}</td>
                <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $service['discount']  }}</td>
                <td class="borderBottom" style="text-align:center">-{{ $service['total_service_cost'] }}</td>
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
  font-family: Arial, Helvetica, sans-serif;
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

           /**
           * Define the real margins of the content of your PDF
           * Here you will fix the margins of the header and footer
           * Of your background image.
           **/
           body {
               margin-top:    4.4cm;
               margin-bottom: 2.2cm;
               margin-left:   1cm;
               margin-right:  1cm;
           }

           /**
           * Define the width, height, margins and position of the watermark.
           **/
           #watermark {
               position: fixed;
               bottom:   0px;
               left:     0px;
               /** The width and height may change
                   according to the dimensions of your letterhead
               **/
               width:    21.8cm;
               height:   30cm;

               /** Your watermark should be behind every content**/
               z-index:  -1000;
           }
</style>

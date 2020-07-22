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
            <img src="{{ $banner }}" height="100%" width="100%" />
</div>

<table >
    <thead>
    <tr>
        <th colspan="4" style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_servic_name'] }}</b></th>
        <th colspan="7" style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_total_service_cost'] }}</b></th>
    </tr>
    </thead>
    <tbody>

    <tr>
        <td colspan="11"></td>
    </tr>

    @foreach($services as $service)
        <tr >
            <td colspan="4" style="text-align:center; background-color:#63b8d5"><b>{{ $service['detail'] }}</b></td>
            <td colspan="7" style="text-align:center; background-color:#63b8d5"><b>-{{ $service['total_service_cost'] }}</b></td>
        </tr>

        <tr >
            <td colspan="2" style="text-align:center; border-right: 1px solid #e0e0e0;" ><b>{{ $lang['_package'] }}</b></td>
            <td colspan="1" style="text-align:center; border-right: 1px solid #e0e0e0;"><b>{{ $lang['_status'] }}</b></td>
            <td colspan="2" style="text-align:center; border-right: 1px solid #e0e0e0;"><b>{{ $lang['_charge'] }}</b></td>
            <td colspan="2" style="text-align:center; border-right: 1px solid #e0e0e0;"><b>{{ $lang['_discount'] }}</b></td>
            <td colspan="2" style="text-align:center; border-right: 1px solid #e0e0e0;"><b>{{ $lang['_service_sub'] }}</b></td>
            <td colspan="2" style="text-align:center"><b>{{ $lang['_group_total_bal'] }}</b></td>
        </tr>


          @foreach($service['bydates'] as $bydate)

           <tr>
                <td colspan="11" style="text-align:center; background-color:#d5d0b5"><b>{{ $bydate['sdate'] }}</b></td>
           </tr>

            @foreach($bydate['members'] as $member)
             <tr>
                  <td colspan="11" style="text-align:left"  class="borderBottom"><b>{{ $member['name'] }}</b></td>
             </tr>
             <tr>
                  <td class="borderBottom" colspan="2" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $member['service']['tracking'] }}</td>
                  <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;" >{{ $member['service']['status']  }}</td>
                  <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;" colspan="2">{{ $member['service_cost']  }}</td>
                  <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;" colspan="2">{{ $member['discount']  }}</td>
                  <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;" colspan="2">{{ $member['total_charge']  }}</td>
                  <td class="borderBottom" style="text-align:center; " colspan="2">-{{ $member['service']['total_service_cost'] }}</td>

             </tr>
           @endforeach

          @endforeach

    @endforeach


    <tr>
        <td colspan="11"></td>
    </tr>
    <tr>
        <td colspan="11"></td>
    </tr>
    <tr>
        <td colspan="11"></td>
    </tr>
    @foreach($services as $service)
        <tr >
            <td colspan="4" style="text-align:center; background-color:#63b8d5"><b>{{ $service['detail'] }}</b></td>
            <td colspan="7" style="text-align:center; background-color:#63b8d5"><b>-{{ $service['total_service_cost'] }}</b></td>
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

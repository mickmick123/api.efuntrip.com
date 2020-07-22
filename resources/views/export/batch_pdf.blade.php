<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body>
<div id="watermark">
            <img src="{{ $watermark }}" height="100%" width="100%" />
</div>
<table>
    <thead>
    <tr>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_service_date'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_package'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_status'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_details'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_charge'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_discount'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_service_sub'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_group_total_bal'] }}</b></th>
    </tr>
    </thead>
    <tbody>
    @foreach($services as $service)
        <tr><td colspan="8"/></tr>
        <tr >
            <td colspan="8" style="text-align:left; background-color:#d5d0b5"><b>{{ $service['service_date'] }}</b></td>
        </tr>
          @foreach($service['members'] as $member)
          <tr>
                <td colspan="8" class="borderBottom" style="text-align:left"><b >{{ $member['name'] }}</b></td>
          </tr>

             @foreach($member['services'] as $service)
              <tr>
                    <td  colspan="2" class="borderBottom" style="text-align:right; border-right: 1px solid #e0e0e0;">{{ $service['tracking'] }} &nbsp; &nbsp;</td>
                    <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $service['status'] }}</td>
                    <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;"><b>{{ $service['detail'] }}</b></td>
                    <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;"><b>{{ ($service['detail'] === "Deposit" || $service['detail'] === "Payment") ? "+".$service['total_charge']  : "-" .$service['total_charge']  }}</b></td>
                    <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $service['discount']  }}</td>
                    <td class="borderBottom" style="text-align:center; border-right: 1px solid #e0e0e0;">{{ $service['service_cost']  }}</td>
                    <td class="borderBottom" style="text-align:center">{{ $service['total_service_cost'] }}</td>
              </tr>

              @endforeach

          @endforeach


          <tr>
              <td></td>
          </tr>


    @endforeach

    </tbody>
</table>
</body>

<style type="text/css">


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

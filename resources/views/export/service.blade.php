<table>
    <thead>
    <tr>
        <th colspan="5" style="text-align:center; background-color:#63b8d5; column-width: 500px;"><b>{{ $lang['_servic_name'] }}</b></th>
        <th colspan="8" style="text-align:center; background-color:#63b8d5; border-left: 1px solid #ccc; border-right: 1px solid #000000;"><b>{{ $lang['_total_service_cost'] }}</b></th>
    </tr>
    </thead>
    <tbody>

    <tr>
        <td colspan="13"></td>
    </tr>

    @foreach($services as $service)
        <tr >
            <td colspan="5" style="text-align:center; background-color:#63b8d5"><b>{{ $service['detail'] }}</b></td>
            <td colspan="8" style="text-align:center; background-color:#63b8d5; border-left: 1px solid #000000; border-right: 1px solid red;"><b>-{{ $service['total_service_cost'] }}</b></td>
        </tr>

        <tr>
            <td colspan="13"></td>
        </tr>

        <tr >
            <td colspan="2" style="text-align:center"><b>{{ $lang['_package'] }}</b></td>
            <td colspan="1" style="text-align:center"><b>{{ $lang['_status'] }}</b></td>
            <td colspan="2" style="text-align:center"><b>{{ $lang['_charge'] }}</b></td>
            <td colspan="2" style="text-align:center"><b>{{ $lang['_discount'] }}</b></td>
            <td colspan="2" style="text-align:center"><b>{{ $lang['_payment'] }}</b></td>
            <td colspan="2" style="text-align:center"><b>{{ $lang['_service_sub'] }}</b></td>
            <td colspan="2" style="text-align:center"><b>{{ $lang['_group_total_bal'] }}</b></td>
        </tr>


          @foreach($service['bydates'] as $bydate)

           <tr>
                <td colspan="13" style="text-align:center; background-color:#d5d0b5">{{ $bydate['sdate'] }}</td>
           </tr>

            @foreach($bydate['members'] as $member)
             <tr>
                  <td colspan="2" style="text-align:center"><b>{{ $member['name'] }}</b></td>
                  <td colspan="11"></td>
             </tr>
             <tr>
                  <td style="text-align:center;">{{ $member['service']['tracking'] }}</td>
                  <td></td>
                  <td style="text-align:center;" >{{ $member['service']['status']  }}</td>
                  <td style="text-align:center;" colspan="2">{{ $member['service_cost']  }}</td>
                  <td style="text-align:center;" colspan="2">{{ $member['discount']  }}</td>
                  <td style="text-align:center;" colspan="2">{{ $member['service']['payment_amount'] }}</td>
                  <td style="text-align:center" colspan="2">{{ ($member['service']['is_full_payment']) ? 0 : (($member['service']['payment_amount'] > 0) ? "-". ($member['total_charge'] - $member['service']['payment_amount']) : (($member['total_charge'] > 0) ? "-". $member['total_charge'] : 0)) }}</td>
                  <td style="text-align:center;" colspan="2">-{{ $member['service']['total_service_cost'] }}</td>

             </tr>
             <tr>
                   <td colspan="13" ></td>
             </tr>
           @endforeach

          @endforeach

          <tr>
              <td colspan="13"></td>
          </tr>
    @endforeach


    <tr>
        <td colspan="13"></td>
    </tr>
    <tr>
        <td colspan="13"></td>
    </tr>
    <tr>
        <td colspan="13"></td>
    </tr>
    @foreach($services as $service)
        <tr >
            <td colspan="5" style="text-align:center; background-color:#63b8d5"><b>{{ $service['detail'] }}</b></td>
            <td colspan="8" style="text-align:center; background-color:#63b8d5; border-left: 1px solid #000000; border-right: 1px solid red;"><b>-{{ $service['total_service_cost'] }}</b></td>
        </tr>
   @endforeach


    </tbody>
</table>

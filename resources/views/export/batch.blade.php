<table>
    <thead>
    <tr>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_service_date'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_package'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_status'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_details'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_charge'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_discount'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_payment'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_service_sub'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_group_total_bal'] }}</b></th>
    </tr>
    </thead>
    <tbody>
    @foreach($services as $service)
        <tr><td colspan="9"/></tr>
        <tr >
            <td colspan="9" style="text-align:left; background-color:#d5d0b5"><b>{{ $service['service_date'] }}</b></td>
        </tr>


          @foreach($service['members'] as $member)
          <tr>
                <td></td>
                <td style="text-align:center"><b >{{ $member['name'] }}</b></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
          </tr>

          <tr>
             <td colspan="8"></td>
          </tr>
             @foreach($member['services'] as $service)
              <tr>
                    <td></td>
                    <td style="text-align:center">{{ $service['tracking'] }}</td>
                    <td style="text-align:center">{{ $service['status'] }}</td>
                    <td style="text-align:center"><b>{{ $service['detail'] }}</b></td>
                    <td style="text-align:center"><b>{{ ($service['detail'] === "Deposit" || $service['detail'] === "Payment") ? "+".$service['total_charge']  : "-" .$service['total_charge']  }}</b></td>
                    <td style="text-align:center">{{ $service['discount']  }}</td>
                    <td style="text-align:center">{{ $service['payment_amount'] }}</td>
                    <td style="text-align:center">{{ ($service['is_full_payment']) ? 0 : (($service['payment_amount'] > 0) ? "-". ($service['service_cost'] - $service['payment_amount']) : (($service['service_cost'] > 0) ? "-". $service['service_cost'] : 0)) }}</td>
                    <td style="text-align:center">{{ $service['total_service_cost'] }}</td>
              </tr>

              @endforeach
          <tr>
             <td colspan="8"></td>
          </tr>
          @endforeach


          <tr>
              <td></td>
          </tr>


    @endforeach


    </tbody>
</table>

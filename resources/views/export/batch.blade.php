<table>
    <thead>
    <tr>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_service_date'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_package'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_status'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_details'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_charge'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_group_total_bal'] }}</b></th>
    </tr>
    </thead>
    <tbody>
    @foreach($services as $service)
        <tr><td colspan="6"/></tr>
        <tr >
            <td colspan="6" style="text-align:left; background-color:#d5d0b5"><b>{{ $service['service_date'] }}</b></td>
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
             <td colspan="5"></td>
          </tr>
             @foreach($member['services'] as $service)
              <tr>
                    <td></td>
                    <td style="text-align:center">{{ $service['tracking'] }}</td>
                    <td style="text-align:center">{{ $service['status'] }}</td>
                    <td style="text-align:center"><b>{{ $service['detail'] }}</b></td>
                    <td style="text-align:center"><b>{{ ($service['detail'] === "Deposit" || $service['detail'] === "Payment") ? "+".$service['total_charge']  : "-" .$service['total_charge']  }}</b></td>
                    <td style="text-align:center">{{ $service['total_service_cost'] }}</td>
              </tr>

              <tr>
                  <td colspan="5" align="center"><b>{{{ $service['remarks'] }}}</b></td>
              </tr>


              @endforeach
          <tr>
             <td colspan="5"></td>
          </tr>
          @endforeach


          <tr>
              <td></td>
          </tr>


    @endforeach

    <tr >
        <td colspan="5"></td>
    </tr>


    <tr>
        <td colspan="5"></td>
    </tr>
    <tr>
        <td colspan="5"></td>
    </tr>



    </tbody>
</table>

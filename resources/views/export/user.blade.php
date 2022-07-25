<table>
    <tr>
        <td colspan="2" ></td>
        <td colspan="4" ><img src="{{base_path().'/public/images/head.jpg'}}" alt="heading" /></td>
        <td colspan="2" ></td>
    </tr>
</table>
<table>
    <thead>
    <tr>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_date'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_service'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_status'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_charge'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_discount'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_payment'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_service_sub'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_group_total'] }}</b></th>
    </tr>
    </thead>
    <tbody>
    @foreach($members as $member)

        <tr >
            <td colspan="8"></td>
        </tr>
        <tr >
            <td colspan="8" style="text-align:center;  background-color:#63b8d5"><b>{{ $member['name'] }}</b></td>
        </tr>


          @foreach($member['packages'] as $service)
          <tr>
                <td style="text-align:center">{{ $service['datetime'] }}</td>
                <td style="text-align:center">{{ $service['detail'] }}</td>
                <td style="text-align:center">{{ $service['status'] }}</td>
                <td style="text-align:center">{{ $service['package_cost'] }}</td>
                <td style="text-align:center">{{ $service['discount']  }}</td>
                <td style="text-align:center">{{ $service['payment_amount'] }}</td>
                <td style="text-align:center">{{ ($service['is_full_payment']) ? 0 : (($service['payment_amount'] > 0) ? "-". ($service['package_cost'] - $service['payment_amount']) : (($service['package_cost'] > 0) ? "-". $service['package_cost'] : 0)) }}</td>
                <td style="text-align:center">-{{ $service['total_service_cost'] }}</td>
          </tr>

          @endforeach

          <tr>
              <td></td>
              <td style="text-align:center"><b> {{ $lang['_member_subtotal'] }} </b></td>
              <td></td>
              <td style="text-align:center"><b>{{ $member['total_service_cost'] }}</b></td>
              <td></td>
          </tr>

          <tr>
              <td></td>
          </tr>
    @endforeach


    </tbody>
</table>

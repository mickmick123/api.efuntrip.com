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

        <tr >
            <td colspan="7"></td>
        </tr>
        <tr >
            <td colspan="7" style="text-align:center;  background-color:#63b8d5"><b>{{ $member['name'] }}</b></td>
        </tr>


          @foreach($member['packages'] as $service)
          <tr>
                <td style="text-align:center">{{ $service['datetime'] }}</td>
                <td style="text-align:center">{{ $service['detail'] }}</td>
                <td style="text-align:center">{{ $service['status'] }}</td>
                <td style="text-align:center">{{ $service['package_cost'] }}</td>
                <td style="text-align:center">{{ $service['discount']  }}</td>
                <td style="text-align:center">{{ $service['service_cost']  }}</td>
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

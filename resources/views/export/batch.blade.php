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
                <td style="text-align:center"><b >{{ $member->first_name." ".$member->last_name }}</b></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
          </tr>

          <tr>
             <td colspan="5"></td>
          </tr>
             @foreach($member->services as $service)
              <tr>
                    <td></td>
                    <td style="text-align:center">{{ $service->tracking }}</td>
                    <td style="text-align:center">{{ $service->status }}</td>
                    <td style="text-align:center">{{ $service->detail }}</td>
                    <td style="text-align:center">{{ ($service->cost + $service->charge + $service->tip) - $service->discount  }}</td>

                    <td style="text-align:center">-{{ $service->total_service_cost }}</td>
              </tr>

              <tr>
                  <td colspan="5" align="center"><b>{{ $service->remarks }}</b></td>
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
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="2"><b>{{ $lang['_total_complete_cost'] }} {{ $group['total_complete_service_cost'] }}</b></td>
        <td></td>
    </tr>

    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="2"><b>{{ $lang['_total_deposit'] }} {{ $group['total_deposit'] }}</b></td>
        <td></td>
    </tr>

    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="2"><b>{{ $lang['_total_cost'] }} {{ $group['total_cost'] }}</b></td>
        <td></td>
    </tr>

    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="2"><b>{{ $lang['_total_promo'] }} {{ $group['total_discount'] }}</b></td>
        <td></td>
    </tr>

    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="2"><b>{{ $lang['_total_refund'] }}  {{ $group['total_refund'] }}</b></td>
        <td></td>
    </tr>

    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="2"><b>{{ $lang['_total_collectables'] }} {{ $group['total_collectables'] }}</b></td>
        <td></td>
    </tr>


    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="2" style="background-color:#63b8d5"><b>{{ $lang['_total_balance'] }} {{ $group['total_balance'] }}</b></td>
        <td></td>
    </tr>



    <tr>
        <td colspan="5"></td>
    </tr>
    <tr>
        <td colspan="5"></td>
    </tr>

    <tr>
        <td colspan="5"><b>{{ $lang['_transcation_history'] }}</b></td>
    </tr>

    <tr>
          <td style="text-align:center"><b>{{ $lang['_amount'] }}</b></td>
          <td style="text-align:center"><b>{{ $lang['_date'] }}</b></td>
          <td style="text-align:center"><b>{{ $lang['_type'] }}</b></td>
          <td></td>
          <td></td>
    </tr>



    @foreach($transactions as $transaction)
    <tr>
          <td style="text-align:center">{{ $transaction->amount }}</td>
          <td style="text-align:center">{{ $transaction->created_at }}</td>
          <td style="text-align:center">{{ $transaction->type }}</td>
          <td></td>
          <td></td>
    </tr>
    @endforeach

    </tbody>
</table>
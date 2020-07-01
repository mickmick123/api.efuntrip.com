<table>
    <thead>
    <tr>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_date'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_service'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_status'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_charge'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>{{ $lang['_group_total'] }}</b></th>
    </tr>
    </thead>
    <tbody>
    @foreach($members as $member)

        <tr >
            <td colspan="5"></td>
        </tr>
        <tr >
            <td colspan="5" style="text-align:center;  background-color:#63b8d5"><b>{{ $member['name'] }}</b></td>
        </tr>


          @foreach($member['packages'] as $service)
          <tr>
                <td style="text-align:center">{{ $service->datetime }}</td>
                <td style="text-align:center">{{ $service->detail }}</td>
                <td style="text-align:center">{{ $service->status }}</td>
                <td style="text-align:center">{{ $service->package_cost }}</td>
                <td style="text-align:center">-{{ $service->total_service_cost }}</td>
          </tr>

          <tr >
              <td colspan="5" style="text-align:center"><b>{{ $service->remarks }}</b></td>
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
          <td style="text-align:center;  background-color:#63b8d5"><b>{{ $lang['_amount'] }}</b></td>
          <td style="text-align:center;  background-color:#63b8d5"><b>{{ $lang['_date'] }}</b></td>
          <td style="text-align:center;  background-color:#63b8d5"><b>{{ $lang['_type'] }}</b></td>
          <td></td>
          <td></td>
    </tr>


    @foreach($transactions as $transaction)
    <tr>
          <td style="text-align:center;">{{ $transaction->amount }}</td>
          <td style="text-align:center;">{{ $transaction->created_at }}</td>
          <td style="text-align:center;">{{ $transaction->type }}</td>
          <td></td>
          <td></td>
    </tr>
    @endforeach

    </tbody>
</table>

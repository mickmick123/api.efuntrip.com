<table>
    <thead>
    <tr>
        <th colspan="4" style="text-align:center; background-color:#63b8d5; column-width: 500px;"><b>{{ $lang['_servic_name'] }}</b></th>
        <th colspan="3" style="text-align:center; background-color:#63b8d5; border-left: 1px solid #000000; border-right: 1px solid #000000;"><b>{{ $lang['_total_service_cost'] }}</b></th>
    </tr>
    </thead>
    <tbody>

    <tr>
        <td colspan="7"></td>
    </tr>

    @foreach($services as $service)
        <tr >
            <td colspan="4" style="text-align:center; background-color:#63b8d5"><b>{{ $service['detail'] }}</b></td>
            <td colspan="3" style="text-align:center; background-color:#63b8d5; border-left: 1px solid #000000; border-right: 1px solid red;"><b>-{{ $service['total_service_cost'] }}</b></td>
        </tr>

        <tr>
            <td colspan="7"></td>
        </tr>

        <tr >
            <td colspan="2" style="text-align:center"><b>{{ $lang['_package'] }}</b></td>
            <td colspan="1" style="text-align:center"><b>{{ $lang['_status'] }}</b></td>
            <td colspan="2" style="text-align:center"><b>{{ $lang['_charge'] }}</b></td>
            <td colspan="2" style="text-align:center"><b>{{ $lang['_group_total_bal'] }}</b></td>
        </tr>


          @foreach($service['bydates'] as $bydate)

           <tr>
                <td colspan="7" style="text-align:center; background-color:#d5d0b5">{{ $bydate['sdate'] }}</td>
           </tr>

            @foreach($bydate['members'] as $member)
             <tr>
                  <td colspan="2" style="text-align:center"><b>{{ $member['name'] }}</b></td>
                  <td colspan="5"></td>
             </tr>
             <tr>
                  <td style="text-align:center;">{{ $member['service']['tracking'] }}</td>
                  <td></td>
                  <td style="text-align:center;" >{{ $member['service']['status']  }}</td>

                  <td style="text-align:center;" colspan="2">{{ $member['total_charge']  }}</td>
                  <td style="text-align:center;" colspan="2">-{{ $member['service']['total_service_cost'] }}</td>

             </tr>
             <tr>
                   <td colspan="7" ></td>
             </tr>
           @endforeach

          @endforeach


          <tr>
              <td colspan="7"></td>
          </tr>


    @endforeach

    <tr >
        <td colspan="7"></td>
    </tr>

    <tr>
        <td colspan="7"></td>
    </tr>
    <tr>
        <td colspan="7"></td>
    </tr>

    <tr>
        <td colspan="7"><b>{{ $lang['_transcation_history'] }}</b></td>
    </tr>

    <tr>
          <td style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_amount'] }}</b></td>
          <td style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_date'] }}</b></td>
          <td style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_type'] }}</b></td>
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

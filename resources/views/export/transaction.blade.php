<table>
    <tr>
        <td colspan="3" ></td>
        <td colspan="4" ><img src="{{base_path().'/public/images/head.jpg'}}" alt="heading" /></td>
        <td colspan="3" ></td>
    </tr>
</table>
<table>
    <thead>
    <tr>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_date_time'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_load'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_client_name'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_service_name'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_amount_paid'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_sub_total'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_previous_balance'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_current_balance'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_operator'] }}</b></th>
        <th style="text-align:center; background-color:#63b8d5;"><b>{{ $lang['_source'] }}</b></th>
    </tr>
    </thead>
    <tbody>
    @foreach($transactions as $t)

    <tr>
        <td colspan="10"></td>
    </tr>



    <tr>
        <td  style="text-align:center; background-color:#cccccc;" ><b>{{ $t['display_date'] }}</b></td>
        <td  style="text-align:center; background-color:#cccccc;">{{ ($t['data']['type'] != 'payment') ? $t['data']['amount'] : '' }}</td>
        <td  style="text-align:center; background-color:#cccccc;"></td>
        <td  style="text-align:center; background-color:#cccccc;"></td>
        <td  style="text-align:center; background-color:#cccccc;"></td>
        <td  style="text-align:center; background-color:#cccccc;">{{ $t['data']['amount'] }}</td>
        <td  style="text-align:center; background-color:#cccccc;">{{ $t['data']['prevbalance'] }}</td>
        <td  style="text-align:center; background-color:#cccccc;">{{ $t['data']['balance'] }}</td>
        <td  style="text-align:center; background-color:#cccccc;">{{ $t['data']['processor'] }}</td>
        <td  style="text-align:center; background-color:#cccccc;">{{ ($t['data']['body'] != '') ? $t['data']['body'][0]['log_type'] : $t['data']['storage'] }}</td>

    </tr>


      @if($t['data']['type'] == 'payment')

       @if($t['data']['body'] !== '')
        @foreach($t['data']['body'] as $s)
        <tr>
            <td  style="text-align:center;"></td>
            <td  style="text-align:center;"></td>
            <td  style="text-align:center;"><b>{{ $s['services']['name'] }}</b></td>
            <td  style="text-align:center;"><b>{{ $s['services']['detail'] }}</b></td>
            <td  style="text-align:center;"><b>{{ $s['amount'] }}</b></td>
            <td  style="text-align:center;"></td>
            <td  style="text-align:center;"></td>
            <td  style="text-align:center;"></td>
            <td  style="text-align:center;"></td>
            <td  style="text-align:center;"></td>
        </tr>
        @endforeach
        @endif
      @endif

    @endforeach

    </tbody>
</table>

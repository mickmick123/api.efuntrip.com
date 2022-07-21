<table>
    <thead>
    <tr>
        <th style="text-align:center; background-color:#63b8d5"><b>Service Date</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>Detail</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>Tracking</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>Cost</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>Charge</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>Tip</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>Status</b></th>
        <th style="text-align:center; background-color:#63b8d5"><b>Remarks</b></th>
    </tr>
    </thead>
    <tbody>
    @foreach($userdata['services'] as $service)
          <tr>
              <td style="text-align:center">{{ $service['sdate'] }}</td>
              <td style="text-align:center"><b> {{ $service['detail'] }} </b></td>
              <td style="text-align:center">{{ $service['tracking'] }}</td>
              <td style="text-align:center">{{ $service['cost'] }}</td>
              <td style="text-align:center">{{ $service['charge'] }}</td>
              <td style="text-align:center">{{ $service['tip'] }}</td>
              <td style="text-align:center">{{ $service['status'] }}</td>
              <td style="text-align:center">{{ $service['remarks'] }}</td>
          </tr>
    @endforeach


    </tbody>
</table>

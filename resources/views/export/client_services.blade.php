<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<meta charset="UTF-8">
<style type="tet/css">

@font-face {
   font-family: SimHei;
   src: url('{{base_path().'/public/assets/'}}fonts/simhei.ttf') format('truetype');
}

* {
  font-family: SimHei !important;
}

.header-text {
   font-size: 16px;
   font-family: Bahnschrift, Arial, Helvetica, sans-serif !important;
   src: url('{{base_path().'/public/assets/'}}fonts/BAHNSCHRIFT.ttf') format('truetype');
   font-weight: "bold";
   text-align: center;
}
</style>
</head>
<body>
<table>
    <tr>
        <td colspan="8" style="text-align: center; display: flex; align-items: center; justify-content: center"><img style="margin-left: 200px" src="{{base_path().'/public/images/head.jpg'}}" alt="heading" /></td>
    </tr>
</table>
<table>
    <tr>
        <td style="text-align:center; background-color:#63b8d5">Client:</td>
        <td><b>{{ $userdata['full_name'] }}</b></td>
        <td></td>
        <td></td>
        <td style="text-align:center; background-color:#63b8d5">Passport:</td>
        <td><b>{{ $userdata['passport'] }}</b></td>
    </tr>
    <tr>
        <td style="text-align:center; background-color:#63b8d5">Balance:</td>
        <td><b>{{ $userdata['balance'] }}</b></td>
        <td></td>
        <td></td>
        <td style="text-align:center; background-color:#63b8d5">Collectable:</td>
        <td><b>{{ $userdata['collectable'] }}</b></td>
    </tr>
</table>
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
</body>
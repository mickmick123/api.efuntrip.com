<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientReport extends Model
{

    protected $table = 'client_reports';

    protected $fillable = ['detail', 'client_service_id', 'report_id', 'service_procedure_id'];

    public function clientReportDocuments() {
    	return $this->hasMany('App\ClientReportDocument', 'client_report_id', 'id');
    }

    public function clientService() {
    	return $this->belongsTo('App\ClientService', 'client_service_id', 'id');
    }

    public function report() {
    	return $this->belongsTo('App\Report', 'report_id', 'id');
    }

    public function serviceProcedure() {
    	return $this->belongsTo('App\ServiceProcedure', 'service_procedure_id', 'id');
    }

}

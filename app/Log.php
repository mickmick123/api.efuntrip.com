<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{

    protected $table = 'logs';

    protected $fillable = ['client_service_id', 'client_id', 'group_id', 'service_procedure_id', 'processor_id', 'log_type', 'log_group', 'detail', 'detail_cn', 'log_date'];

    public function clientService() {
    	return $this->belongsTo('App\ClientService', 'client_service_id', 'id');
    }

    public function client() {
    	return $this->belongsTo('App\User', 'client_id', 'id');
    }

    public function group() {
    	return $this->belongsTo('App\Group', 'group_id', 'id');
    }

    public function serviceProcedure() {
    	return $this->belongsTo('App\ServiceProcedure', 'service_procedure_id', 'id');
    }

    public function processor() {
    	return $this->belongsTo('App\User', 'processor_id', 'id');
    }

    public function documents() {
    	return $this->belongsToMany('App\Document', 'document_log', 'log_id', 'document_id')->withPivot('count', 'created_at', 'updated_at');
    }

}

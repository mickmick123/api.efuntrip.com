<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceProcedureDocument extends Model
{
    
    protected $table = 'service_procedure_documents';

    protected $fillable = ['service_procedure_id', 'document_id', 'is_required'];

    public function serviceProcedure() {
    	return $this->belongsTo('App\ServiceProcedure', 'service_procedure_id', 'id');
    }

}

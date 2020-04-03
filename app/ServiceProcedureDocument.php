<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceProcedureDocument extends Model
{
    
    protected $table = 'service_procedure_documents';

    protected $fillable = ['service_procedure_id', 'document_id', 'is_required', 'mode', 'required_copies'];

    public function serviceProcedure() {
    	return $this->belongsTo('App\ServiceProcedure', 'service_procedure_id', 'id');
    }

    public function document() {
    	return $this->belongsTo('App\Document', 'document_id', 'id');
    }

}

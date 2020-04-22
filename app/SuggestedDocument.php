<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuggestedDocument extends Model
{
    
    protected $table = 'suggested_documents';

    protected $fillable = ['service_procedure_id', 'document_id', 'points'];

    public $timestamps = false;

    public function serviceProcedure() {
    	return $this->belongsTo('App\ServiceProcedure', 'service_procedure_id', 'id');
    }

    public function document() {
    	return $this->belongsTo('App\Document', 'document_id', 'id');
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientReportDocument extends Model
{

    protected $table = 'client_report_documents';

    protected $fillable = ['client_report_id', 'document_id', 'count'];

    public function clientReport() {
    	return $this->belongsTo('App\ClientReport', 'client_report_id', 'id');
    }

    public function document() {
    	return $this->belongsTo('App\Document', 'document_id', 'id');
    }

}

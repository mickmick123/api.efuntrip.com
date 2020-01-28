<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class ClientReportDocument extends Model
{
    
    use SoftDeletes;

    protected $table = 'client_report_documents';

    protected $fillable = ['client_report_id', 'document_id'];

    public function clientReport() {
    	return $this->belongsTo('App\ClientReport', 'client_report_id', 'id');
    }

    public function document() {
    	return $this->belongsTo('App\Document', 'document_id', 'id');
    }

}

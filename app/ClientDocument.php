<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientDocument extends Model
{
    
    protected $table = 'client_documents';

    protected $fillable = ['client_id', 'client_document_type_id', 'file_path', 'issued_at', 'expired_at'];

    public function client() {
    	return $this->belongsTo('App\User', 'client_id', 'id');
    }

    public function clientDocumentType() {
    	return $this->belongsTo('App\ClientDocumentType', 'client_document_type_id', 'id');
    }

}

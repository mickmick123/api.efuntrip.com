<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientDocument extends Model
{
    
    protected $table = 'client_documents';

    protected $fillable = ['client_id', 'client_document_type_id', 'file_path', 'issued_at', 'expired_at', 'status'];

    public function client() {
    	return $this->belongsTo('App\User', 'client_id', 'id');
    }

    public function clientDocuments() {
        return $this->hasMany('App\ClientDocument', 'client_document_type_id', 'client_document_type_id')->where('status', 1)->orderBy('id', 'desc');
    }

    public function clientDocumentType() {
    	return $this->belongsTo('App\ClientDocumentType', 'client_document_type_id', 'id');
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class ClientDocumentType extends Model
{
    
    use SoftDeletes;

    protected $table = 'client_document_types';

    protected $fillable = ['name'];

    public function clientDocuments() {
        return $this->hasMany('App\ClientDocument', 'client_document_type_id', 'id');
    }
    
}

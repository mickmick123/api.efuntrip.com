<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class ClientDocumentType extends Model
{
    
    use SoftDeletes;

    protected $table = 'client_document_types';

    protected $fillable = ['name'];
    
}

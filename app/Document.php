<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    
    use SoftDeletes;

    protected $table = 'documents';

    protected $fillable = ['title', 'title_cn', 'is_unique'];

    public function clientReportDocuments() {
    	return $this->hasMany('App\ClientReportDocument', 'document_id', 'id');
    }

    public function logs() {
    	return $this->belongsToMany('App\Log', 'document_log', 'document_id', 'log_id')->withPivot('created_at', 'updated_at');
    }

}

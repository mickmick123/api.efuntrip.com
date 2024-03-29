<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    
    use SoftDeletes;

    protected $table = 'documents';

    protected $fillable = ['title', 'title_cn', 'shorthand_name', 'is_unique', 'is_company_document'];

    public function clientReportDocuments() {
        return $this->hasMany('App\ClientReportDocument', 'document_id', 'id');
    }

    public function logs() {
        return $this->belongsToMany('App\Log', 'document_log', 'document_id', 'log_id')->withPivot('count', 'pending_count', 'previous_on_hand', 'created_at', 'updated_at');
        // return $this->belongsToMany('App\Log', 'document_log', 'document_id', 'log_id')->withPivot('count', 'previous_on_hand', 'created_at', 'updated_at');
    }

    public function onHandDocuments() {
        return $this->hasMany('App\OnHandDocument', 'document_id', 'id');
    }

    public function suggestedDocuments() {
        return $this->hasMany('App\SuggestedDocument', 'document_id', 'id');
    }

}

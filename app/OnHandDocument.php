<?php

namespace App;

use App\Http\Controllers\OnHandDocumentController;

use Illuminate\Database\Eloquent\Model;

class OnHandDocument extends Model
{
    
	protected $table = 'on_hand_documents';

    protected $fillable = ['client_id', 'group_id', 'document_id'];

    public static function boot() {
        parent::boot();

        self::created(function($model) {
            OnHandDocumentController::handleCompanyDocument('created', $model);
        });

        self::deleted(function($model) {
            OnHandDocumentController::handleCompanyDocument('deleted', $model);
        });
    }

    public function client() {
    	return $this->belongsTo('App\User', 'client_id', 'id');
    }

    public function group() {
    	return $this->belongsTo('App\Group', 'group_id', 'id');
    }

    public function document() {
    	return $this->belongsTo('App\Document', 'document_id', 'id');
    }

}

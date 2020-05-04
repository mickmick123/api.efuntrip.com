<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class OnHandDocument extends Model
{
    
    use SoftDeletes;
    
	protected $table = 'on_hand_documents';

    protected $fillable = ['client_id', 'document_id', 'count'];

    public function client() {
    	return $this->belongsTo('App\User', 'client_id', 'id');
    }

    public function document() {
    	return $this->belongsTo('App\Document', 'document_id', 'id');
    }

}

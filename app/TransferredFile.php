<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransferredFile extends Model
{

    protected $table = 'transferred_files';

    protected $fillable = ['id', 'sender_id', 'receiver_id', 'client_id', 'document_log_id', 'status'];

    public function documentlog() {
    	return $this->belongsTo('App\DocumentLog', 'document_log_id', 'id');
    }

}

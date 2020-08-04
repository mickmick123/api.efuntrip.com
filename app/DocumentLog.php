<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DocumentLog extends Model
{

    protected $table = 'document_log';

    protected $fillable = ['id', 'document_id', 'log_id', 'count', 'pending_count', 'previous_on_hand'];

}

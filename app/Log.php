<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{

    protected $table = 'logs';

    protected $fillable = ['client_service_id', 'client_id', 'group_id', 'processor_id', 'log_type', 'log_group', 'detail', 'detail_cn', 'log_date'];

}

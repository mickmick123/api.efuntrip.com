<?php

namespace App;

use App\BaseModel;

class LogsAppNotification extends BaseModel
{
    protected $table = 'logs';
    protected $primaryKey = 'id';

    const UPDATED_AT = null;
}

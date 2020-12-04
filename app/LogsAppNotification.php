<?php

namespace App;

use App\BaseModel;

class LogsAppNotification extends BaseModel
{
    protected $table = 'logs_app_notification';
    protected $primaryKey = 'id';

    const UPDATED_AT = null;
}

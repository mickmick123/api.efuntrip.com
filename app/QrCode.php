<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\GroupController;

use App\User;
use App\Group;

class QrCode extends Model
{

    protected $table = 'qr_code';

    protected $fillable = [ 'client_id', 'group_id', 'service_ids'];

    public static function boot(){
        parent::boot();

        static::created(function ($model) {

        });

        static::updated(function ($model) {
            
        });
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\GroupController;

use App\User;
use App\Group;

class ClientEWallet extends Model
{

	use SoftDeletes;

    protected $table = 'client_ewallet';

    protected $fillable = ['type', 'client_id', 'group_id', 'client_service_id', 'amount', 'reason', 'storage_type', 'alipay_reference', 'is_promo'];

    public static function boot(){
        parent::boot();

        static::created(function ($model) {

        });

        static::updated(function ($model) {

        });
    }



    public function client() {
    	return $this->belongsTo('App\User', 'client_id', 'id');
    }

    public function group() {
    	return $this->belongsTo('App\Group', 'group_id', 'id');
    }

    public function clientService() {
    	return $this->belongsTo('App\ClientService', 'client_service_id', 'id');
    }

}

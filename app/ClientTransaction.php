<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class ClientTransaction extends Model
{
    
	use SoftDeletes;

    protected $table = 'client_transactions';

    protected $fillable = ['type', 'client_id', 'group_id', 'client_service_id', 'amount', 'tracking', 'reason', 'storage_type', 'alipay_reference', 'is_commission'];

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

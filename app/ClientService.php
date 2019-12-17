<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientService extends Model
{
    
    protected $table = 'client_services';

    protected $fillable = ['client_id', 'group_id', 'service_id', 'detail', 'cost', 'charge', 'tip', 'com_client', 'com_agent', 'client_com_id', 'agent_com_id', 'status', 'remarks', 'tracking', 'active', 'extend', 'checked'];

    public function agentCom() {
    	return $this->belongsTo('App\User', 'agent_com_id', 'id');
    }

    public function client() {
    	return $this->belongsTo('App\User', 'client_id', 'id');
    }

    public function clientCom() {
    	return $this->belongsTo('App\User', 'client_com_id', 'id');
    }

    public function clientTransactions() {
        return $this->hasMany('App\ClientTransaction', 'client_service_id', 'id');
    }

    public function group() {
    	return $this->belongsTo('App\Group', 'group_id', 'id');
    }

    public function service() {
    	return $this->belongsTo('App\Service', 'service_id', 'id');
    }

}

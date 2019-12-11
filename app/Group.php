<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    
    protected $table = 'groups';

    protected $fillable = ['name', 'leader_id', 'vice_leader_id', 'tracking', 'address', 'risk', 'balance', 'collectables', 'is_shop', 'client_com_id', 'agent_com_id'];

    public function agentCom() {
    	return $this->belongsTo('App\User', 'agent_com_id', 'id');
    }

    public function branches() {
    	return $this->belongsToMany('App\Branch', 'branch_group', 'group_id', 'branch_id');
    }

    public function clients() {
    	return $this->belongsToMany('App\User', 'group_user', 'group_id', 'user_id')->withPivot('total_service_cost');
    }

    public function clientCom() {
    	return $this->belongsTo('App\User', 'client_com_id', 'id');
    }

    public function contactNumbers() {
        return $this->hasMany('App\ContactNumber', 'group_id', 'id');
    }

    public function leader() {
    	return $this->belongsTo('App\User', 'leader_id', 'id');
    }

    public function viceLeader() {
    	return $this->belongsTo('App\User', 'vice_leader_id', 'id');
    }

}

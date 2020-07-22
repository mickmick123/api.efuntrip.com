<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Http\Controllers\GroupController;

use App\Http\Controllers\ClientController;

use App\User;
use App\Group;

class ClientService extends Model
{
    
    protected $table = 'client_services';

    protected $fillable = ['client_id', 'group_id', 'service_id', 'detail', 'cost', 'charge', 'tip', 'com_client', 'com_agent', 'client_com_id', 'agent_com_id', 'status', 'remarks', 'tracking', 'active', 'extend', 'checked'];

    public static function boot() {
        parent::boot();

        static::created(function ($model) {
            static::updateBalanceAndCollectables($model->client_id, $model->group_id);
        });

        self::updated(function($model) {
            $original = $model->getOriginal();
            GroupController::createOrDeleteCommission($model,$original);
            static::updateBalanceAndCollectables($model->client_id, $model->group_id);
        });
    }

    private static function updateBalanceAndCollectables($clientId, $groupId) {
        if($groupId == null){
            $collectable = app(ClientController::class)->getClientTotalCollectables($clientId);
            $balance = app(ClientController::class)->getClientTotalBalance($clientId);

            $collectable = ($collectable < 0 ? $collectable : 0);
            User::where('id', $clientId)->update([
                'balance' => $balance,
                'collectable' => $collectable
            ]);
        }
        else{
            $collectable = app(GroupController::class)->getGroupTotalCollectables($groupId);
            $balance = app(GroupController::class)->getGroupTotalBalance($groupId);
            
            $collectable = ($collectable < 0 ? $collectable : 0);
            Group::where('id', $groupId)->update([
                'balance' => $balance,
                'collectables' => $collectable
            ]);
        }
    }

    public function agentCom() {
    	return $this->belongsTo('App\User', 'agent_com_id', 'id');
    }

    public function client() {
    	return $this->belongsTo('App\User', 'client_id', 'id');
    }

    public function clientCom() {
    	return $this->belongsTo('App\User', 'client_com_id', 'id');
    }

    public function clientReports() {
        return $this->hasMany('App\ClientReport', 'client_service_id', 'id');
    }

    public function clientTransactions() {
        return $this->hasMany('App\ClientTransaction', 'client_service_id', 'id');
    }

    public function group() {
    	return $this->belongsTo('App\Group', 'group_id', 'id');
    }

    public function package() {
        return $this->belongsTo('App\Package', 'tracking', 'tracking');
    }

    public function service() {
    	return $this->belongsTo('App\Service', 'service_id', 'id');
    }

    public function logs() {
        return $this->hasMany('App\Log', 'client_service_id', 'id');
    }

    public function getPoints() {
        return $this->hasOne('App\ClientServicePoints', 'client_service_id', 'id');
    }

    public function updatedCost() {
        return $this->hasMany('App\ClientReport', 'client_service_id', 'id')->leftjoin('service_procedures', 'client_reports.service_procedure_id', '=', 'service_procedures.id');
    }

}

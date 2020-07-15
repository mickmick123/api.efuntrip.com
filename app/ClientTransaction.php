<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\GroupController;

use App\User;
use App\Group;

class ClientTransaction extends Model
{
    
	use SoftDeletes;

    protected $table = 'client_transactions';

    protected $fillable = ['type', 'client_id', 'group_id', 'client_service_id','order_id', 'amount', 'tracking', 'reason', 'storage_type', 'alipay_reference', 'is_commission'];

    public static function boot(){
        parent::boot();

        static::created(function ($model) {
            static::updateBalanceAndCollectables($model->client_id, $model->group_id);
        });

        static::updated(function ($model) {
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

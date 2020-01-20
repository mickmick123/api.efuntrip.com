<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceProcedure extends Model
{
    
    use SoftDeletes;

    protected $table = 'service_procedures';

    protected $fillable = ['service_id', 'name', 'step', 'action_id', 'category_id', 'is_required'];

    public function service() {
    	return $this->belongsTo('App\Service', 'service_id', 'id');
    }

    public function action() {
    	return $this->belongsTo('App\Action', 'action_id', 'id');
    }

    public function category() {
    	return $this->belongsTo('App\Category', 'category_id', 'id');
    }

    public function serviceProcedureDocuments() {
    	return $this->hasMany('App\ServiceProcedureDocument', 'service_procedure_id', 'id');
    }

}
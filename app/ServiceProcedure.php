<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceProcedure extends Model
{
    
    use SoftDeletes;

    protected $table = 'service_procedures';

    protected $fillable = ['service_id', 'name', 'preposition', 'step', 'action_id', 'category_id', 'is_required', 'required_service_procedure', 'status_upon_completion', 'documents_mode'];

    public function action() {
    	return $this->belongsTo('App\Action', 'action_id', 'id');
    }

    public function category() {
    	return $this->belongsTo('App\Category', 'category_id', 'id');
    }

    public function clientReports() {
        return $this->hasMany('App\ClientReport', 'service_procedure_id', 'id');
    }

    public function service() {
        return $this->belongsTo('App\Service', 'service_id', 'id');
    }

    public function suggestedDocuments() {
        return $this->hasMany('App\SuggestedDocument', 'service_procedure_id', 'id');
    }

}

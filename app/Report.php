<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{

    protected $table = 'reports';

    protected $fillable = ['processor_id'];

    public function clientReports() {
    	return $this->hasMany('App\ClientReport', 'report_id', 'id');
    }

    public function processor() {
    	return $this->belongsTo('App\User', 'processor_id', 'id');
    }

}

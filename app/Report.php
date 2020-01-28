<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    
    use SoftDeletes;

    protected $table = 'reports';

    protected $fillable = ['detail', 'processor_id'];

    public function clientReports() {
    	return $this->hasMany('App\ClientReport', 'report_id', 'id');
    }

    public function processor() {
    	return $this->belongsTo('App\User', 'processor_id', 'id');
    }

}

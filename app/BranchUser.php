<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class BranchUser extends Model
{

	protected $table = 'branch_user';
    protected $fillable = ['client_id', 'branch_id'];
    public $timestamps = false;

    public function user() {
      return $this->belongsTo('App\User', 'client_id', 'id');
    }

}

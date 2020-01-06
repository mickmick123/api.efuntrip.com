<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class GroupUser extends Model
{

	  protected $table = 'group_user';
    protected $fillable = ['group_id', 'user_id', 'is_vice_leader', 'total_service_cost'];
    public $timestamps = false;

    public function group() {
      return $this->belongsTo('App\Group', 'group_id', 'id');
    }

}

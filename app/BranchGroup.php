<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class BranchGroup extends Model
{

	  protected $table = 'branch_group';
    protected $fillable = ['group_id', 'branch_id'];
    public $timestamps = false;

    public function group() {
      return $this->belongsTo('App\Group', 'group_id', 'id');
    }

    public function branches() {
      return $this->belongsTo('App\Group', 'branch_id', 'id');
    }

}

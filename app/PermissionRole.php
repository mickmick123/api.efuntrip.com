<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\QueryFilterTrait;
use Illuminate\Database\Eloquent\Builder;
use Auth;

class PermissionRole extends Model
{
    use QueryFilterTrait;
	
    protected $connection = 'mysql';

	protected $table = 'permission_role';
    protected $fillable = [
		 'permission_id'
		,'role_id'
    ];

    public function permissions()
    {
        return $this->belongsTo(Permission::class,'permission_id','id');
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    
    use SoftDeletes;

    protected $table = 'roles';

    protected $fillable = ['name', 'label'];

    public function users() {
        return $this->belongsToMany('App\User', 'role_user', 'role_id', 'user_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class)
            ->withTimestamps();
    }

    public function assignPermission($permission)
    {
        if (is_string($permission)) {
            $this->permissions()->save(
                Permission::whereName($permission)->firstOrFail()
            );
        } elseif (is_array($permission)) {
            $this->permissions()->attach($permission);
        }
    }

}

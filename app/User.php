<?php

namespace App;
use App\ContactNumber;
use App\RoleUser;
use App\BranchUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes, HasApiTokens;

    protected $fillable = ['email', 'password', 'first_name', 'middle_name', 'last_name', 'address', 'birth_date', 'gender', 'height', 'weight', 'civil_status', 'birth_country_id', 'risk', 'wechat_id', 'telegram', 'service_profile_id', 'visa_type', 'arrival_date', 'first_expiration_date', 'extended_expiration_date', 'expiration_date', 'icard_issue_date', 'icard_expiration_date', 'passport', 'passport_exp_date', 'balance', 'collectable', 'verification_token', 'registered_at'];

    protected $hidden = [
        'password', 'verification_token', 'remember_token',
    ];

    public static function boot()
    {
        parent::boot();

        $usrs = User::where('id','>=',15100)->get();
        foreach($usrs as $u){
            $checkRole = RoleUser::where('user_id',$u->id)->where('role_id',2)->first();
            $checkBranch = BranchUser::where('user_id',$u->id)->where('branch_id',1)->first();
            if(!$checkRole){
                $user = User::where('id',$u->id)->first();
                $user->roles()->attach(2);
            }            
            if(!$checkBranch){
                $user = User::where('id',$u->id)->first();
                $user->branches()->attach(1);
            }
            if($u->password == ''){            
                $num = ContactNumber::where('user_id',$u->id)->first()->number;
                $u->password = bcrypt($num);
                $u->save();
            }
        }

    }

    public function birthCountry() {
        return $this->belongsTo('App\Country', 'birth_country_id', 'id');
    }

    public function branches() {
        return $this->belongsToMany('App\Branch', 'branch_user', 'user_id', 'branch_id');
    }

    public function clientDocuments() {
        return $this->hasMany('App\ClientDocument', 'client_id', 'id');
    }

    public function clientServices() {
        return $this->hasMany('App\ClientService', 'client_id', 'id');
    }

    public function clientServiceAgentComs() {
        return $this->hasMany('App\ClientService', 'agent_com_id', 'id');
    }

    public function clientServiceClientComs() {
        return $this->hasMany('App\ClientService', 'client_com_id', 'id');
    }

    public function clientTransactions() {
        return $this->hasMany('App\ClientTransaction', 'client_id', 'id');
    }

    public function contactNumbers() {
        return $this->hasMany('App\ContactNumber', 'user_id', 'id');
    }

    public function department() {
        return $this->hasOne('App\DepartmentUser', 'user_id', 'id');
    }

    public function devices() {
        return $this->hasMany('App\Device', 'user_id', 'id');
    }

    public function groups() {
        return $this->belongsToMany('App\Group', 'group_user', 'user_id', 'group_id')->withPivot('is_vice_leader', 'total_service_cost');
    }

    public function groupAgentComs() {
        return $this->hasMany('App\Group', 'agent_com_id', 'id');
    }

    public function groupClientComs() {
        return $this->hasMany('App\Group', 'client_com_id', 'id');
    }

    public function leaders() {
        return $this->hasMany('App\Group', 'leader_id', 'id');
    }

    public function nationalities() {
        return $this->belongsToMany('App\Nationality', 'nationality_user', 'user_id', 'nationality_id');
    }

    public function onHandDocuments() {
        return $this->hasMany('App\OnHandDocument', 'client_id', 'id');
    }

    public function packages() {
        return $this->hasMany('App\Package', 'client_id', 'id');
    }

    public function reports() {
        return $this->hasMany('App\Report', 'processor_id', 'id');
    }

    public function roles() {
        return $this->belongsToMany('App\Role', 'role_user', 'user_id', 'role_id');
    }

    public function rolesname()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return !! $role->intersect($this->roles)->count();
    }

}

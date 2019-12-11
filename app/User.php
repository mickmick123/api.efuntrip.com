<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes, HasApiTokens;

    protected $fillable = ['email', 'password', 'first_name', 'middle_name', 'last_name', 'address', 'birth_date', 'gender', 'height', 'weight', 'civil_status', 'birth_country_id', 'risk', 'visa_type', 'arrival_date', 'first_expiration_date', 'extended_expiration_date', 'expiration_date', 'icard_issue_date', 'icard_expiration_date', 'passport', 'passport_exp_date', 'balance', 'collectable', 'verification_token', 'registered_at'];

    protected $hidden = [
        'password', 'verification_token', 'remember_token',
    ];

    public function agentComs() {
        return $this->hasMany('App\User', 'agent_com_id', 'id');
    }

    public function birthCountry() {
        return $this->belongsTo('App\Country', 'birth_country_id', 'id');
    }

    public function branches() {
        return $this->belongsToMany('App\Branch', 'branch_user', 'user_id', 'branch_id');
    }

    public function clientComs() {
        return $this->hasMany('App\User', 'client_com_id', 'id');
    }

    public function contactNumbers() {
        return $this->hasMany('App\ContactNumber', 'user_id', 'id');
    }

    public function devices() {
        return $this->hasMany('App\Device', 'user_id', 'id');
    }

    public function groups() {
        return $this->belongsToMany('App\Group', 'group_user', 'user_id', 'group_id')->withPivot('total_service_cost');
    }

    public function leaders() {
        return $this->hasMany('App\User', 'leader_id', 'id');
    }

    public function nationalities() {
        return $this->belongsToMany('App\Nationality', 'nationality_user', 'user_id', 'nationality_id');
    }

    public function roles() {
        return $this->belongsToMany('App\Role', 'role_user', 'user_id', 'role_id');
    }

    public function viceLeaders() {
        return $this->hasMany('App\User', 'vice_leader_id', 'id');
    }

}

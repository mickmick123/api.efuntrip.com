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

    protected $fillable = ['email', 'password', 'first_name', 'middle_name', 'last_name', 'address', 'birth_date', 'gender', 'height', 'weight', 'civil_status', 'birth_country_id', 'risk', 'visa_type', 'icard', 'exp_date', 'icard_exp_date', 'issue_date', 'first_exp_date', 'arrival_date', 'passport', 'passport_exp_date', 'balance', 'collectable', 'verification_token', 'registered_at'];

    protected $hidden = [
        'password', 'verification_token', 'remember_token',
    ];

    public function birthCountry() {
        return $this->belongsTo('App\Country', 'birth_country_id', 'id');
    }

    public function branches() {
        return $this->belongsToMany('App\Branch', 'branch_user', 'user_id', 'branch_id');
    }

    public function contactNumbers() {
        return $this->hasMany('App\ContactNumber', 'user_id', 'id');
    }

    public function devices() {
        return $this->hasMany('App\Device', 'user_id', 'id');
    }

    public function nationalities() {
        return $this->belongsToMany('App\Nationality', 'nationality_user', 'user_id', 'nationality_id');
    }

}

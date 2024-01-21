<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactTypeList extends Model
{
    
    protected $table = 'contact_type_list';

    protected $fillable = ['name'];

}

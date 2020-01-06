<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    
    use SoftDeletes;

    protected $table = 'documents';

    protected $fillable = ['title', 'title_cn', 'is_unique'];

}
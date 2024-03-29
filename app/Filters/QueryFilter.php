<?php

namespace App\Filters;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

abstract class QueryFilter
{
    protected $request;
    protected $builder;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function filters()
    {
        return $this->request->all();
    }

    public function apply(Builder $builder)
    {
        $this->builder = $builder;

        if (method_exists($this, 'default')) {
            call_user_func([$this, 'default']);
        }

        foreach ($this->filters() as $name => $value) {
            if (method_exists($this, $name)) {
                call_user_func_array([$this, $name], array_filter([$value]));
            }
        }

        return $this->builder;
    }

}
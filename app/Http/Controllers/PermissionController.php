<?php

namespace App\Http\Controllers;

use App\Permission;

use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(){
		$types = Permission::groupBy('type')->orderBy('id')->get();
		$permi = [];
		$ctr = 0;
		foreach($types as $t){
			$t['access'] = Permission::where('type',$t->type)->get();
			$t['parent'] = $t->type;
		}

        return $types;
	}
}

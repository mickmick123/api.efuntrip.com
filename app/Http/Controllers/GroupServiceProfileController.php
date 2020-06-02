<?php

namespace App\Http\Controllers;

use Response, Validator;

use Illuminate\Support\Str;
use Illuminate\Http\Request;


use App\Service;
use App\Group;
use App\ServiceProfileCost;
use DB;

class GroupServiceProfileController extends Controller
{


  public function allServices() {
    $parents = Service::where('parent_id', 0)->where('is_active', 1)->orderBy('detail')
      ->select(array('id', 'parent_id', 'detail','is_active', DB::raw('SUM(cost + charge + tip + com_agent + com_client) as total_service_charge')))
      ->groupBy('id')
      ->get();

    $services = [];
    foreach($parents as $parent) {
            $services[] = $parent;

            $children = Service::where('parent_id', $parent->id)->where('is_active', 1)->orderBy('detail')
        ->select(array('id', 'parent_id', 'detail','is_active', DB::raw('SUM(cost + charge + tip + com_agent + com_client) as total_service_charge')))
        ->groupBy('id')
        ->get();

      foreach($children as $child) {
        $services[] = $child;
      }
    }

    $response['status'] = 'Success';
    $response['data'] = [
        'services' => $services
    ];
    $response['code'] = 200;

    return Response::json($response);
  }


  public function services($group_id, $branch_id){

      $group = Group::where('id',$group_id)->first();

      if($group->service_profile_id > 0 && $group->service_profile_id != null){
          $profileServiceIds = ServiceProfileCost::where('profile_id',$group->service_profile_id)->where('branch_id',$branch_id)
                              ->where(function($query) {
                                  return $query->orwhere('cost', '>', 0)
                                      ->orWhere('charge', '>', 0)
                                      ->orWhere('tip', '>', 0)
                                      ->orWhere('com_agent', '>', 0)
                                      ->orWhere('com_client', '>', 0);
                              })
                              ->pluck('service_id');
          $profileServices = Service::whereIn('id',$profileServiceIds)
                                ->orwhere(function($query) {
                                    return $query->where('cost', 0)
                                        ->where('charge', 0)
                                        ->where('tip', 0)
                                        ->where('com_agent', 0)
                                        ->where('com_client', 0);
                                })
                                ->get();

          $response['status'] = 'Success';
          $response['data'] = [
               'services' => $profileServices
          ];
          $response['code'] = 200;

          return Response::json($response);

      }
      else{
           return $this->allServices();
      }
  }



}

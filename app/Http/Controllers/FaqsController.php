<?php

namespace App\Http\Controllers;

use App\Action;

use Auth, DB, Response, Validator;

use Illuminate\Http\Request;

class FaqsController extends Controller
{
    
  public function index() {
    $faqs = DB::table('faqs')->get();

    $response['status'] = 'Success';
    $response['data'] = $faqs;
    $response['code'] = 200;

    return Response::json($response);
  }

}

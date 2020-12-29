<?php

namespace App\Http\Controllers;

use App\RiderEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class RiderEvaluationController extends Controller
{
    protected $riderEvaluation;

    public function __construct(RiderEvaluation $riderEvaluation)
    {
        $this->riderEvaluation = $riderEvaluation;
    }

    public function addEvaluation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'rider_id' => 'required',
            'answer' => 'required',
            'result' => 'required',
            'delivery_fee' => 'required',
            'date' => 'required',
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $data = new RiderEvaluation;
            $data->order_id = $request->order_id;
            $data->rider_id = $request->rider_id;
            foreach (json_decode($request->answer) as $k => $v) {
                $data['q' . ($k + 1)] = $v;
            }
            $data->result = $request->result;
            $data->delivery_fee = $request->delivery_fee;
            $data->date = $request->date;
            $this->riderEvaluation->saveToDb($data->toArray());

            $response['status'] = 'Success';
            $response['data'] = $data;
            $response['code'] = 200;
        }
        return Response::json($response);
    }

    public function getEvaluationDay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rider_id' => 'required|exists:rider_evaluation',
            'date' => 'required'
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $data = RiderEvaluation::where([['rider_id', $request->rider_id], ['date', $request->date]])->get();

            $response['status'] = 'Success';
            $response['data'] = $data;
            $response['code'] = 200;
        }
        return Response::json($response);
    }
    public function getEvaluationMonth()
    {
        $data = RiderEvaluation::all();

        $response['status'] = 'Success';
        $response['data'] = $data;
        $response['code'] = 200;

        return Response::json($response);
    }
}

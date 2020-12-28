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
            $data->answer = $request->answer;
            $data->result = $request->result;
            $data->date = $request->date;
            // $this->logsAppNotification->saveToDb($data);

            $response['status'] = 'Success';
            $response['data'] = $data;
            $response['code'] = 200;
        }
        return Response::json($response);
    }

    public function getEvaluationDay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:rider_evaluation',
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $data = RiderEvaluation::find($request->id);
            $data->save();

            $response['status'] = 'Success';
            $response['data'] = $data;
            $response['code'] = 200;
        }
        return Response::json($response);
    }
    public function getEvaluationMonth(Request $request)
    {
    }
}

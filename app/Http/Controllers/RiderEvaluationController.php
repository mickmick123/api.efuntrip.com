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
            $data->rider_id = $request->rider_id;
            foreach (json_decode($request->answer) as $k => $v) {
                $data['q' . ($k + 1)] = $v;
            }
            $data->result = $request->result;
            $data->delivery_fee = $request->delivery_fee;
            $data->date = $request->date;
            $this->riderEvaluation->saveToDb($data->toArray());

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = "Evaluation succesfully created!";
        }
        return Response::json($response);
    }

    public function updateEvaluation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:rider_evaluation',
            'answer' => 'required',
            'result' => 'required',
            'delivery_fee' => 'required',
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            foreach (json_decode($request->answer) as $k => $v) {
                $data['q' . ($k + 1)] = $v;
            }
            $data['result'] = $request->result;
            $data['delivery_fee'] = $request->delivery_fee;
            $this->riderEvaluation->updateById(['id' => $request->id], $data);

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = "Evaluation succesfully updated!";
        }
        return Response::json($response);
    }

    public function deleteEvaluation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:rider_evaluation',
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $this->riderEvaluation->deleteById(['id' => $request->id]);

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = "Evaluation succesfully deleted!";
        }
        return Response::json($response);
    }

    public function getEvaluation(Request $request)
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

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $data;
        }
        return Response::json($response);
    }

    public function getEvaluationDay(Request $request, $perPage = 10)
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
            $sort = $request->sort;
            $data = RiderEvaluation::where([
                ['rider_id', $request->rider_id],
                ['date', $request->date]
            ])
                ->when($sort != '', function ($q) use ($sort) {
                    $sort = explode('-', $sort);
                    return $q->orderBy($sort[0], $sort[1]);
                })->paginate($perPage);

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $data;
        }
        return Response::json($response);
    }

    public function getEvaluationMonth()
    {
        $data = RiderEvaluation::all();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $data;

        return Response::json($response);
    }
}

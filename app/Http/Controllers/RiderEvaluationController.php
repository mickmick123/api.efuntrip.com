<?php

namespace App\Http\Controllers;

use App\Helpers\ArrayHelper;
use App\RiderEvaluation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
            // 'rider_id' => 'required|exists:rider_evaluation',
            'date' => 'required'
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $sort = $request->sort;
            $data = RiderEvaluation::select(DB::raw('*,ROW_NUMBER() OVER(ORDER BY ID DESC) AS row'))
                ->where([
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

    public function getSummaryEvaluationHalfMonth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rider_id' => 'required|exists:rider_evaluation',
            'month' => 'required',
            'year' => 'required',
            'half_month' => 'required'
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $data = [];
            $tempData = [];
            $index = 1;
            for ($i = 1; $i <= 15; $i++) {
                $year = $request->year;
                $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);
                $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                $tempDate = $year . '-' . $month . '-' . $day;
                $total = RiderEvaluation::where([
                    ['rider_id', $request->rider_id],
                    ['date', 'like', '%' . $tempDate . '%']
                ])->get();
                $orders = 0;
                $delivery_fee = 0;
                $percentage = 0;
                for ($ii = 1; $ii <= 3; $ii++) {
                    foreach ($total as $v) {
                        if ($ii === 1) {
                            $orders += 1;
                            $tempData[$index] = [
                                "date" => Carbon::parse($tempDate)->format('F d, Y'),
                                "detail" => 'Total # of Orders:',
                                "value" => $orders
                            ];
                        } else if ($ii === 2) {
                            $delivery_fee += $v->delivery_fee;
                            $tempData[$index] = [
                                "date" => Carbon::parse($tempDate)->format('F d, Y'),
                                "detail" => 'Total Delivery Fee:',
                                "value" => $delivery_fee
                            ];
                        } else if ($ii === 3) {
                            $percentage = 100;
                            $tempData[$index] = [
                                "date" => Carbon::parse($tempDate)->format('F d, Y'),
                                "detail" => 'Total Percentage:',
                                "value" => $percentage
                            ];
                        }
                    }
                    $index++;
                }
                $data = ArrayHelper::ArrayIndexFixed($tempData);
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $data;
        }
        return Response::json($response);
    }
}

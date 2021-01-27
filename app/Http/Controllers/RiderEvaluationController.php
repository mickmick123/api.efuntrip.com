<?php

namespace App\Http\Controllers;

use App\Helpers\ArrayHelper;
use App\RiderEvaluationQA;
use App\RiderEvaluation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RiderEvaluationController extends Controller
{
    protected $riderEvaluationQA;
    protected $riderEvaluation;

    public function __construct(RiderEvaluationQA $riderEvaluationQA, RiderEvaluation $riderEvaluation)
    {
        $this->riderEvaluationQA = $riderEvaluationQA;
        $this->riderEvaluation = $riderEvaluation;
    }

    public function updateQA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'question' => 'required',
            'choices' => 'required',
        ]);
        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $tempData = [];
            foreach (json_decode($request->id, true) as $k => $v) {
                $tempData[$k]['id'] = $v;
            }
            foreach (json_decode($request->question, true) as $k => $v) {
                $tempData[$k]['question'] = $v;
            }
            foreach (json_decode($request->choices, true) as $k => $v) {
                foreach ($v["id"] as $kk => $vv) {
                    $tempData[$k]['choices'][$kk]["id"] = $kk;
                }
                foreach ($v["answer"] as $kk => $vv) {
                    $tempData[$k]['choices'][$kk]["answer"] = $vv;
                }
                foreach ($v["score"] as $kk => $vv) {
                    $tempData[$k]['choices'][$kk]["score"] = $vv;
                }
                $tempData[$k]['choices'] = json_encode($tempData[$k]['choices']);
            }
            $tempId = [];
            foreach ($tempData as $k => $v) {
                $tempId[$k] = $v["id"];
                $checkId = RiderEvaluationQA::where('id', $v["id"])->pluck('id');
                if ($checkId->count() == 0) {
                    $this->riderEvaluationQA->saveMultipleToDb($v);
                } else {
                    $this->riderEvaluationQA->updateById(['id' => $v["id"]], $v);
                }
            }
            $deleteId = RiderEvaluationQA::pluck('id');
            foreach ($deleteId as $k => $v) {
                if (!in_array($v, $tempId)) {
                    $this->riderEvaluationQA->deleteById(['id' => $v]);
                }
            }

            $update = [];
            $getEvaluation = RiderEvaluation::all();
            $getQA = RiderEvaluationQA::all();
            foreach ($getEvaluation as $k => $v) {
                $scores = 0;
                foreach (json_decode($v['answers']) as $kk => $vv) {
                    if ($vv !== null) {
                        $scores += (float)json_decode($getQA[$kk]['choices'])[$vv]->score;
                    }
                }
                $update['result'] = $scores + 1;
                $update['delivery_fee'] = $v['delivery_fee'];
                $update['rider_income'] = self::riderIncome($update['result'], $v['delivery_fee']);
                $update['evaluation'] = 100 + $update['result'];
                $this->riderEvaluation->updateById(['id' => $v['id']], $update);
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = "Question & Answer succesfully updated!";
        }
        return Response::json($response);
    }

    public function addEvaluation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rider_id' => 'required',
            'answer' => 'required',
            'scores' => 'required',
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
            $answer = [];
            foreach (json_decode($request->answer) as $k => $v) {
                $answer[$k] = $v;
            }
            $data->answers = json_encode($answer);
            $data->result = array_sum(json_decode($request->scores)) + 1;
            $data->delivery_fee = $request->delivery_fee;
            $data->rider_income = self::riderIncome($data->result, $request->delivery_fee);
            $data->evaluation = 100 + $data->result;
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
            'scores' => 'required',
            'delivery_fee' => 'required',
        ]);

        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $answer = [];
            foreach (json_decode($request->answer) as $k => $v) {
                $answer[$k] = $v;
            }
            $data['answers'] = json_encode($answer);
            $data['result'] = array_sum(json_decode($request->scores)) + 1;
            $data['delivery_fee'] = $request->delivery_fee;
            $data['rider_income'] = self::riderIncome($data['result'], $request->delivery_fee);
            $data['evaluation'] = 100 + $data['result'];
            $this->riderEvaluation->updateById(['id' => $request->id], $data);

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = "Evaluation succesfully updated!";
            $response['data1'] = $data;
            $response['request'] = $request->all();
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

    public function getQA()
    {
        $data = RiderEvaluationQA::all();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $data;
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

    public function getEvaluationMonth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required',
        ]);
        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $data = [];
            $stringJson = '"id":1,"rider_id":1,"name":"Tom",';
            $tempData = [];
            $index = 1;
            for ($i = 1; $i <= 12; $i++) {
                for ($ii = 1; $ii <= 2; $ii++) {
                    $request = [
                        'rider_id' => 1,
                        'month' => str_pad($i, 2, '0', STR_PAD_LEFT),
                        'year' => $request['year'],
                        'half_month' => $ii
                    ];
                    $tempData[$index] = self::getSummary($request)['summary'];
                    foreach ($tempData[$index] as $k => $v) {
                        if ($k === 'result') {
                            $stringJson .= '"result' . $i . $ii . '":' . '"' . $v . '%",';
                        }
                    }
                    $index++;
                }
            }
            $data = [json_decode('{' . substr($stringJson, 0, -1) . '}', true)];
            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $data;
        }

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
            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['summary'] = self::getSummary($request)['summary'];
            $response['data'] = self::getSummary($request)['data'];
        }
        return Response::json($response);
    }

    protected static function getSummary($request)
    {
        $data = [];
        $tempData = [];
        $summary = ['evaluation' => 0, 'average' => 0, 'days' => 0, 'result' => 0];
        $tempSummary = ['evaluation' => [], 'average' => [], 'days' => []];
        $year = $request['year'];
        $month = str_pad($request['month'], 2, '0', STR_PAD_LEFT);
        $endMonthDay = Carbon::parse($year . '-' . $month . '-01')->endOfMonth()->format('d');
        $index = 1;
        if ($request['half_month'] == 1) {
            $start = 1;
            $end = 15;
        } else {
            $start = 16;
            $end = $endMonthDay;
        }
        for ($i = $start; $i <= $end; $i++) {
            $day = str_pad($i, 2, '0', STR_PAD_LEFT);
            $tempDate = $year . '-' . $month . '-' . $day;
            $total = RiderEvaluation::where([
                ['rider_id', $request['rider_id']],
                ['date', 'like', '%' . $tempDate . '%']
            ])->get();
            $orders = 0;
            $delivery_fee = 0;
            $evaluation = ['orders' => 0, 'delivery_fee' => 0, 'extra' => 0, 'result' => 0];
            $average = 0;
            for ($ii = 1; $ii <= 4; $ii++) {
                foreach ($total as $v) {
                    if ($ii === 1) {
                        $tempSummary['days'][$i] = 1;
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
                        if ($evaluation['orders'] >= 10 || $evaluation['delivery_fee'] >= 800) {
                            $evaluation['extra'] += 1 * 3;
                            $evaluation['result'] = 100 + $evaluation['extra'];
                            if ($evaluation['result'] > 105) {
                                $evaluation['result'] = 105;
                            }
                        } else if ($orders < 10 && $delivery_fee < 800) {
                            $evaluation['extra'] = (10 - $orders) * -9.5;
                            $evaluation['result'] = 100 + $evaluation['extra'];
                            if ($evaluation['result'] < 80) {
                                $evaluation['result'] = 80;
                            }
                        } else {
                            $evaluation['result'] = 100;
                        }
                        $evaluation['orders'] += 1;
                        $evaluation['delivery_fee'] += $v->delivery_fee;

                        $tempData[$index] = [
                            "date" => Carbon::parse($tempDate)->format('F d, Y'),
                            "detail" => 'Daily Evaluation:',
                            "value" => $evaluation['result'] . '%'
                        ];
                        $tempSummary['evaluation'][$i] = $evaluation['result'];
                    } else if ($ii === 4) {
                        $average += $v->evaluation;
                        $tempData[$index] = [
                            "date" => Carbon::parse($tempDate)->format('F d, Y'),
                            "detail" => 'Average/Order Evaluation:',
                            "value" => $average / $orders . '%'
                        ];
                        $tempSummary['average'][$i] = $average / $orders;
                    }
                }
                $index++;
            }
            $data = ArrayHelper::ArrayIndexFixed($tempData);
        }
        $summary['evaluation'] = array_sum(ArrayHelper::ArrayIndexFixed($tempSummary['evaluation']));
        $summary['average'] = array_sum(ArrayHelper::ArrayIndexFixed($tempSummary['average']));
        $summary['days'] = array_sum(ArrayHelper::ArrayIndexFixed($tempSummary['days']));
        $summary['result'] = round((($summary['evaluation'] + $summary['average']) / ($summary['days'] === 0 ? 1 : $summary['days'])) / 2, 2);

        return ['summary' => $summary, 'data' => $data];
    }

    protected static function riderIncome($data, $fee)
    {
        if ($data > 0) {
            $data = $fee;
        } else if ($data >= -5) {
            $data = $fee / 2;
        } else if ($data >= -10) {
            $data = $fee - $fee;
        } else if ($data < -10) {
            $data = -$fee / 2;
        }
        return $data;
    }
}

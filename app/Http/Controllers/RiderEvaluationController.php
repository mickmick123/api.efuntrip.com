<?php

namespace App\Http\Controllers;

use App\Helpers\ArrayHelper;
use App\RiderEvaluationQA;
use App\RiderEvaluation;
use App\RiderName;
use App\OrderDelayed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class RiderEvaluationController extends Controller
{
    
    /*
        http://localhost:8082/#/rider-evaluation
        Once you click the Question & Answer Editor button
        You can now fill it up and after that you can now save it.
    */
    public function updateQA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'question' => 'required',
            'choices' => 'required',
            'answer_history' => 'required',
            'choice_history' => 'required',
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
                    $data = new RiderEvaluationQA;
                } else {
                    $data = RiderEvaluationQA::find($v['id']);
                }
                $data->id = $v['id'];
                $data->question = $v['question'];
                $data->choices = $v['choices'];
                $data->save();
            }
            $deleteId = RiderEvaluationQA::get();
            $id = 1;
            foreach ($deleteId as $k => $v) {
                if (!in_array($v['id'], $tempId)) {
                    RiderEvaluationQA::find($v['id'])->delete();
                    $id--;
                }
                $data = RiderEvaluationQA::where('id', $v['id'])->first();
                if ($data !== null) {
                    $data->id = $id;
                    $data->question = $v['question'];
                    $data->choices = $v['choices'];
                    $data->save();
                }
                $id++;
            }

            self::updateAllEvaluation($request->choice_history, $request->answer_history);

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = "Question & Answer succesfully updated!";
        }
        return Response::json($response);
    }

    /*
        http://localhost:8082/#/rider-evaluation/{rider_id}/{month}/{halfMonth}/{year}
        halfMonth = day<=15=1; day>=16=2;
        Once you click the Rider Evaluation Form button it will show you the form.
        Click the radio button and start the survey then save.
    */
    public function addEvaluation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'order_id' => 'required',
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
            // $data->order_id = $request->order_id;
            $answer = [];
            foreach (json_decode($request->answer) as $k => $v) {
                $answer[$k] = $v;
            }
            $data->answers = json_encode($answer);
            $data->result = array_sum(json_decode($request->scores));
            $data->delivery_fee = $request->delivery_fee;
            $data->rider_income = self::riderIncome($data->result, $request->delivery_fee);
            $data->evaluation = 80 + ($data->result * 5);
            $data->date = $request->date;
            $data->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = "Evaluation succesfully created!";
        }
        return Response::json($response);
    }

    /*
        http://localhost:8082/#/rider-evaluation/{rider_id}/{month}/{halfMonth}/{year}
        halfMonth = day<=15=1; day>=16=2;
        Once you click the Edit button it will show you the form.
        Click the radio button and start the survey then save.
    */
    public function updateEvaluation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:rider_evaluation',
            'answer' => 'required',
            'scores' => 'required',
            'delivery_fee' => 'required',
            // 'order_id' => 'required',
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
            $data = RiderEvaluation::find($request->id);
            $data->answers = json_encode($answer);
            $data->result = array_sum(json_decode($request->scores));
            // $data['order_id'] = $request->order_id;
            $data->delivery_fee = $data->delivery_fee;
            $data->rider_income = self::riderIncome($data['result'], $data->delivery_fee);
            $data->evaluation = 80 + ($data['result'] * 5);
            $data->save();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = "Evaluation succesfully updated!";
        }
        return Response::json($response);
    }

    /*
        http://localhost:8082/#/rider-evaluation/{rider_id}/{month}/{halfMonth}/{year}
        halfMonth = day<=15=1; day>=16=2;
        Once you click the Delete button it will deleted that row in instant.
    */
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
            RiderEvaluation::find($request->id)->delete();

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = "Evaluation succesfully deleted!";
        }
        return Response::json($response);
    }

    /*
        http://localhost:8082/#/rider-evaluation
        Once you click the Question & Answer Editor button
        It will fill up input fields when its have existed data.
    */
    public function getQA()
    {
        $data = RiderEvaluationQA::all();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $data;
        return Response::json($response);
    }

    /*
        http://localhost:8082/#/rider-evaluation/{rider_id}/{month}/{halfMonth}/{year}
        halfMonth = day<=15=1; day>=16=2;
        Once you click the Edit button it will show you the form.
        Tt will fill up automatic the original data of the survey.
    */
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

    public function getDailyEvaluationDetails(Request $request)
    {
        $order_ids = RiderEvaluation::where([
                            ['rider_evaluation.date', 'like', '%' . $request->date . '%']
                        ])->pluck('order_id');
        $rider = RiderName::findorfail($request['rider_id']);
        $delayed = OrderDelayed::whereIn('order_id', $order_ids)->where('riders', 'LIKE' ,'%' . $rider->name . '%')->get();

        $orders = RiderEvaluation::select(['rider_evaluation.*'])
                ->leftJoin('rider_name as rn', 'rider_evaluation.rider_id', 'rn.id')
                ->where([
                    ['rider_evaluation.rider_id', $request->rider_id],
                    ['rider_evaluation.date', $request->date]
                ])
                ->orderBy('rider_evaluation.id')
                ->get();

        $average = 0;
        $sum_eval = $orders->sum('evaluation');

        $total_order = $orders->count();
        $total_delayed = $delayed->count();

        $average = $sum_eval / $total_order;

        $average = (explode('.', number_format($average, 2))[1] == '00' ? $average : number_format($average, 2));
        
        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $orders;
        $response['total_order'] = $total_order;
        $response['average'] = $average;
        $response['delayed'] = $delayed;
        $response['total_delayed'] = $total_delayed;

        return Response::json($response);
    }

    /*
        http://localhost:8082/#/rider-evaluation/{rider_id}/{month}/{halfMonth}/{year}
        halfMonth = day<=15=1; day>=16=2;
        Once you Pick Available Days
        It will fill up the datatable.
    */
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
            self::updateAllEvaluation('[]', '[]');
            $data = RiderEvaluation::select(['rider_evaluation.*'])
                ->leftJoin('rider_name as rn', 'rider_evaluation.rider_id', 'rn.id')
                //->leftJoin('order_delayed as od', 'rider_evaluation.order_id', 'od.order_id')
                ->where([
                    ['rider_evaluation.rider_id', $request->rider_id],
                    ['rider_evaluation.date', $request->date]
                ])
                // ->when($sort != '', function ($q) use ($sort) {
                //     $sort = explode('-', $sort);
                //     return $q->orderBy($sort[0], $sort[1]);
                // })
                ->orderBy('rider_evaluation.id')
                ->paginate($perPage);

            $rider = RiderName::findorfail($request->rider_id);

            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $data;
            $response['rider_name'] = $rider->name;
            $response['shift'] = $rider->shift;
        }
        return Response::json($response);
    }

    /*
        http://localhost:8082/#/rider-evaluation
        It will fill up the datatable
    */
    public function getEvaluationMonth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required',
            'quarter' => 'required',
            'search' => 'nullable',
        ]);
        if ($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $data = [];
            $riders = RiderName::where('name', 'like', '%' . $request->search . '%')->get();
            foreach ($riders as $k => $v) {
                $stringJson = '"id":' . $v->id . ',"name":"' . $v->name . '",';
                $tempData = [];
                $index = 1;
                for ($i = 1; $i <= 12; $i++) {
                    $quarterx = ceil($i / 3);
                    if ($request->quarter == $quarterx) {
                        for ($ii = 1; $ii <= 2; $ii++) {
                            // $data[$k] = [
                            //     'rider_id' => $v->id,
                            //     'month' => str_pad($i, 2, '0', STR_PAD_LEFT),
                            //     'year' => $request['year'],
                            //     'half_month' => $ii
                            // ];
                            // $tempData[$index] = self::getSummary($data[$k])['summary'];
                            // foreach ($tempData[$index] as $kk => $vv) {
                            //     if ($kk === 'result') {
                            //         $stringJson .= '"result' . $i . $ii . '":' . '"' . $vv . '%",';
                            //     }
                            // }
                            $stringJson .= '"result' . $i . $ii . '":' . '"el-icon-right",';
                            $index++;
                        }
                    }
                }
                $data[$k] = json_decode('{' . substr($stringJson, 0, -1) . '}', true);
            }
            $response['status'] = 'Success';
            $response['code'] = 200;
            $response['data'] = $data;
        }

        return Response::json($response);
    }

    /*
        http://localhost:8082/#/rider-evaluation/{rider_id}/{month}/{halfMonth}/{year}
        halfMonth = day<=15=1; day>=16=2;
        Once you click the Summary Evaluation Button
        You will see the everyday score and in the half month score.
    */
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
            $response['rider_name'] = RiderName::findorfail($request->rider_id)->name;;
        }
        return Response::json($response);
    }

    /*
        http://localhost:8082/#/rider-summary-half-month/{rider_id}/{month}/{halfMonth}/{year}
        halfMonth = day<=15=1; day>=16=2;
        This is the calculation of the Summary
    */
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
                ['rider_evaluation.rider_id', $request['rider_id']],
                ['rider_evaluation.date', 'like', '%' . $tempDate . '%']
            ])->get();
            $average = 0;
            $average = $total->sum('evaluation');

            $delivery_fee = 0;
            $delivery_fee = $total->sum('delivery_fee');

            $orders = 0;
            $orders = $total->count();

            $order_ids = RiderEvaluation::where([
                            ['rider_evaluation.date', 'like', '%' . $tempDate . '%']
                        ])->pluck('order_id');
            $rider = RiderName::findorfail($request['rider_id']);
            
            $delayed = 0;
            $delayed = OrderDelayed::whereIn('order_id', $order_ids)->where('riders', 'LIKE' ,'%' . $rider->name . '%')->count();

            $evaluation = ['orders' => 0, 'delivery_fee' => 0, 'extra' => 0, 'result' => 0];
            $aveOrder = 0;
            if($orders > 0){
                $aveOrder = $average / $orders;
            }
            $delayed = $delayed > 0 ? (-5 * $delayed) : 0;
            $aveOrder = ($aveOrder + $delayed < 0 ? 0 : $aveOrder + $delayed);
            for ($ii = 1; $ii <= 3; $ii++) {
                foreach ($total as $v) {
                    if ($ii === 1) {
                        $tempSummary['days'][$i] = 1;
                        //$orders += 1;
                        $tempData[$index] = [
                            "date" => Carbon::parse($tempDate)->format('F d, Y'),
                            "detail" => 'Total # of Orders:',
                            "value" => $orders
                        ];
                    } else if ($ii === 2) {
                        //$delivery_fee += $v->delivery_fee;
                        $tempData[$index] = [
                            "date" => Carbon::parse($tempDate)->format('F d, Y'),
                            "detail" => 'Total Delivery Fee:',
                            "value" => $delivery_fee
                        ];
                    } 
                    // else if ($ii === 3) {
                    //     if ($evaluation['orders'] >= 10 || $evaluation['delivery_fee'] >= 800) {
                    //         $evaluation['extra'] += 1 * 3;
                    //         $evaluation['result'] = 100 + $evaluation['extra'];
                    //         if ($evaluation['result'] > 105) {
                    //             $evaluation['result'] = 105;
                    //         }
                    //     } else if ($orders < 10 && $delivery_fee < 800) {
                    //         $evaluation['extra'] = (10 - $orders) * -9.5;
                    //         $evaluation['result'] = 100 + $evaluation['extra'];
                    //         if ($evaluation['result'] < 80) {
                    //             $evaluation['result'] = 80;
                    //         }
                    //     } else {
                    //         $evaluation['result'] = 100;
                    //     }
                    //     $delayed = $delayed > 0 ? 5 : 0;
                    //     $evaluation['result'] = $evaluation['result'] - $delayed;
                    //     $evaluation['orders'] += 1;
                    //     $evaluation['delivery_fee'] += $v->delivery_fee;

                    //     $tempData[$index] = [
                    //         "date" => Carbon::parse($tempDate)->format('F d, Y'),
                    //         "detail" => 'Daily Evaluation:',
                    //         "value" => $evaluation['result'] . '%'
                    //     ];
                    //     $tempSummary['evaluation'][$i] = $evaluation['result'] ;
                    // } 
                    else if ($ii === 3) {

                        $tempData[$index] = [
                            "date" => Carbon::parse($tempDate)->format('F d, Y'),
                            "detail" => 'Daily Evaluation (Average):',
                            "value" => (explode('.', number_format($aveOrder, 2))[1] == '00' ? $aveOrder : number_format($aveOrder, 2)). '%'
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

    /*
        http://localhost:8082/#/rider-evaluation
        When updating the Question it will global update

        http://localhost:8082/#/rider-evaluation/{rider_id}/{month}/{halfMonth}/{year}
        halfMonth = day<=15=1; day>=16=2;
        when updating the Evaluation it will trigger on change
    */
    protected static function updateAllEvaluation($choice_history, $answer_history)
    {
        $getEvaluation = RiderEvaluation::select(['rider_evaluation.*'])->leftJoin('rider_name as rn', 'rider_evaluation.rider_id', 'rn.id')
            // ->leftJoin('order_delayed as od', 'rider_evaluation.order_id', 'od.order_id')
            ->orderBy('rider_evaluation.id')
            ->get();

        foreach ($getEvaluation as $k => $v) {
            $scores = 0;
            $answerHistory = json_decode($v['answers']);
            foreach ($answerHistory as $kk => $vv) {
                if (in_array($kk, json_decode($choice_history))) {
                    $answerHistory[$kk] = null;
                }
            }
            foreach (json_decode($answer_history) as $kk => $vv) {
                if ($vv == 'add') {
                    array_push($answerHistory, null);
                } else {
                    $answerHistory = ArrayHelper::ArrayRemoveKey($answerHistory, $vv + 1);
                }
            }
            foreach ($answerHistory as $kk => $vv) {
                if ($vv !== null) {
                    $scores += (float)json_decode(RiderEvaluationQA::where('id', $kk + 1)->get()[0]['choices'])[$vv]->score;
                }
            }
            $delayed = $v['order_delayed'] !== null ? 5 : 0;
            $update = RiderEvaluation::find($v['id']);
            $update->answers = $answerHistory;
            $update->result = $scores;
            // $update->delivery_fee = $v['delivery_fee'];
            $update->delivery_fee = $update->delivery_fee;
            $update->rider_income = self::riderIncome($update['result'], $update->delivery_fee);
            // $update->evaluation = (80 + ($update['result'] * 5)) - $delayed;
            $update->evaluation = (80 + ($update['result'] * 5)) ;
            $update->save();
        }
    }

    /*
        formula of riderIncome
    */
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

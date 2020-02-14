<?php

namespace App\Http\Controllers;

use App\Attendance;

use App\CalendarEvents;

use App\Department;

use App\DepartmentUser;

use App\User;

use DB, Response, Validator;

use Carbon\Carbon;
use Illuminate\Http\Request;

use Storage;

class AttendanceController extends Controller
{

    public function index(Request $request) {
        $month = $request->input('month');
        $year = $request->input('year');
        
        $attendance = DepartmentUser::with(array('attendance' => function($query) use ($month, $year){ 
                            $query->select('*')->where('month', '=', $month)->where('year', '=', $year); 
                        }))
                        ->leftjoin('users', 'department_user.user_id', '=', 'users.id')
                        ->leftjoin('departments', 'department_user.department_id', '=', 'departments.id')
                        ->select('department_user.*', 'users.first_name', 'users.last_name', DB::raw('CONCAT(users.last_name," ",users.first_name) AS full_name'), 'departments.name as department')
                        ->orderBy('departments.id', 'asc')
                        ->orderBy('users.last_name', 'asc')
                        ->get();

        $response['status'] = 'Success';
		$response['code'] = 200;
        $response['data'] = $attendance;

		return Response::json($response);
    }
    
    public function testKernel () {
        $d = Carbon::now('Asia/Manila');
        $date = $d->toDateString();
        $time = $d->toTimeString();
        $dayname = $d->format('l');

        $dt = explode('-',$date);
        $day = $dt[2];
        $month = $dt[1];
        $year = $dt[0];

        $newArr = [];

        $query = Attendance::with('user')
                ->where(function($query) {
                    return $query->where('day', '=', '13')->where('month', '=', '06')->where('year', '=', '2019');
                })
                ->get();

        $user = User::with('department')->whereHas('department', function ($user) {
                    return $user->where('user_id', '!=', 'null');
                })
                ->with('department.departments')
                ->get();

        foreach($user as $u) {
            $user_id = $u->id;

            $checkAttendance = Attendance::where('day',$day)
                                ->where('month',$month)
                                ->where('year',$year)
                                ->where('user_id',$user_id)
                                ->first();

            $getSchedule = DB::table('schedules')->where('user_id', $user_id)->first();
            array_push($newArr, $getSchedule->schedule_type_id);

            if(!$checkAttendance && ($dayname=='Saturday' || $dayname=='Sunday')) { // If today is weekend
                $timein = new Attendance();
                $timein->user_id = $user_id;
                $timein->day = $day;
                $timein->month = $month;
                $timein->year = $year;
                $timein->timein_status = 'WEEKENDS';
                $timein->timeout_status = 'WEEKENDS';
                $timein->save();
            }

            $cdate = $year.'-'.$month.'-'.$day;

            // Check if there 
            $checkHoliday = CalendarEvents::where(function($query) use ($cdate) {
                                return $query->whereDate('start_date', '<=', $cdate)
                                        ->whereDate('end_date', '>=', $cdate)
                                        ->where('type', 'LIKE', '%Holiday%')
                                        ->where('approval', 'Approved');
                            })
                            ->first();

            $checkLeave = CalendarEvents::where(function($query) use ($user_id, $cdate) {
                            return $query->where('user_id', $user_id)
                                        ->whereDate('start_date', '<=', $cdate)
                                        ->whereDate('end_date', '>=', $cdate)
                                        ->where('approval', 'Approved');
                            })
                            ->first();
            
            if(!$checkAttendance && ($dayname!='Saturday' && $dayname!='Sunday')) {
                

                if($checkHoliday) { // Today is a holiday
                    $timein = new Attendance();
                    $timein->user_id = $user_id;
                    $timein->day = $day;
                    $timein->month = $month;
                    $timein->year = $year;
                    $timein->timein_status = 'HOLIDAY';
                    $timein->timeout_status = 'HOLIDAY';
                    $timein->save();
                }

                if($checkLeave && !$checkHoliday) {
                    $eventType = strtoupper(str_replace(' ', '', $checkLeave->type));
                    $timein = new Attendance();
                    $timein->user_id = $user_id;
                    $timein->day = $day;
                    $timein->month = $month;
                    $timein->year = $year;
                    $timein->timein_status = $eventType;
                    $timein->timeout_status = $eventType;
                    $timein->event_id = $checkLeave->id;
                    $timein->save();
                }

                if(!$checkHoliday && !$checkLeave) {
                    if($getSchedule->schedule_type_id == 3) {
                        $timein = new Attendance();
                        $timein->user_id = $user_id;
                        $timein->day = $day;
                        $timein->month = $month;
                        $timein->year = $year;
                        $timein->time_in = '08:00:00';
                        $timein->time_out = '17:00:00';
                        $timein->save();
                    } else {
                        $timein = new Attendance();
                        $timein->user_id = $user_id;
                        $timein->day = $day;
                        $timein->month = $month;
                        $timein->year = $year;
                        $timein->timein_status = 'ABSENT';
                        $timein->timeout_status = 'ABSENT';
                        $timein->save();
                    }
                }
            } else if($checkAttendance && ($dayname!='Saturday' && $dayname!='Sunday')) {
                if($checkHoliday) {
                    $eventType = strtoupper(str_replace(' ', '', $checkHoliday->type));
                    Attendance::where('day',$day)
                                ->where('month',$month)
                                ->where('year',$year)
                                ->where('user_id',$user_id)
                                ->update(['timein_status'=>$eventType,'timeout_status'=>$eventType,'event_id'=>$checkHoliday->id]);
                }

                if($checkLeave && !$checkHoliday) {
                    $eventType = strtoupper(str_replace(' ', '', $checkLeave->type));
                    Attendance::where('day',$day)
                                ->where('month',$month)
                                ->where('year',$year)
                                ->where('user_id',$user_id)
                                ->update(['timein_status'=>$eventType,'timeout_status'=>$eventType,'event_id'=>$checkLeave->id]);
                }

                if($checkAttendance->time_out==NULL && $getSchedule->schedule_type_id==3){
                    Attendance::where('day',$day)
                                ->where('month',$month)
                                ->where('year',$year)
                                ->where('user_id',$user_id)
                                ->update(['time_out'=>'18:00']);
                }
            }

        }
    }

}
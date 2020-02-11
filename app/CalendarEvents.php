<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CalendarEvents extends Model
{

    protected $table = 'calendar_events';

    protected $fillable = ['id', 'user_id', 'title', 'start_date', 'end_date', 'type', 'approval', 
                            'status', 'year_added', 'day_count', 'hour_count', 'time_start', 'time_end'];

    // protected $fillable = [
    // 	'title', 'status',
    // 	'start_date','end_date'
    // ];

    // protected $dates = ['start_date', 'end_date'];

    // /**
    //  * Get the event's id number
    //  *
    //  * @return int
    //  */
    // public function getId() {
	// 	return $this->id;
	// }

    // /**
    //  * Get the event's title
    //  *
    //  * @return string
    //  */
    // public function getTitle()
    // {
    //     return $this->title;
    // }

    // /**
    //  * Is it an all day event?
    //  *
    //  * @return bool
    //  */
    // public function isAllDay()
    // {
    //     return (bool)$this->all_day;
    // }

    // /**
    //  * Get the start time
    //  *
    //  * @return DateTime
    //  */
    // public function getStart()
    // {
    //     return $this->start_date;
    // }

    // /**
    //  * Get the end time
    //  *
    //  * @return DateTime
    //  */
    // public function getEnd()
    // {
    //     return $this->end_date;
    // }
}

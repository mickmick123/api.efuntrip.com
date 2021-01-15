<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Auth, Carbon\Carbon, DB, Response, Validator;
use Edujugon\PushNotification\PushNotification;

class LogsPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user_id;
    protected $message;
    protected $log_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id, $message = null, $log_id = null)
    {
        $this->user_id = $user_id;
        $this->message = $message;
        $this->log_id = $log_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $devices = DB::table('devices')->where('user_id', $this->user_id)->groupBy('device_token')->get();

        $iosToken = [];
        $androidToken = [];
        if(count($devices)) {
            foreach($devices as $device) {
                if($device->device_type === "IOS"){
                    array_push($iosToken, $device->device_token);
                }else{
                    array_push($androidToken, $device->device_token);
                }
            }
        }

        if(count($iosToken) > 0) {
            $ios = new PushNotification('apn');
            $ios->setMessage([
                    'notification' => [
                        'body' => $this->message,
                        'sound' => 'default'
                    ],
                    'data' => [
                        'id' => $this->log_id
                    ]
                ])
                ->setDevicesToken($iosToken)
                ->send();
        }

        if(count($androidToken) > 0) {
            $push = new PushNotification('fcm');
            $push->setUrl('https://fcm.googleapis.com/fcm/send')
                ->setMessage([
                    'notification' => [
                        //'title'=>'',
                        'body' => $this->message,
                        'sound' => 'default'
                    ],
                    'data' => [
                        'id' => $this->log_id
                    ]
                ])
                ->setConfig(['dry_run' => false,'priority' => 'high'])
                ->setApiKey('AAAAIynhqO8:APA91bH5P-SGimP4b0jazCrC8ya7bV9LoR57wWB9zLqatXfRyxSIdKs2_q4-e01Ofce6oxW-7YQOGlk4Sov4WwiUAE7qojRu-3xb9429ve0Ufkh4JDMaod7cKBAxbypFUPJNKX0yoe98')
                ->setDevicesToken($androidToken)
                ->send();
        }

//        DB::table('logs_notification')
//            ->where('log_id', $this->log_id)
//            ->where('job_id', $this->job->getJobId())
//            ->update([
//                'status' => 0
//            ]);
    }
}

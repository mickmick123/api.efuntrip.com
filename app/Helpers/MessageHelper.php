<?php

namespace App\Helpers;

use App\Traits\FilterTrait;

class MessageHelper
{
    use FilterTrait;
    
    public static function MsgNotification($type, $data = [])
    {
        $self = new static;
        $msg = "";
        if ($type == "E-wallet Deposit") {
            if ($data['group_id'] !== null) {
                $msg = "Your e-wallet deposit amounting to " . $self->absNumber($data['amount']) . " on " . $data['date'] . " has been added to your group " . $data['group_name'] . ". Your new group balance is " . $self->absNumber($data['balance']);
            } else {
                $msg = "Your e-wallet deposit amounting to " . $self->absNumber($data['amount']) . " on " . $data['date'] . " has been added to your account. Your new balance is " . $self->absNumber($data['balance']);
            }
        } else if ($type == "Document Released") {
            $msg = "Your documents have been released on " . $data['date'] . " You can now view it on your account.";
        } else if ($type == "Document Received") {
            $msg = "Your documents have been received by " . $data['user'] . " on " . $data['date'] . " you can now view it on your account.";
        } else if ($type == "Withdrawal") {
            if ($data['group_id'] !== null) {
                $msg = "Your withdrawal amounting to " . $self->absNumber($data['amount']) . " has been successfully processed to your group " . $data['group_name'] . ". on " . $data['date'];
            } else {
                $msg = "Your withdrawal amounting to " . $self->absNumber($data['amount']) . " has been successfully processed on " . $data['date'];
            }
        } else if ($type == "Service Payment 1") {
            $msg = "Your payment for " . $data['service_name'] . ", amounting to " . $self->absNumber($data['amount']) . " has been successfully processed on " . $data['date'];
        } else if ($type == "Service Payment 2") {
            $msg = "Your payment amounting to " . $self->absNumber($data['amount']) . " through " . $data['bank'] . " has been successfully processed on " . $data['date'] . ".";
        } else if ($type == "Service Payment 3") {
            $temp = [];
            $group = collect($data['group'])->sortBy('service');
            $getUniqueService = $group->unique('service');
            foreach ($getUniqueService as $k => $v) {
                $temp['clients'] = [];
                $temp['total_amount'] = 0;
                foreach ($group as $kk => $vv) {
                    if ($v['service'] === $vv['service']) {
                        $temp['clients'][$kk] = $vv['client_name'];
                        $temp['total_amount'] += $vv['amount'];
                    }
                }
                $temp['message'][$k] = ArrayHelper::CommaAnd(array_unique($temp['clients']), ', ', ' and ') . PHP_EOL . "Paid total amount of " . number_format($temp['total_amount']) . " to service " . $v['service'];
            }
            $msg = implode(PHP_EOL . PHP_EOL, $temp['message']);
        } elseif ($type == "Added Service") {
            if (array_key_exists("clients", $data)) {
                if($data['is_leader']) {
                    $msg .= ArrayHelper::CommaAnd($data['clients']['name'], ', ', ' and ') . PHP_EOL;
                }
                $services = $data['clients']['service'];
                //$i=0;
                $message = [];
                foreach ($services as $k => $v){
                    array_push($message, $v);
                    //$msg .= "Package #" . $data['clients']['package'][$k] . " with the Total Service Cost of " . $data['clients']['amount'][$k] . PHP_EOL;
                    //if(count($services)-1 != $i) {
                    //    $msg .= PHP_EOL;
                    //}
                    //$i++;
                }
                $msg .= ArrayHelper::CommaAnd($message, ',' . PHP_EOL, ' and' . PHP_EOL);
            }else{
                $msg = $data['service'];
                //$msg = $data['service'] . PHP_EOL . "Package #" . $data['package'] . " with the Total Service Cost of " . number_format($data['amount']);
            }

        }

        return $msg;
    }
}

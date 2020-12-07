<?php

namespace App\Helpers;

class MessageHelper
{
    public static function MsgNotification($type, $data = [])
    {
        $msg = "";
        if ($type == "E-wallet Deposit") {
            $msg = "Your e-wallet deposit amounting to " . $data['amount'] . " on " . $data['date'] . " has been added to your account. Your new balance is" . $data['balance'];
        } else if ($type == "Documents Released") {
            $msg = "Your documents have been released on " . $data['date'] . " You can now view it on your account.";
        } else if ($type == "Documents Received") {
            $msg = "Your documents have been received by " . $data['user'] . " on " . $data['date'] . " you can now view it on your account.";
        } else if ($type == "Withdrawal") {
            $msg = "Your withdrawal amounting to " . $data['amount'] . " has been successfully processed on " . $data['date'];
        } else if ($type == "Service Payment 1") {
            $msg = "Your payment amounting to " . $data['amount'] . " has been successfully processed on " . $data['date'];
        } else if ($type == "Service Payment 2") {
            $msg = "Your payment amounting to " . $data['amount'] . " through " . $data['bank'] . " has been successfully processed on " . $data['date'] . ".";
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
                $temp['message'][$k] = ArrayHelper::CommaAnd($temp['clients']) . PHP_EOL . "Paid total amount of " . $temp['total_amount'] . " to service " . $v['service'];
            }
            $msg = implode(PHP_EOL . PHP_EOL, $temp['message']);
        } elseif ($type == "Added Service") {
            if (array_key_exists("clients", $data)) {
                if($data['is_leader']) {
                    $msg .= ArrayHelper::CommaAnd($data['clients']['name']) . PHP_EOL;
                }
                $services = $data['clients']['service'];
                $i=0;
                foreach ($services as $k => $v){
                    $msg .= $v . PHP_EOL;
                    $msg .= "Package #" . $data['clients']['package'][$k] . " with the Total Service Cost of " . $data['clients']['amount'][$k] . PHP_EOL;
                    if(count($services)-1 != $i) {
                        $msg .= PHP_EOL;
                    }
                    $i++;
                }
            }else{
                $msg = $data['service'] . PHP_EOL . "Package #" . $data['package'] . " with the Total Service Cost of " . $data['amount'];
            }

        }

        return $msg;
    }
}

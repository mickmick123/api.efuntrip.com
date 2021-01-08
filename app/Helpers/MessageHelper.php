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
                $msg = "Your e-wallet deposit amounting to " . $self->absNumber($data['amount']) . " has been added to your group " . $data['group_name'] . ". Your new group balance is " . $self->absNumber($data['balance']);
            } else {
                $msg = "Your e-wallet deposit amounting to " . $self->absNumber($data['amount']) . " has been added to your account. Your new balance is " . $self->absNumber($data['balance']);
            }
        } else if ($type == "Document Released") {
            if (strpos($data['detail'], "client's representative ")) {
                $receiver = explode("client's representative ", $data['label'])[1]."\n ";
                $detail = explode('.', explode($receiver, $data['detail'])[1])[0];
            } else {
                $detail = explode('.', explode("client\n ", $data['detail'])[1])[0];
            }
            $msg = "Your documents " . $detail . " have been released.";
        }
        //else if ($type == "Document Received") {
        //    if (strpos($data['detail'], "client's representative ")) {
        //        $receiver = explode("client's representative ", $data['label'])[1];
        //        $detail = explode('.', explode($receiver . ' ', $data['detail'])[1])[0];
        //    } else {
        //       $detail = explode('.', explode("client ", $data['detail'])[1])[0];
        //    }
        //    $msg = "Your documents " . $detail . " have been received.";
        //}
        else if ($type == "Withdrawal") {
            if ($data['group_id'] !== null) {
                $msg = "Your withdrawal amounting to " . $self->absNumber($data['amount']) . " has been successfully processed to your group " . $data['group_name']; //. ". on " . $data['date'];
            } else {
                $msg = "Your withdrawal amounting to " . $self->absNumber($data['amount']) . " has been successfully processed"; //on " . $data['date'];
            }
        } else if ($type == "Service Payment 1") {
            $msg = "Your payment for " . $data['service_name'] . ", amounting to " . $self->absNumber($data['amount']) . " has been successfully processed"; // on " . $data['date'];
        } else if ($type == "Service Payment 2") {
            $msg = "Your payment amounting to " . $self->absNumber($data['amount']) . " through " . $data['bank'] . " has been successfully processed"; // on " . $data['date'] . ".";
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
                $temp['message'][$k] = ArrayHelper::CommaAnd(array_unique($temp['clients']), ', ', ' and ') . PHP_EOL . "Paid total amount of " . $self->absNumber($temp['total_amount']) . " to service " . $v['service'];
            }
            $msg = implode(PHP_EOL . PHP_EOL, $temp['message']);
        } elseif ($type == "Added Service") {
            if (array_key_exists("clients", $data)) {
                $msg .= PHP_EOL;
                if($data['is_leader']) {
                    $msg .= ArrayHelper::CommaAnd($data['clients']['name'], ', ', ' and ') . PHP_EOL;
                }
                $services = $data['clients']['service'];
                $i=1;
                foreach ($services as $k => $v){
                    //$msg .= "Package #" . $data['clients']['package'][$k] . " with the Total Service Cost of " . $data['clients']['amount'][$k] . PHP_EOL;

                    $msg .= "$i. $v";
                    $msg .= count($services) != $i?PHP_EOL:"";
                    $i++;
                }
            }else{
                $i=1;
                //$msg .= "Service Added: ";
                foreach ($data['service'] as $v) {
                    if(count($data['service']) == 1){
                        $msg .= $v;
                    }else{
                        $msg .= $i == 1?PHP_EOL:"";
                        $msg .= "$i. $v";
                        $msg .= count($data['service']) != $i?PHP_EOL:"";
                    }
                    $i++;
                }

            }

        }

        return $msg;
    }
}

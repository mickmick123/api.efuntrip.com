<?php

namespace App\Helpers;

class MessageHelper
{
    public static function MsgNotification($type, $data=[]) {
        $msg = "";
        if ($type == "E-wallet Deposit") {
            $msg = "Your e-wallet deposit amounting to ".$data['amount']." on ".$data['date']." has been added to your account. Your new balance is". $data['balance'];
        }
        if ($type == "Documents Released") {
            $msg = "Your documents have been released on ".$data['date']." You can now view it on your account.";
        }
        if ($type == "Documents Received"){
            $msg = "Your documents have been received by ". $data['user'] ." on ".$data['date']." you can now view it on your account.";
        }
        if($type == "Withdrawal" || $type == "Service Payment 1") {
            $type = "withdrawal";
            if ($type != "Withdrawal") {
                $type = "payment";
            }
            $msg = "Your $type amounting to ".$data['amount']." has been successfully processed on ".$data['date'];
        }
        if($type == "Service Payment 2") {
            $msg = "Your payment amounting to ".$data['amount']." through ".$data['$data->bank']." has been successfully processed on ".$data['date'].".";
        }
        if($type == "Service Payment 3") {
            $msg = $data['clients']. PHP_EOL . "Paid total amount of ".$data['amount']." to service ".$data['duration'] . PHP_EOL . $data['service'];
        }
        if($type == "Added Service") {
            $x = "";
            if ($data['clients'] && $data['clients'] != null) {
                $x = $data['client'] . PHP_EOL;
            }
            $msg = $x . $data['service'] . PHP_EOL . "Package ". $data['package'] ." with the Total Service Cost of ".$data['amount'];
        }

        return $msg;
    }
}

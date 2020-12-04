<?php

namespace App\Helpers;

class MessageHelper
{
    public static function MsgNotification($type, $date, $option1=null, $option2=null, $option3=null, $option4=null)
    {
        $msg = '';

        switch ($type) {
            case "E-wallet Deposit":
                $msg =  "Your e-wallet deposit amounting to $option1 on $date has been added to your account. Your new balance is $option2";
                break;

            case "Documents Released":
                $msg =  "Your documents have been released on $date You can now view it on your account.";
                break;

            case "Documents Received":
                $msg =  "Your documents have been received by $option1 on $date you can now view it on your account.";
                break;

            case ("Withdrawal" || "Service Payment 1"):
                $type = "withdrawal";
                if($type != "Withdrawal"){
                    $type = "payment";
                }
                $msg =  "Your $type amounting to $option1 has been successfully processed on $date";
                break;

            case "Service Payment 2":
                $msg =  "You payment amounting to $option1 through $option2 has been successfully processed on $date.";
                break;

            case "Service Payment 3":
                $msg =  "$option1".PHP_EOL."Paid total amount of $option2 to service $option3".PHP_EOL.$option4;
                break;

            case "Added Service":
                $msg =  "$option1".PHP_EOL."$option2".PHP_EOL."Package $option3 with the Total Service Cost of $option4";
                break;
        }

        return $msg;
    }

}

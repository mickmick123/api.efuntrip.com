<?php

	/**
		* @param string, required $title
		* @param string, required $body
 		* @param array, required $users
 	**/
	function pushNotification($title, $body, $users) {
		$devices = \App\Device::whereIn('user_id', $users)->select(['device_type', 'device_token'])->get();

		$iosDeviceTokens = getTokens($devices, 'IOS');
		if( count($iosDeviceTokens) > 0 ) {
			sendToIos($title, $body, $iosDeviceTokens);
		}

		$androidDeviceTokens = getTokens($devices, 'ANDROID');
		if( count($androidDeviceTokens) > 0 ) {
			sendToAndroid($title, $body, $androidDeviceTokens);
		}
	}

	function getTokens($devices, $platform) {
		return $devices->filter(function($value, $key) use($platform) {
		    return $value->device_type == $platform;
		})->values()->map(function($item, $key) {
		    return $item['device_token'];
		});;
	}

	function sendToIos($title, $body, $iosDeviceTokens) {
		$push = new \Edujugon\PushNotification\PushNotification('apn');
        $push->setMessage([
            'aps' => [
                'alert' => [
                    'title' => $title,
                    'body' => $body
                ],
                'sound' => 'default',
								'badge' => 1
            ]
        ])
        ->setDevicesToken($iosDeviceTokens)
        ->send();
	}

	function sendToAndroid($title, $body, $androidDeviceTokens) {
		$push = new \Edujugon\PushNotification\PushNotification;
        $push->setMessage([
            'notification' => [
                'title'=> $title,
                'body'=> $body,
                'sound' => 'default'
            ]
        ])
        // ->setApiKey('AIzaSyA7ev628GjNpVE5WMy7mhARgvFur_JZ-zg')
        ->setApiKey('AIzaSyDPF-KM8WG3bIyj0t9Ybf-SU41e3XPy--o')
        ->setDevicesToken($androidDeviceTokens)
        ->send();
	}

?>

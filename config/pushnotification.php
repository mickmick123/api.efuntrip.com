<?php
/**
 * @see https://github.com/Edujugon/PushNotification
 */

return [
    'gcm' => [
        'priority' => 'normal',
        'dry_run' => true,
        'apiKey' => 'AIzaSyA7ev628GjNpVE5WMy7mhARgvFur_JZ-zg',
    ],
    'fcm' => [
        'priority' => 'normal',
        'dry_run' => false,
        'apiKey' => '',
    ],
    'apn' => [
        'certificate' => __DIR__ . '/iosCertificates/visa_app_cert.pem',
        'passPhrase' => 'qwerty123', //Optional
        'passFile' => '', //Optional
        'dry_run' => true,
    ],
];

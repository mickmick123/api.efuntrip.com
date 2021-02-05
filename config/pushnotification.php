<?php
/**
 * @see https://github.com/Edujugon/PushNotification
 */

return [
    'gcm' => [
        'priority' => 'normal',
        'dry_run' => false,
        'apiKey' => 'AIzaSyDPF-KM8WG3bIyj0t9Ybf-SU41e3XPy--o',
    ],
    'fcm' => [
        'priority' => 'normal',
        'dry_run' => false,
        'apiKey' => 'AIzaSyDPF-KM8WG3bIyj0t9Ybf-SU41e3XPy--o',
    ],
    'apn' => [
        'certificate' => __DIR__ . '/iosCertificates/fourways_app_cert.pem',
        'passPhrase' => '123admin', //Optional
        'passFile' => '', //Optional
        'dry_run' => false,
    ],
];

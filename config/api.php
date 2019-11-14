<?php
return [
    'domain'=>'http://api.com',
    //passport配置
    'grant_type' => env('OAUTH_GRANT_TYPE'),
    'client_id' => env('OAUTH_CLIENT_ID'),
    'client_secret' => env('OAUTH_CLIENT_SECRET'),
    'scope' => env('OAUTH_SCOPE', '*'),
];

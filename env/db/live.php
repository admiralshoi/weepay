<?php
return [
    'default' => "testing",
    'connections' => [
        'testing' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'co9i1xgl0_weepaysolutions',
            'username' => 'co9i1xgl0_weepaysolutions',
            'password' => 'adLt3vJXmhNTRVn',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            "prefix" => "TEST_PEC_"
        ],
        'production' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'co9i1xgl0_weepaysolutions',
            'username' => 'co9i1xgl0_weepaysolutions',
            'password' => 'adLt3vJXmhNTRVn',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            "prefix" => "PROD_FKF_"
        ],
    ],
];
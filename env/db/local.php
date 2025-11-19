<?php
return [
    'default' => "testing",
    'connections' => [
        'testing' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'weepay',
            'username' => 'root',
            'password' => 'macOSMysqlPwd',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            "prefix" => "TEST_PEC_"
        ],
        'production' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'weepay',
            'username' => 'root',
            'password' => 'macOSMysqlPwd',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            "prefix" => "PROD_FKF_"
        ],
    ],
];
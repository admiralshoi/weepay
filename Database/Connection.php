<?php

namespace Database;

use PDO;

class Connection {
    protected static PDO $pdo;


    public static function getPdo($config = []): PDO {
        if(empty($config)) $config = DbConnection::getNewConfig();
        if(empty($config)) return self::$pdo;


        self::$pdo = new PDO(
            "{$config['driver']}:host={$config['host']};dbname={$config['database']};",
            $config['username'],
            $config['password']
        );
        return self::$pdo;
    }
}


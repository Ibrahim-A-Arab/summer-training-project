<?php

namespace App\Utils;
use PDO;
class Database{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct(){
        
        $config = require __DIR__ . '/../config/database.php';

        $dsn =
            "mysql:host={$config['host']};" .
            "port={$config['port']};" .
            "dbname={$config['name']};" .
            "charset=utf8mb4";

        $this->connection = new PDO(
            $dsn,
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public static function getInstance(): Database{
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function execute(string $sql, array $params = []): bool{
        $statement = $this->connection->prepare($sql);

        return $statement->execute($params);
    }

    public function select(string $sql, array $params = []): array{
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    

}

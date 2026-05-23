<?php
class Database {
    private string $host = 'localhost';
    private string $dbName = 'smartcampus';
    private string $user = 'root';
    private string $password = '';
    private int $port = 3309;
    private ?PDO $connection = null;

    public function connect(): PDO {
        if ($this->connection === null) {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, $this->user, $this->password, $options);
        }
        return $this->connection;
    }
}

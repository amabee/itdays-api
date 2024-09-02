<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

class DatabaseConnection
{
    private $server;
    private $username;
    private $password;
    private $database;
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        $this->server = $_ENV['DATABASE_HOST'];
        $this->username = $_ENV['DATABASE_USER'];
        $this->password = $_ENV['DATABASE_PASS'];
        $this->database = $_ENV['DATABASE_DB'];

        $this->conn = new PDO('mysql:host=' . $this->server . ';dbname=' . $this->database, $this->username, $this->password);
        
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new DatabaseConnection();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        
        return $this->conn;
    }
}
?>

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
include "../connection.php";

class Main
{
    private $conn;

    public function __construct()
    {
        $this->conn = DatabaseConnection::getInstance()->getConnection();
    }

    public function addHandler($json)
    {
        $json = json_decode($json, true);

        if (!$json || !isset($json['h_fname'], $json['h_lname'], $json['h_email'], $json['h_pwd'], $json['hid'], )) {
            return json_encode(["error" => "Invalid input data"]);
        }

        $password = sha1($json['h_pwd']);
        $handler_id = $json['hid'];
        $email = $json['h_email'];

        try {

            $checkSql = "SELECT 1 FROM `handlers` WHERE `handler_id` = :hid OR `h_email` = :hemail";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->bindParam(':hid', $handler_id);
            $stmt->bindParam(':hemail', $email);
            $stmt->execute();

            if ($stmt->fetch()) {
                return json_encode(["error" => "Handler ID or email already exists"]);
            }

            // Insert into handlers table
            $insertSql = "INSERT INTO `handlers`(`handler_id`, `h_fname`, `h_lname`, `h_email`, `h_pwd`, `status`, `created_at`) 
                          VALUES (:hid, :hfname, :hlname, :hemail, :hpwd, 'active', NOW())";
            $stmt = $this->conn->prepare($insertSql);
            $stmt->bindParam(':hid', $handler_id);
            $stmt->bindParam(':hfname', $json['h_fname']);
            $stmt->bindParam(':hlname', $json['h_lname']);
            $stmt->bindParam(':hemail', $email);
            $stmt->bindParam(':hpwd', $password);


            if ($stmt->execute()) {
                return json_encode(["success" => "Handler added successfully"]);
            } else {
                return json_encode(["error" => "Failed to add handler"]);
            }
        } catch (PDOException $e) {
            return json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
    }


    public function getHandlers()
    {
        try {
            $sql = "SELECT handlers.handler_id, `h_fname`, `h_lname`, `h_email`, `status`, handlers.created_at, tribu.tribu_name
                     FROM `handlers`
                     LEFT JOIN tribu ON handlers.handler_id = tribu.handler_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $handlers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($handlers) {
                return json_encode(["success" => $handlers]);
            } else {
                return json_encode(["error" => "No handlers found"]);
            }
        } catch (PDOException $e) {
            return json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
    }

    public function addTribu($json)
    {
        $json = json_decode($json, true);

        if (!$json || !isset($json['tribu_name'], $json['handler_id'])) {
            return json_encode(["error" => "Invalid input data"]);
        }

        $tribu_name = $json['tribu_name'];
        $handler_id = $json['handler_id'];
        $created_at = date('Y-m-d H:i:s');


        try {

            $checkSql = "SELECT * FROM `tribu` WHERE LOWER(`tribu_name`) = LOWER(:tribu_name)";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->bindParam(':tribu_name', $tribu_name);
            $stmt->execute();

            if ($stmt->fetch()) {
                return json_encode(["error" => "Tribu already exists"]);
            }

            $insertSql = "INSERT INTO `tribu` (`tribu_name`, `handler_id`, `created_at`) 
                          VALUES (:tribu_name, :handler_id, :created_at)";
            $stmt = $this->conn->prepare($insertSql);
            $stmt->bindParam(':tribu_name', $tribu_name);
            $stmt->bindParam(':handler_id', $handler_id);
            $stmt->bindParam(':created_at', $created_at);

            if ($stmt->execute()) {
                return json_encode(["success" => "Tribu added successfully"]);
            } else {
                return json_encode(["error" => "Failed to add tribu"]);
            }
        } catch (PDOException $e) {
            return json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
    }


    public function getTribu()
    {
        try {
            $sql = "SELECT * FROM tribu";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $tribu = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($tribu) {
                return json_encode(["success" => $tribu]);
            } else {
                return json_encode(["error" => "No Tribu found"]);
            }
        } catch (PDOException $e) {
            return json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
    }

}

$main = new Main();

if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_REQUEST['operation']) && isset($_REQUEST['json'])) {
        $operation = $_REQUEST['operation'];
        $json = $_REQUEST['json'];

        switch ($operation) {
            case 'addHandler':
                echo $main->addHandler($json);
                break;
            case 'getHandlers':
                echo $main->getHandlers();
                break;
            case 'getTribu':
                echo $main->getTribu();
                break;
            case 'addTribu':
                echo $main->addTribu($json);
                break;
            default:
                echo json_encode(["error" => "Invalid operation"]);
                break;
        }
    } else {
        echo json_encode(["error" => "Missing parameters"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}
?>
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
include "../connection.php";

class Auth
{
    private $conn;

    public function __construct()
    {
        $this->conn = DatabaseConnection::getInstance()->getConnection();
    }

    public function login($json)
    {
        $json = json_decode($json, true);

        try {
            if (isset($json['username']) && isset($json['password'])) {
                $username = $json['username'];
                $password = sha1($json['password']);

                $sql = 'SELECT 
                            handlers.handler_id, 
                            handlers.h_fname, 
                            handlers.h_lname, 
                            handlers.h_email, 
                            handlers.status, 
                            tribu.pid 
                        FROM 
                            handlers
                        INNER JOIN 
                            tribu 
                        ON 
                            handlers.handler_id = tribu.handler_id
                        WHERE 
                            (handlers.handler_id = :username OR handlers.h_email = :username) 
                            AND handlers.h_pwd = :password'
                ;

                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':password', $password, PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    unset($this->conn);
                    unset($stmt);
                    return json_encode(array("success" => $row));
                } else {
                    return json_encode(array('error' => 'Invalid Credentials'));
                }
            } else {
                return json_encode(array('error' => 'Username or Password required!'));
            }
        } catch (PDOException $e) {
            return json_encode(array('error' => $e->getMessage()));
        }
    }

    public function signup($json)
    {
        $json = json_decode($json, true);

        try {
            if (
                isset($json['username']) && isset($json['password']) && isset($json['firstname'])
                && isset($json['lastname']) && isset($json['email'])
            ) {
                $username = $json['username'];
                $password = sha1($json['password']);
                $firstname = $json['firstname'];
                $lastname = $json['lastname'];
                $email = $json['email'];
                $status = 'active';

                $checkSql = 'SELECT * FROM `handlers` WHERE `handler_id` = :username OR `h_email` = :email';
                $checkStmt = $this->conn->prepare($checkSql);
                $checkStmt->bindParam(':username', $username, PDO::PARAM_STR);
                $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
                $checkStmt->execute();

                if ($checkStmt->rowCount() > 0) {
                    return json_encode(array("error" => "Username or email already exists. Please choose another."));
                }

                $sql = 'INSERT INTO `handlers` (`handler_id`, `h_pwd`, `h_fname`, `h_lname`, `h_email`, `status`) 
                        VALUES (:username, :password, :firstname, :lastname, :email, :status)';
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':password', $password, PDO::PARAM_STR);
                $stmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
                $stmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    return json_encode(array("success" => "User account successfully created."));
                } else {
                    return json_encode(array("error" => "Failed to create user account."));
                }
            } else {
                return json_encode(array("error" => "All fields (username, password, firstname, lastname, email) are required!"));
            }
        } catch (PDOException $e) {
            return json_encode(array('error' => 'An error occurred while creating the account.', 'exception_message' => $e->getMessage()));
        }
    }
}

$auth = new Auth();

if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_REQUEST['operation']) && isset($_REQUEST['json'])) {
        $operation = $_REQUEST['operation'];
        $json = $_REQUEST['json'];

        switch ($operation) {
            case 'login':
                echo $auth->login($json);
                break;

            case 'signup':
                echo $auth->signup($json);
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
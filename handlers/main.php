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

    public function getTribuMembers($json)
    {
        $json = json_decode($json, true);

        try {
            // Base SQL query
            $sql = "SELECT 
                        tribu.tribu_name,
                        students.stud_fname,
                        students.stud_lname,
                        students.tribu_id,
                        attendance.time_in,
                        attendance.time_out
                    FROM 
                        tribu
                    LEFT JOIN 
                        students ON tribu.pid = students.tribu_id
                    LEFT JOIN 
                        attendance ON students.stud_id = attendance.stud_id
                    LEFT JOIN 
                        event ON attendance.event_id = event.event_id
                    WHERE 
                        tribu.handler_id = :hid";

            // Check if event_id is present and adjust SQL query accordingly
            if (isset($json['event_id']) && !empty($json['event_id'])) {
                $sql .= " AND event.event_id = :event_id";
            }

            $sql .= " GROUP BY 
                        tribu.tribu_name, students.stud_fname, students.stud_lname, students.tribu_id, attendance.time_in, attendance.time_out";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":hid", $json['hid']);

            // Bind event_id parameter if it is set
            if (isset($json['event_id']) && !empty($json['event_id'])) {
                $stmt->bindParam(":event_id", $json['event_id']);
            }

            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode(array("success" => $result));

        } catch (PDOException $e) {
            return json_encode(array("error" => $e->getMessage()));
        }
    }

    public function getEvents()
    {
        try {
            $sql = "SELECT * FROM `event` ORDER BY event_date ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return json_encode(array("success" => $result));
            } else {
                return json_encode(array("success" => "No data found"));
            }
        } catch (PDOException $e) {
            return json_encode(array("success" => $e->getMessage()));
        }
    }

    public function getStudentsWithoutTribu()
    {
        try {
            $sql = "SELECT * FROM `students` WHERE tribu_id IS NULL OR tribu_id = ''";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return json_encode(array("success" => $result));
            } else {
                return json_encode(array("success" => []));
            }
        } catch (PDOException $e) {
            return json_encode(array("error" => $e->getMessage()));
        }
    }

    public function updateStudentTribuId($json)
    {
        $data = json_decode($json, true);

        $stud_id = $data['stud_id'];
        $tribu_id = $data['tribu_id'];

        try {

            $sql = "UPDATE `students` SET tribu_id = :tribu_id WHERE stud_id = :stud_id";
            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':tribu_id', $tribu_id);
            $stmt->bindParam(':stud_id', $stud_id);

            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return json_encode(array("success" => "Student tribu_id updated successfully"));
            } else {
                return json_encode(array("success" => "No changes made or student not found"));
            }
        } catch (PDOException $e) {
            return json_encode(array("error" => $e->getMessage()));
        }
    }




}

$main = new Main();

if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_REQUEST['operation']) && isset($_REQUEST['json'])) {
        $operation = $_REQUEST['operation'];
        $json = $_REQUEST['json'];

        switch ($operation) {
            case 'getTribuMembers':
                echo $main->getTribuMembers($json);
                break;

            case 'getEvents':
                echo $main->getEvents();
                break;

            case 'getStudentsWithoutTribu':
                echo $main->getStudentsWithoutTribu();
                break;

            case "updateStudentTribuId":
                echo $main->updateStudentTribuId($json);
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
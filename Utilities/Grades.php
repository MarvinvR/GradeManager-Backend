<?php

include_once "./Helpers/GradeCalculation.php";

class Grades {
    
    private $dbConn;
    private $genericFunctions;
    private $user;

    public function __construct() {
        $this->dbConn = $GLOBALS['dbConn'];
        $this->genericFunctions = $GLOBALS['genericFunctions'];
        $this->user = $GLOBALS['user'];
    }

    public function add() {
        $user = $this->user;
        $dbConn = $this->dbConn;
        $genericFunctions = $this->genericFunctions;

        if(!($user->uid))
        {
            $genericFunctions->sendError(401, "You are not signed in.");
        }

        if(!isset($_POST['name']) || !isset($_POST['semesterid']) || !isset($_POST['subjectid']) || !isset($_POST['grade']))
        {
            $genericFunctions->sendError(400, "Please send a valid request.");
        }

        $name = $_POST['name'];
        $semesterid = $_POST['semesterid'];
        $subjectid = $_POST['subjectid'];
        $grade = floatval($_POST['grade']);

        if (!is_numeric($grade)) {
            $genericFunctions->sendError(400, "Please enter a valid grade.");
        }

        $sqlAddSubject = "INSERT INTO grades(uid, semesterid, subjectid, name, grade) VALUES( ? , ? , ? , ? , ? );";
        $stmtAddSubject = $dbConn->prepare($sqlAddSubject);
        $stmtAddSubject->bind_param("iiisd", $user->uid, $semesterid, $subjectid, $name, $grade);
        
        if (!$stmtAddSubject->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $stmtAddSubject->close();

        // create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        $genericFunctions->sendResponse(200, $jwtUser, 'Successfully created grade "'.$name.'".');

    }

    public function getAll() {
        $user = $this->user;
        $dbConn = $this->dbConn;
        $genericFunctions = $this->genericFunctions;

        if(!($user->uid))
        {
            $genericFunctions->sendError(401, "You are not signed in.");
        }

		if(!isset($_POST["semesterid"]) || !isset($_POST["subjectid"])) {
            $genericFunctions->sendError(400, "Please send a valid request.");
		}
		
		$semesterid = $_POST["semesterid"];
		$subjectid = $_POST["subjectid"];
		
        $sqlGetGrades = "SELECT id, name, grade FROM grades WHERE uid = ? AND subjectid = ? AND semesterid = ?";
        $stmtGetGrades = $dbConn->prepare($sqlGetGrades);
        $stmtGetGrades->bind_param("iii", $user->uid, $subjectid, $semesterid);
        
        if (!$stmtGetGrades->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultGetGrades = $stmtGetGrades->get_result();

        if($resultGetGrades->num_rows < 1)
        {
            $genericFunctions->sendError(404, "No grades found.");
        }

        $resultGetGrades = $resultGetGrades->fetch_all();
		
		$output = $resultGetGrades;
		
        $stmtGetGrades->close();

        // create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        $genericFunctions->sendResponse(200, $jwtUser, $output);

    }

    public function delete() {
        $user = $this->user;
        $dbConn = $this->dbConn;
        $genericFunctions = $this->genericFunctions;

        if(!($user->uid))
        {
            $genericFunctions->sendError(401, "You are not signed in.");
        }

        if(!isset($_POST['id']))
        {
            $genericFunctions->sendError(400, "Please send a valid request.");
        }

        $id = $_POST['id'];

        $sqlVerifyOwner = "SELECT uid FROM grades WHERE id = ? ";
        $stmtVerifyOwner = $dbConn->prepare($sqlVerifyOwner);
        $stmtVerifyOwner->bind_param("i", $id);

        if (!$stmtVerifyOwner->execute())
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultVerifyOwner = $stmtVerifyOwner->get_result();

        if ($resultVerifyOwner->num_rows < 1)
        {
            $genericFunctions->sendError(404, "Semester does not exist.");
        }

        $resultVerifyOwner = $resultVerifyOwner->fetch_all()[0];

        if ($resultVerifyOwner[0] != $user->uid) 
        {
            $genericFunctions->sendError(404, "Subject does not exist.");
        }

        $stmtVerifyOwner->close();

        $sqlDeleteGrade = "DELETE FROM grades WHERE id = ? ";
        $stmtDeleteGrade = $dbConn->prepare($sqlDeleteGrade);
        $stmtDeleteGrade->bind_param("i", $id);
        
        if (!$stmtDeleteGrade->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $stmtDeleteGrade->close();

        
        // create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        // return response
        $genericFunctions->sendResponse(200, $jwtUser, 'Successfully deleted subject.');

    }

    public function update() {
        $user = $this->user;
        $dbConn = $this->dbConn;
        $genericFunctions = $this->genericFunctions;

        if(!($user->uid))
        {
            $genericFunctions->sendError(401, "You are not signed in.");
        }

        if(!isset($_POST['id']) || !isset($_POST['name']) || !isset($_POST['grade']))
        {
            $genericFunctions->sendError(400, "Please send a valid request.");
        }

        $id = $_POST['id'];
        $name = $_POST['name'];
        $grade = floatval($_POST['grade']);

        if (!is_numeric($grade)) 
        {
            $genericFunctions->sendError(400, "Please enter a valid grade.");
        }

        $sqlVerifyOwner = "SELECT uid FROM grades WHERE id = ? ";
        $stmtVerifyOwner = $dbConn->prepare($sqlVerifyOwner);
        $stmtVerifyOwner->bind_param("i", $id);

        if (!$stmtVerifyOwner->execute())
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultVerifyOwner = $stmtVerifyOwner->get_result();

        if ($resultVerifyOwner->num_rows < 1)
        {
            $genericFunctions->sendError(404, "Semester does not exist.");
        }

        $resultVerifyOwner = $resultVerifyOwner->fetch_all()[0];

        if ($resultVerifyOwner[0] != $user->uid) 
        {
            $genericFunctions->sendError(404, "Grade does not exist.");
        }

        $stmtVerifyOwner->close();

        $sqlUpdateGrade = "UPDATE grades SET name = ? , grade = ? WHERE id = ? ";
        $stmtUpdateGrade = $dbConn->prepare($sqlUpdateGrade);
        $stmtUpdateGrade->bind_param("sdi", $name, $grade, $id);
        
        if (!$stmtUpdateGrade->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $stmtUpdateGrade->close();

        // create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        // return response
        $genericFunctions->sendResponse(200, $jwtUser, 'Successfully updated grade.');

    }

}
?>
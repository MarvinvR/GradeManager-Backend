<?php

include_once "./Helpers/GradeCalculation.php";

class Subjects {
    
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

        if(!isset($_POST['name']))
        {
            $genericFunctions->sendError(400, "Please send a valid request.");
        }

        $name = $_POST['name'];

        $sqlAddSubject = "INSERT INTO subjects(uid, name) VALUES( ? , ? );";
        $stmtAddSubject = $dbConn->prepare($sqlAddSubject);
        $stmtAddSubject->bind_param("is", $user->uid, $name);
        
        if (!$stmtAddSubject->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $stmtAddSubject->close();

        // create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        $genericFunctions->sendResponse(200, $jwtUser, 'Successfully created subject "'.$name.'".');

    }

    public function getAll() {
        $user = $this->user;
        $dbConn = $this->dbConn;
        $genericFunctions = $this->genericFunctions;

        if(!($user->uid))
        {
            $genericFunctions->sendError(401, "You are not signed in.");
        }

		if(!isset($_POST["semesterid"])) {
            $genericFunctions->sendError(400, "Please send a valid request.");
		}
		
		$semesterid = $_POST["semesterid"];
		
        $sqlGetSubjects = "SELECT id, name FROM subjects WHERE uid = ?";
        $stmtGetSubjects = $dbConn->prepare($sqlGetSubjects);
        $stmtGetSubjects->bind_param("i", $user->uid);
        
        if (!$stmtGetSubjects->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultGetSubjects = $stmtGetSubjects->get_result();

        if($resultGetSubjects->num_rows < 1)
        {
            $genericFunctions->sendError(404, "No subjects found.");
        }

        $resultGetSubjects = $resultGetSubjects->fetch_all();

        $gradeCalculation = new GradeCalculation();

		for($i = 0; $i < count($resultGetSubjects); $i++) {
			$resultGetSubjects[$i][2] = $gradeCalculation->getSubjectGrade($user->uid, $semesterid, $resultGetSubjects[$i][0]);
		}
		
		$output = $resultGetSubjects;
		
        $stmtGetSubjects->close();

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

        $sqlVerifyOwner = "SELECT uid FROM subjects WHERE id = ? ";
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

        $sqlDeleteSubject = "DELETE FROM subjects WHERE id = ? ";
        $stmtDeleteSubject = $dbConn->prepare($sqlDeleteSubject);
        $stmtDeleteSubject->bind_param("i", $id);
        
        if (!$stmtDeleteSubject->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $stmtDeleteSubject->close();

        
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

        if(!isset($_POST['id']) || !(isset($_POST['name'])))
        {
            $genericFunctions->sendError(400, "Please send a valid request.");
        }

        $id = $_POST['id'];
        $name = $_POST['name'];

        $sqlVerifyOwner = "SELECT uid FROM subjects WHERE id = ? ";
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

        $sqlUpdateSubject = "UPDATE subjects SET name = ? WHERE id = ? ";
        $stmtUpdateSubject = $dbConn->prepare($sqlUpdateSubject);
        $stmtUpdateSubject->bind_param("si", $name, $id);
        
        if (!$stmtUpdateSubject->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $stmtUpdateSubject->close();

        // create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        // return response
        $genericFunctions->sendResponse(200, $jwtUser, 'Successfully updated subject.');

    }

}
?>
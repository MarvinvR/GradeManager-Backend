<?php

include_once "./Helpers/GradeCalculation.php";

class Semesters {
    
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

        $sqlAddSemester = "INSERT INTO semesters(uid, name) VALUES( ? , ? );";
        $stmtAddSemester = $dbConn->prepare($sqlAddSemester);
        $stmtAddSemester->bind_param("is", $user->uid, $name);
        
        if (!$stmtAddSemester->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $stmtAddSemester->close();

        // create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        $genericFunctions->sendResponse(200, $jwtUser, 'Successfully created semester "'.$name.'".');

    }

    public function getAll() {
        $user = $this->user;
        $dbConn = $this->dbConn;
        $genericFunctions = $this->genericFunctions;

        if(!($user->uid))
        {
            $genericFunctions->sendError(401, "You are not signed in.");
        }

        $sqlGetSemesters = "SELECT id, name FROM semesters WHERE uid = ? ";
        $stmtGetSemesters = $dbConn->prepare($sqlGetSemesters);
        $stmtGetSemesters->bind_param("i", $user->uid);
        
        if (!$stmtGetSemesters->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultGetSemesters = $stmtGetSemesters->get_result();

        if($resultGetSemesters->num_rows < 1)
        {
            $genericFunctions->sendError(404, "No semesters found.");
        }

        $resultGetSemesters = $resultGetSemesters->fetch_all();

        $gradeCalculation = new GradeCalculation();

        for ($i=0; $i < count($resultGetSemesters); $i++) 
        { 
            $resultGetSemesters[$i][2] = $gradeCalculation->getSemesterGrade($resultGetSemesters[$i][0], $user->uid);
        }

        $output = $resultGetSemesters;

        $stmtGetSemesters->close();

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

        $semesterId = $_POST['id'];

        $sqlVerifySemesterOwner = "SELECT uid FROM semesters WHERE id = ? ";
        $stmtVerifySemesterOwner = $dbConn->prepare($sqlVerifySemesterOwner);
        $stmtVerifySemesterOwner->bind_param("i", $semesterId);

        if (!$stmtVerifySemesterOwner->execute())
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultVerifySemesterOwner = $stmtVerifySemesterOwner->get_result();

        if ($resultVerifySemesterOwner->num_rows < 1)
        {
            $genericFunctions->sendError(400, "Semester does not exist.");
        }

        $resultVerifySemesterOwner = $resultVerifySemesterOwner->fetch_all()[0];

        if ($resultVerifySemesterOwner[0] != $user->uid) 
        {
            $genericFunctions->sendError(400, "Semester does not exist.");
        }

        $stmtVerifySemesterOwner->close();

        $sqlDeleteSemester = "DELETE FROM semesters WHERE id = ? ";
        $stmtDeleteSemester = $dbConn->prepare($sqlDeleteSemester);
        $stmtDeleteSemester->bind_param("i", $semesterId);
        
        if (!$stmtDeleteSemester->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $stmtDeleteSemester->close();

        
        // create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        // return response
        $genericFunctions->sendResponse(200, $jwtUser, 'Successfully deleted semester.');

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

        $semesterId = $_POST['id'];
        $semesterName = $_POST['name'];
        
        $sqlVerifySemesterOwner = "SELECT uid FROM semesters WHERE id = ? ";
        $stmtVerifySemesterOwner = $dbConn->prepare($sqlVerifySemesterOwner);
        $stmtVerifySemesterOwner->bind_param("i", $semesterId);

        if (!$stmtVerifySemesterOwner->execute())
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultVerifySemesterOwner = $stmtVerifySemesterOwner->get_result();

        if ($resultVerifySemesterOwner->num_rows < 1)
        {
            $genericFunctions->sendError(400, "Semester does not exist.");
        }

        $resultVerifySemesterOwner = $resultVerifySemesterOwner->fetch_all()[0];

        if ($resultVerifySemesterOwner[0] != $user->uid) 
        {
            $genericFunctions->sendError(400, "Semester does not exist.");
        }

        $stmtVerifySemesterOwner->close();

        $sqlUpdateSemester = "UPDATE semesters SET name = ? WHERE id = ? ";
        $stmtUpdateSemester = $dbConn->prepare($sqlUpdateSemester);
        $stmtUpdateSemester->bind_param("si", $semesterName, $semesterId);
        
        if (!$stmtUpdateSemester->execute()) 
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $stmtUpdateSemester->close();

        // create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        // return response
        $genericFunctions->sendResponse(200, $jwtUser, 'Successfully updated semester.');

    }

}

?>
<?php

class GradeCalculation {

    private $dbConn;
    private $genericFunctions;
    private $user;

    public function __construct() {
        $this->dbConn = $GLOBALS['dbConn'];
        $this->genericFunctions = $GLOBALS['genericFunctions'];
        $this->user = $GLOBALS['user'];
    }

    public function getSemesterGrade($semesterid, $userid) {
        $user = $this->user;
        $dbConn = $this->dbConn;
        $genericFunctions = $this->genericFunctions;

        if (!is_numeric($userid))
        {
            $genericFunctions->sendError(400, "Please send a valid request.");
        }

        if (!is_numeric($semesterid))
        {
            $genericFunctions->sendError(400, "Please send a valid request.");
        }

        $sqlSelectSubjects = "SELECT id FROM subjects WHERE uid = ?";
        $stmtSelectSubjects = $dbConn->prepare($sqlSelectSubjects);
        $stmtSelectSubjects->bind_param("i", $userid);

        if (!$stmtSelectSubjects->execute())
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultSelectSubjects = $stmtSelectSubjects->get_result();

        if ($resultSelectSubjects->num_rows < 1) {
            return "-";
        }

        $resultSelectSubjects = $resultSelectSubjects->fetch_all();

        $subjects = array();

        foreach ($resultSelectSubjects as $subject) {
            array_push($subjects, $this->getSubjectGrade($userid, $semesterid, $subject[0]));
        }

        $semesterGrade = 0;
        $counter = 0;

        foreach ($subjects as $subject) {
            if ($subject != "-") {
                $semesterGrade += $subject;
                $counter++;
            }
        }

		if($counter > 0) {
			return round($semesterGrade / $counter, 2);
		} else {
            return "-";
        }
        


    }

    public function getSubjectGrade($userid, $semesterid, $subjectid) {
        $user = $this->user;
        $dbConn = $this->dbConn;
        $genericFunctions = $this->genericFunctions;

        if (!is_numeric($userid))
        {
            $genericFunctions->sendError(400, "Please send a valid request.");
        }

        if (!is_numeric($semesterid))
        {
            $genericFunctions->sendError(400, "Please send a valid request.");
        }

        if (!is_numeric($subjectid))
        {
            $genericFunctions->sendError(400, "Please send a valid request.");
        }

        $sqlSelectGrades = "SELECT grade FROM grades WHERE uid = ? AND subjectid = ? AND semesterid = ? ";
        $stmtSelectGrades = $dbConn->prepare($sqlSelectGrades);
        $stmtSelectGrades->bind_param("iii", $userid, $subjectid, $semesterid);

        if (!$stmtSelectGrades->execute())
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultSelectGrades = $stmtSelectGrades->get_result();

        if ($resultSelectGrades->num_rows < 1) {
            return "-";
        }

        $resultSelectGrades = $resultSelectGrades->fetch_all();

        $subjectGrade = 0;
        $counter = 0;

        foreach ($resultSelectGrades as $grade) {
            $subjectGrade += $grade[0];
            $counter++;
        }

		if($counter > 0) {
			return round(($subjectGrade / $counter) * 2) / 2;
		} else {
			return "-";
		}

    }

}

?>
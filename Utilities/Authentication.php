<?php

class Authentication {

    private $dbConn;
    private $genericFunctions;

    public function __construct() {
        $this->dbConn = $GLOBALS['dbConn'];
        $this->genericFunctions = $GLOBALS['genericFunctions'];
    }

    public function signup() {
        $dbConn = $this->dbConn;
        $genericFunctions = $this->genericFunctions;

        //   Check if variables were set correctly.

        if(!isset($_POST['email']) || !isset($_POST['name']) || !isset($_POST['password']) || !isset($_POST['confirmpassword']))
        {
            $genericFunctions->sendError(400, "Please enter valid information.");
        }

        $email = $_POST['email'];
        $name = $_POST['name'];
        $password = $_POST['password'];
        $confirmpassword = $_POST['confirmpassword'];


        //   Check if email address is already in use

        $sqlCheckEmail = "SELECT id FROM users WHERE email = ?;";
        $stmtCheckEmail = $dbConn->prepare($sqlCheckEmail);
        $stmtCheckEmail->bind_param("s", $email);

        if(!$stmtCheckEmail->execute())
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultCheckEmail = $stmtCheckEmail->get_result();

        if($resultCheckEmail->num_rows > 0)
        {
            $genericFunctions->sendError(400, "Email address already in use.");
        }

        $stmtCheckEmail->close();


        //  Check if passwords match

        if($password != $confirmpassword)
        {
            $genericFunctions->sendError(400, "Password does not match.");
        }


        //  Check if email address is valid

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            $genericFunctions->sendError(400, "Please enter a valid email address.");
        }


        //  Hash password

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);


        //  Insert new user into database

        $sqlSignup = "INSERT INTO users(email, name, password) VALUES( ? , ? , ? )";
        $stmtSignup = $dbConn->prepare($sqlSignup);
        $stmtSignup->bind_param("sss", $email, $name, $hashedPassword);

        if(!$stmtSignup->execute())
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $stmtSignup->close();


        // Get id from database

        $sqlId = "SELECT id FROM users WHERE email = ? ";
        $stmtId = $dbConn->prepare($sqlId);
        $stmtId->bind_param("s", $email);

        if(!$stmtId->execute())
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultId = $stmtId->get_result();
        $stmtId->close();

        if ($resultId->num_rows < 1) {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $id = $resultId->fetch_all()[0][0];

        // Create user
        $user = new User($id, $email, $name);

        // Create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        // Return jwt
        $genericFunctions->sendResponse(200, $jwtUser, "Successfully signed up.");
    }

    public function login() {
        $dbConn = $this->dbConn;
        $genericFunctions = $this->genericFunctions;

        if(!isset($_POST['email']) || !isset($_POST['password']))
        {
            $genericFunctions->sendError(400, "Please enter valid information.");
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        $sqlGetInformation = "SELECT * FROM users WHERE email = ?;";
        $stmtGetInformation = $dbConn->prepare($sqlGetInformation);
        $stmtGetInformation->bind_param("s", $email);

        if(!$stmtGetInformation->execute())
        {
            $genericFunctions->sendError(500, "Internal server error, please try again later.");
        }

        $resultCheckEmail = $stmtGetInformation->get_result();

        if($resultCheckEmail->num_rows < 1)
        {
            $genericFunctions->sendError(400, "Invalid E-Mail address or password.");
        }

        $resultCheckEmail = $resultCheckEmail->fetch_all()[0];
        $stmtGetInformation->close();

        if (!password_verify($password, $resultCheckEmail[3])) 
        {
            $genericFunctions->sendError(400, "Invalid E-Mail address or password.");
        }

        $id = $resultCheckEmail[0];
        $email = $resultCheckEmail[1];
        $name = $resultCheckEmail[2];

        // create user
        $user = new User($id, $email, $name);

        // create jwt 
        $jwtHelper = new JWTHelper();
        $jwtUser = $jwtHelper->createJWT($user);

        // return jwt
        $genericFunctions->sendResponse(200, $jwtUser, "Successfully signed in.");

    }

}

?>
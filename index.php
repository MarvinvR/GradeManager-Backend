<?php

require 'vendor/autoload.php';
require "Helpers/GenericFunctions.php";
require "Model/User.php";
require "Helpers/JWTHelper.php";
require "DatabaseConnection/DatabaseConnection.php";
use \Firebase\JWT\JWT;

$genericFunctions = new GenericFunctions();

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");
error_reporting(E_ALL);
ini_set("display_errors", 1);

if(!isset($_GET["type"]) || !isset($_GET["method"]))
{
    $genericFunctions->sendError(400, "Please send a valid request.");
}

$type = $_GET['type'];
$method = $_GET['method'];
$user = new User(0, "", "");
$databaseConnection = new DatabaseConnection();
$dbConn = $databaseConnection->establishConnection();

if (file_exists("Utilities/".$type.".php"))
{

    require_once "Utilities/".$type.".php";

    if (method_exists($type, $method))
    {

        if (isset($_POST['token']) && $_POST['token'] != "")
        {

            $token = $_POST['token'];

            $jwtHelper = new JWTHelper();
            $jwtContent = $jwtHelper->verifyJWT($token);

            $uId = $jwtContent->user->uid;

            if ($uId)
            {
                $sqlUser = "SELECT id, name, email FROM users WHERE id = ?";
                $stmtUser = $dbConn->prepare($sqlUser);
                $stmtUser->bind_param("s", $uId);

                if (!$stmtUser->execute())
                {
                    $genericFunctions->sendError(500, "Internal server error, please try again later.");
                }

                $resultUser = $stmtUser->get_result();

                if ($resultUser->num_rows > 0)
                {
                    $resultUser = $resultUser->fetch_all()[0];

                    $uId = $resultUser[0];
                    $name = $resultUser[1];
                    $email = $resultUser[2];

                    $user = new User($uId, $name, $email);

                }

            }

        }

        $requestedClass = new $type();
        $requestedClass->$method();

    } else {
        $genericFunctions->sendError(400, "Requested method does not exist.");
    }
} else {
    $genericFunctions->sendError(400, "Requested method does not exist.");
}

?>
<?php

class DatabaseConnection {

    public function __construct(){

    }

    public function establishConnection() {

        require 'DatabaseConfig.php';
        
        $dbConn = new mysqli(DB_HOST, DB_USER, DB_PW, DB_NAME);
        
        if ($dbConn->connect_error) {
	            die("Database Error: " . $dbConn->connect_error);
        }
        
        mysqli_set_charset($dbConn, "utf8");

        return $dbConn;

    }

}


?>
<?php

    class GenericFunctions {

        public function __construct() {

        }

        public function sendResponse($status, $token, $payload) {

            $response = array();
            $response["status"] = $status;
            $response["token"] = $token;
            $response["payload"] = $payload;

            header('Content-Type: application/json');
            echo json_encode($response);
            exit();

        }

        public function sendError($status, $errormessage) {
            $this->sendResponse($status, "", $errormessage);
        }

    }


?>

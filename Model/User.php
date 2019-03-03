<?php

class User {

    public $uid;
    public $name;
    public $email;

    function __construct($uid, $name, $email) {
        $this->uid = $uid;
        $this->name = $name;
        $this->email = $email;
    }

}

?>
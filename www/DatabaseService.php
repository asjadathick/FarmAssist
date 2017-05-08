<?php

//fake credentials
const DB_HOST = 'xxx';
const DB_USER = 'xxx';
const DB_PASS = 'xxx';
const DB_NAME = 'xxx';

class DatabaseService{

    var $conn;

    function __construct(){

        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

    }

    function searchQuery($queryString){
        return $this->conn->query($queryString);
    }

    function closeDB(){
        $this->conn->close();
    }

}

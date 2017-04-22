<?php

const DB_HOST = 'iot.csosz45qwa0w.ap-southeast-2.rds.amazonaws.com';
const DB_USER = 'root';
const DB_PASS = 'testavocado';
const DB_NAME = 'analytics';

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
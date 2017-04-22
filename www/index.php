<?php

require_once 'DatabaseService.php';

$db = new DatabaseService();

$result = $db->searchQuery("SELECT * FROM datasheet");

if($result != null){
    var_dump($result);
}else{
    echo "FAILED";
}

$db->closeDB();

echo "Hello test deployment!";

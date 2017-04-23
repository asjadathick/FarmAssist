<?php
/*
 * crophealth/run.php
 * athick@asjad.io
 * Writes daily health score for crop to table
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$crophealthconfig = array(
    "name" => "crophealth"
);

require_once "../../DatabaseService.php";

$db = new DatabaseService();

$result = $db->searchQuery("SELECT runtimestamp from analyticrunhistory WHERE analytic = 'crophealth' ORDER BY runtimestamp DESC LIMIT 1;");

$sensorData = array();

if($result != null){
    $lastruntimestamp = $result->fetch_assoc();
    $result = $db->searchQuery("SELECT id from cropcycle WHERE status = 'DEPLOYED'");

    while($cropcycles = $result->fetch_assoc()){
        $cropcycle = $cropcycles['id'];
        $qry = "SELECT * FROM sensordata WHERE timestamp >'" . $lastruntimestamp['runtimestamp'] . "' AND cropcycleid = '" . $cropcycle . "'";
        $result = $db->searchQuery($qry);
        while ($data = $result->fetch_assoc()){
            $sensorData[] = $data;
        }
    }
}else{
    echo "FAILED";
}

print_r($sensorData);

//TODO:get crop datasheet


$db->closeDB();



echo "Crop health analytic successfully ran.";

<?php
/*  Author : Paul Ang
 *  Purpose: Check when is the best time to water the crop
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../../DatabaseService.php";

$cropwaterconfig = array(
    "name" => "cropwater",
    "appid" => "f13a9e8d9727fd90e3c1f26f5cb29cb3",
    "appurl"=> "api.openweathermap.org/data/2.5/forecast?" //full api url = api.openweathermap.org/data/2.5/forecast?lat={lat}&lon={lon}
);

//db query
$db = new DatabaseService();

$result = $db->searchQuery("SELECT runtimestamp from analyticrunhistory WHERE analytic = 'crophealth' ORDER BY runtimestamp DESC LIMIT 1;");

$sensorData = array();
$coordinates = array();

//get sensor data with humidity in it
if($result != null){
    $lastruntimestamp = $result->fetch_assoc();
    $result = $db->searchQuery("SELECT id,zoneid from cropcycle WHERE status = 'DEPLOYED'");

    while($cropcycles = $result->fetch_assoc()){            //for each crop cycle
        $cropcycle = $cropcycles['id'];
        $qry = "SELECT * FROM sensordata WHERE timestamp >'" . $lastruntimestamp['runtimestamp'] . "' AND cropcycleid = '" . $cropcycle . "'";
        $result = $db->searchQuery($qry);
        while ($data = $result->fetch_assoc()){
            $sensorData[] = $data;
        }

        $qry = "SELECT coordinates FROM zone WHERE id ='" . $cropcycles['zoneid'] . "'";
        $result = $db->searchQuery($qry);

        $coordinates[] = $result->fetch_assoc()['coordinates'];
    }
}else{
    echo "FAILED";
}


//get weather data from weather api



$db->closeDB();

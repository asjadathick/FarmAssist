<?php
/*  Author : Paul Ang
 *  Purpose: Check when is the best time to water the crop
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../../DatabaseService.php";
require_once "../forecast.io.php";

$cropwaterconfig = array(
    "name" => "cropwater",
    "apikey" => "4194b3a620328a02715fa5d0015d07f0"
);

//db query
$db = new DatabaseService();

$result = $db->searchQuery("SELECT id,zoneid from cropcycle WHERE status = 'DEPLOYED'");

while($cropcycles = $result->fetch_assoc()){    //for each crop cycle (main loop) run analytics for each crop cycle

    $coordinates = "";
    $cropcycle = $cropcycles['id'];

    //----------get the latest sensor data------------------
    $sensorDataQry = "SELECT * FROM sensordata WHERE cropcycleid = '" . $cropcycle . "' ORDER BY id DESC LIMIT 1";
    $sensorDataResult = $db->searchQuery($sensorDataQry);

    $sensorData = $sensorDataResult->fetch_object();

    //-----------get coordinates----------------
    $coordinatesQry = "SELECT coordinates FROM zone WHERE id ='" . $cropcycles['zoneid'] . "'";
    $coordinatesQryRsult = $db->searchQuery($coordinatesQry);

    $coordinates = $coordinatesQryRsult->fetch_object()->coordinates;
    $coordinatesSplit = explode(',',$coordinates);

    //------------get weather data from weather api---------------
    $forecast = new ForecastIO($cropwaterconfig['apikey']);

//    $todayCondition = $forecast->getForecastToday($coordinatesSplit[0],$coordinatesSplit[1]);
    $todayCondition = $forecast->getForecastToday('5.40','100.32');

    echo count($todayCondition);

    foreach($todayCondition as $cond){
        echo $cond->getSummary() . ' ' . $cond->getApparentTemperature() . ' ';
        echo $cond->getTime('d-m-y H:i:s') . ': ' . $cond->getPrecipitationProbability() . "\n";
    }

    //-------------- analytics -----------------------------------

}



//print_r($sensorData);



$db->closeDB();

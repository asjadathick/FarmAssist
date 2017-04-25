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
$summaryMsg="";
$bestTimeTemperature=0;
$lowestSummary;

$result = $db->searchQuery("SELECT id,zoneid,cropid from cropcycle WHERE status = 'DEPLOYED'");

while($cropcycles = $result->fetch_assoc()){    //for each crop cycle (main loop) run analytics for each crop cycle
    //variables
    $coordinates = "";
    $cropcycle = $cropcycles['id'];
    $now = new DateTime();

    //----------get the latest sensor data------------------
    $sensorDataResult = $db->searchQuery("SELECT * FROM sensordata WHERE cropcycleid = '" . $cropcycle . "' ORDER BY id DESC LIMIT 1");
    $sensorData = $sensorDataResult->fetch_object();
   $moistureSensor = $sensorData->moisture;         //needed moisture reading from sensor

    //-------------- get datasheet -----------------------------------
    $datasheetData = $db->searchQuery("SELECT * FROM datasheet WHERE cropid ='" . $cropcycles['cropid'] . "'");
    $datasheetObj = $datasheetData->fetch_object();
    $bestTime = DateTime::createFromFormat('H:i:s',$datasheetObj->timeToWater);

    $tmp = json_decode($datasheetObj->moisture,true);
    $tmp = $tmp['values'];
    $bestMoisture = $tmp[4];
    $badMoisture = $tmp[2];
    $bestMoistureArr = explode(':',$bestMoisture);  //needed best optimum moisture
    $badMoistureArr = explode(':', $badMoisture);

    //-----------get coordinates----------------
    $coordinatesQryRsult = $db->searchQuery("SELECT coordinates FROM zone WHERE id ='" . $cropcycles['zoneid'] . "'");

    $coordinates = $coordinatesQryRsult->fetch_object()->coordinates;
    $coordinatesSplit = explode(',',$coordinates);      //needed coordinates

    //------------get weather data from weather api---------------
    $forecast = new ForecastIO($cropwaterconfig['apikey']);

//    $todayCondition = $forecast->getForecastToday($coordinatesSplit[0],$coordinatesSplit[1]);
    $todayCondition = $forecast->getForecastToday('5.40','100.32');
   // print_r($todayCondition);

    $lowestTime = $todayCondition[0]->getTime('H:i:s');
    $lowestTemp = (double)$todayCondition[0]->getTemperature();
    $targetTime = new DateTime;
    $targetTime = $targetTime->setTime(20,00);      //check till that temperature
    $lowestIndex  = 0;
    $rainIndex = 0;


    foreach($todayCondition as $key=>$cond){          //check lowest temperature and high

        $tmpDateTime = DateTime::createFromFormat('H:i:s',$cond->getTime('H:i:s'));

        if($bestTime == $tmpDateTime){              //best time

            if($cond->getPrecipitationProbability() > 0.5){
                $rainIndex = $key;
            }
            $bestTimeTemperature = $cond->getTemperature();
        }

        if($tmpDateTime < $targetTime) {

            $tmpTemp = (double)$cond->getTemperature();

            if ($tmpTemp < $lowestTemp) {
                $lowestTemp = $tmpTemp;
                $lowestTime = $cond->getTime('H:i:s');
                $lowestIndex = $key;
                $lowestSummary = $cond->getSummary();
            }
        }
    }

    if($rainIndex == $lowestIndex){ //delay raining time
        $summaryMsg .= " It will " . $lowestSummary ." during ". $bestTime->format('H:i') . " today. Check back on the moisture reading later after the rain stops.";

    }else{

        if((double)$bestTimeTemperature > 30) {  //too hot during best time
            $summaryMsg .= " It will be quite hot during ". $bestTime->format('H:i') . " so it is better to water at " . $lowestTime . " The temperature will be only " . $lowestTemp;
        }else{

            $summaryMsg .= " It is fine to water the plan during  " . $bestTime->format('H:i');
        }
    }
    $qins;
    //----- analytics ------
    if($moistureSensor < $badMoistureArr[1] ){     //critical
         $summaryMsg = "Water now. Your crop needs more moisture!";
        $qins = "INSERT INTO cropwaterresults VALUES (NULL, '" . $summaryMsg ."', NOW(), ". $cropcycle . ")";
    }else{      //run analytics to suggest best time to water
        $qins = "INSERT INTO cropwaterresults VALUES (NULL, " . $summaryMsg .", NOW(), '". $cropcycle . "')";
    }
        $res = $db->searchQuery($qins);
}


echo("Analytics ran successfully!");



$db->closeDB();

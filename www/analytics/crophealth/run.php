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

function matchValueToDatasheet($datasheet, $value){
    $dsvals = (array) $datasheet->values;
    foreach($dsvals as $key => $range){
        $ar = explode(':', $range);
        $low = $ar[0];
        $high = $ar[1];
        if($value >= $low && $value <= $high){
            return (int) $key;
        }
    }
    return 0;
}

$result = $db->searchQuery("SELECT runtimestamp from analyticrunhistory WHERE analytic = 'crophealth' ORDER BY runtimestamp DESC LIMIT 1;");

$sensorData = array();

if($result != null){
    $lastruntimestamp = $result->fetch_assoc();
    $result = $db->searchQuery("SELECT id, cropid from cropcycle WHERE status = 'DEPLOYED'");

    while($cropcycles = $result->fetch_assoc()){
        echo "got crop cycle <br>";
        $cropcycle = $cropcycles['id'];
        $crop = $cropcycles['cropid'];
        $qry = "SELECT * FROM sensordata WHERE CONVERT_TZ(timestamp, 'Australia/Sydney','UTC') >'" . $lastruntimestamp['runtimestamp'] . "' AND cropcycleid = '" . $cropcycle . "'";
        echo $qry;
        $qry1 = "INSERT INTO analyticrunhistory VALUES(NULL, 'crophealth', NOW(),'ran');";
        $result = $db->searchQuery($qry);
        $resup = $db->searchQuery($qry1);
        $dtcnt = 0;
        while ($data = $result->fetch_assoc()){
            $sensorData[] = $data;
            $dtcnt++;
        }
        $result1 = $db->searchQuery("SELECT * FROM datasheet WHERE cropid ='" . $crop . "'");
        $result1 = $result1->fetch_assoc();

        $phData = json_decode($result1['ph']);
        $moistureData = json_decode($result1['moisture']);
        $temperatureData = json_decode($result1['temperature']);
        $humidityData = json_decode($result1['humidity']);
        $pressureData = json_decode($result1['pressure']);

        $scoreAvg = 0;
        $dcnt = 0;

        foreach ($sensorData as $data){
            //TODO:get crop health for each row
            $score = 0;
            $score += matchValueToDatasheet($phData, $data['ph']) * $phData->weight ;
            $score += matchValueToDatasheet($moistureData, $data['moisture']) * $moistureData->weight;
            $score += matchValueToDatasheet($temperatureData, $data['temperature']) * $temperatureData->weight;
            $score += matchValueToDatasheet($humidityData, $data['humidity']) * $humidityData->weight;
            $score += matchValueToDatasheet($pressureData, $data['pressure']) * $pressureData->weight;

            echo "Score: " . $score . "<br>";
            $scoreAvg += $score;
            $dcnt++;
        }
        if($dcnt != 0){
            $scoreAvg /= $dcnt;
            //insert result
            $qins = "INSERT INTO crophealthresults VALUES (NULL, " . $cropcycle .", NOW(), '". $scoreAvg . "')";
            $res = $db->searchQuery($qins);
        }

        //TODO: aggregate crop health and produce final number for the period of time

    }
}else{
    echo "FAILED";
}


$db->closeDB();



echo "Crop health analytic successfully ran.";

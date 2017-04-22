<?php
/*
 * crophealth/run.php
 * athick@asjad.io
 * Writes daily health score for crop to table
 */


$crophealthconfig = array(
    "name" => "crophealth"
);

//TODO: get last run time for analytic

//TODO: analyse only data recieved after last run time (filtered for each deployed sensor on it's crop type

echo "Crop health analytic successfully ran.";

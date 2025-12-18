<?php
 require_once 'config.php';
 
 if (!defined('CSV_DUMP_PASSWORD')) {
     die('CSV dump password not configured in config.php');
 }
 
 $password = CSV_DUMP_PASSWORD;

    if (empty($_COOKIE['password']) || $_COOKIE['password'] !== $password) {
        // Password not set or incorrect. Send to login.php.
        header('Location: login.php');
        exit;
    }
 $conn = getDatabaseConnection();
 $export = "SELECT `id`,`attacker`,`ship`,`ship2`,`defender`,`logid`,`tactics`,`hit_accuracy`,`maneuver`,`weaponry`,`engineering`,`evasion`,`ECM`,`ECCM`,`crits`,`critsm`,`hits`,`hitsm`,`shots`,`shotsm`,`jams`,`d_crits`,`d_critsm`,`d_hits`,`d_hitsm`,`d_shots`,`d_shotsm`,`d_jams`,`crits2`,`critsm2`,`hits2`,`hitsm2`,`shots2`,`shotsm2`,`jams2`,`submission_time` FROM `combat_data`";
 $exportresults = $conn->query($export);
 $temp = fopen("db.csv", 'w');
 $titles = array("id","attacker","ship","ship2","defender","logid","tactics","hit_accuracy","maneuver","weaponry","engineering","evasion","ECM","ECCM","crits","critsm","hits","hitsm","shots","shotsm","jams","d_crits","d_critsm","d_hits","d_hitsm","d_shots","d_shotsm","d_jams","crits2","critsm2","hits2","hitsm2","shots2","shotsm2","jams2","submission_time");
 fputcsv($temp,$titles,",");
 while($row = $exportresults -> fetch_row()) {
     fputcsv($temp, $row, ",");
 }
fclose($temp);
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="combatDatabase.csv";');
    readfile("db.csv");

?>

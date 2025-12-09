<?php
 /* Your password */
    $password = 'SecureCombatStudy';

    if (empty($_COOKIE['password']) || $_COOKIE['password'] !== $password) {
        // Password not set or incorrect. Send to login.php.
        header('Location: login.php');
        exit;
    }



 require_once 'config.php';
 $conn = getDatabaseConnection();
 $export = "SELECT `tactics`, `hit_accuracy`,`maneuver`,`weaponry`,`engineering`,`evasion`,`ECM`,`ECCM`,`shots`,`jams`, `hits`, `crits`, `shotsm`,`hitsm`,`critsm`, `ship`,`defender`, `d_shots`, `d_jams`, `d_hits`, `d_crits`,`d_shotsm`,`d_hitsm`,`d_critsm`, `log_time`  FROM `combat_data` WHERE 1";
 $exportresults = $conn->query($export);
 $delimiter = ",";
 $temp = fopen("db.csv", 'w');
 $titles = array("tactics","hit_accuracy","maneuver","weaponry","engineering","evasion","ECM","ECCM","shots","jams","hits","crits","missiles fired","missiles hit","missiles crit","ship","defender","d_shots","d_jams","d_hits","d_crits","d_missiles fired","d_missiles hit","d_missiles crit","log_time");
 fputcsv($temp,$titles,",");
 while($row = $exportresults -> fetch_row()) {
   fputcsv($temp, $row, ",");
    }
fclose($temp);
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="combatDatabase.csv";');
    readfile("db.csv");

?>

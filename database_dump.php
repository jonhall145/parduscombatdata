<table>
<tr><th>id</th><th>attacker</th><th>ship</th><th>ship2</th><th>defender</th><th>logid</th><th>tactics</th><th>hit_accuracy</th><th>maneuver</th><th>weaponry</th><th>engineering</th><th>evasion</th><th>ECM</th><th>ECCM</th><th>crits</th><th>critsm</th><th>hits</th><th>hitsm</th><th>shots</th><th>shotsm</th><th>jams</th><th>d_crits</th><th>d_critsm</th><th>d_hits</th><th>d_hitsm</th><th>d_shots</th><th>d_shotsm</th><th>d_jams</th><th>crits2</th><th>critsm2</th><th>hits2</th><th>hitsm2</th><th>shots2</th><th>shotsm2</th><th>jams2</th><th>submission_time</th></tr>
<?php
 require_once 'config.php';
 
 if (!defined('CSV_DUMP_PASSWORD')) {
     die('CSV dump password not configured. Please define CSV_DUMP_PASSWORD in config.php');
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
 
 while($content = $exportresults->fetch_row()) {
    echo "<tr>";
        foreach ($content as $key=>$value) {
            echo "<td>" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "</td>";
        }
        echo "</tr>";
 }
?>

</table>

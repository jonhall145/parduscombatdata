<table>
<tr><th>tactics</th><th>hit_accuracy</th><th>maneuver</th><th>weaponry</th><th>engineering</th><th>evasion</th><th>ECM</th><th>ECCM</th>
<th>shots</th><th>jams</th><th>hits</th><th>crits</th><th>missiles fired</th><th>missiles hit</th><th>missiles crit</th>
<th>ship</th><th>defender</th>
<th>d_shots</th><th>d_jams</th><th>d_hits</th><th>d_crits</th><th>d_missiles fired</th><th>d_missiles hit</th><th>d_missiles crit</th></tr>
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
 $export = "SELECT `tactics`, `hit_accuracy`,`maneuver`,`weaponry`,`engineering`,`evasion`,`ECM`,`ECCM`,`shots`,`jams`, `hits`, `crits`, `shotsm`,`hitsm`,`critsm`, `ship`,`defender`, `d_shots`, `d_jams`, `d_hits`, `d_crits`,`d_shotsm`,`d_hitsm`,`d_critsm`  FROM `combat_data` WHERE 1";
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
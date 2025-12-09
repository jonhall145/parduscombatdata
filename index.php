<?php
 require_once 'config.php';
 
 //tactics study
 echo "<h2>Tactics study</h2>";
 echo "<h3>Attacker stats</h3>";
 echo "<p>Early results suggest the chance a hit is a crit is as simple as 1/5 x tactics, with one caveat - it's very hard (almost impossible!) to crit with mining lasers.  Therefore, data shows this pattern against almost all enemies except Euryales and Elings where mining lasers are most often required.  The data so far is shown below.  Tactics is recorded at the end of the combat, and rounded down.</p>";
 echo "<p>It seems likely that defender stats are used in some way in the calculation.  This will be easier to evaluate as I collect more data.</p>";
 $conn = getDatabaseConnection();
 $tacticsquery = "SELECT FLOOR(`tactics`), sum(`hits`), SUM(`crits`), `defender` FROM `combat_data` WHERE 1 GROUP BY FLOOR(`tactics`), `defender`";
 $tacticsresults = $conn->query($tacticsquery);
 $npcsquery = "SELECT DISTINCT `defender` FROM `combat_data` WHERE 1 ORDER BY `defender`";
 $npcresults = $conn->query($npcsquery);
 ?>

<html>
 <head>
  <title>Combat data</title>
  <link rel="stylesheet" href="styles.css">
  <script>
function fetch_results() {
    var xhttp = new XMLHttpRequest();
    xhttp.onload = function() {
        if(this.status == 200 && this.readyState == 4) {
            document.getElementById("query response").innerHTML = this.responseText;
        }
    }
    xhttp.open("POST", "query_handler.php", true);
    let formData = new FormData(document.forms.custom_query);
    xhttp.send(formData);
    
}
</script>
 </head>
 <body>
     
<h1>Asd&#39;s combat analysis</h1>

<p>An infamous player was shot down in 3 rounds and the log showed 11 out of 12 hits against him were critical. What are the chances?</p>

<p>This site was born because we couldn&#39;t decide whether critical hit rates were dependant on the defending player&#39;s skills. The long term vision is to improve general understanding of combat related formulae by building a database of combat results alongside stats</p>

<p>If you would like to contribute, please install <a href="skillstat.user.js">this script</a>. To use it, just tap &quot;y&quot; twice on the combat result screen after each round of fighting, or press the button that pops up. Health warning: I will be able to see all of your skills!</p>

<p>Contact Asdwolf (Orion) or Ranker Five (Artemis) if you&#39;d like to discuss it.</p>

<p>Select your query parameters below</p>

<form action="/query_handler.php" method = "POST" name="custom_query">
    <label for="focusskill">Skill to analyse by:</label><select name = "focusskill">
        <option value ="tactics">Tactics</option>
        <option value ="hit_accuracy">Hit accuracy</option>
        <option value = "maneuver">Maneuver</option>
        <option value = "weaponry">Weaponry</option>
        <option value = "engineering">Engineering</option>
    </select><br><br>
    <p>Filter combat logs by stats</p>
    <table>
        <tr><th>Skill</th><th>Min</th><th></th><th>Max</th></tr>
        <tr><td><label for="tac_min">Tactics</label></td><td><input type="number" name="tac_min" value = 0 style="width: 5em;"></td><td></td>
        <td><input type="number" name="tac_max" value = 200 style="width: 5em;"></td></tr>
        <tr><td><label for="ha_min">Hit accuracy</label></td><td><input type="number" name="ha_min" value = 0 style="width: 5em;"></td><td></td>
        <td><input type="number" name="ha_max" value = 200 style="width: 5em;"></td></tr>
        <tr><td><label for="man_min">Maneuver</label></td><td><input type="number" name="man_min" value = 0 style="width: 5em;"></td><td></td>
        <td><input type="number" name="man_max" value = 200 style="width: 5em;"></td></tr>
        <tr><td><label for="weap_min">Weaponry</label></td><td><input type="number" name="weap_min" value = 0 style="width: 5em;"></td><td></td>
        <td><input type="number" name="weap_max" value = 200 style="width: 5em;"></td></tr>
        <tr><td><label for="eng_min">Engineering</label></td><td><input type="number" name="eng_min" value = 0 style="width: 5em;"></td><td></td>
        <td><input type="number" name="eng_max" value = 200 style="width: 5em;"></td></tr>
    </table>
    <br>
    <label for="opponent">Opponent: </label>
    <select id = "opponent" name="opponent">
        <option>All opponents</option>
    <?php
     while($content = $npcresults->fetch_row())
     {
        foreach ($content as $value) {
            //echo "<script>alert(\"" . $value . "\");</script>";
        echo "<option value =\"" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "</option>";
        }
     }
    ?></select>
    <br><br><input type="button" onclick="fetch_results()" value="Fetch stats" />
</form>

<div id = "query response"></div>




</body>
</html>

 <?php
 echo "<table><tr><th>Tactics</th><th>Hits</th><th>Crits</th><th>Opponent</th></tr>";
 while($content = $tacticsresults->fetch_row())
 {
     echo "<tr>";
    foreach ($content as $key=>$value) {
        echo "<td>" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "</td>";
        }
    echo "</tr>";
 }
 echo "</table>";
 
 echo "<h3>Defender stats</h3>";
 echo "<p>The following tables show hits against the pilot compared to their TAC and ENG.  These are the most skills which I think are most likely to have an impact on critical hits taken.</p>";
 //generate forms: pull various fields from 
 $engquery = "SELECT FLOOR(`engineering`), SUM(`d_hits`), SUM(`d_crits`), `defender` FROM `combat_data` WHERE 1 GROUP BY FLOOR(`engineering`), `defender`";
 $engresults = $conn->query($engquery);
 

 echo "<table><tr><th>Engineering</th><th>Hits</th><th>Crits</th><th>Opponent</th></tr>";
 while($content = $engresults->fetch_row())
 {
     echo "<tr>";
    foreach ($content as $key=>$value) {
        echo "<td>" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "</td>";
        }
    echo "</tr>";
 }
  echo "</table><br>";
  
 $tacdefquery = "SELECT FLOOR(`tactics`), sum(`d_hits`), SUM(`d_crits`), `defender` FROM `combat_data` WHERE 1 GROUP BY FLOOR(`tactics`), `defender`";
 $tacdefresults = $conn->query($tacdefquery);
 

 echo "<table><tr><th>Tactics</th><th>Hits</th><th>Crits</th><th>Opponent</th></tr>";
 while($content = $tacdefresults->fetch_row())
 {
     echo "<tr>";
    foreach ($content as $key=>$value) {
        echo "<td>" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "</td>";
        }
    echo "</tr>";
 }
  echo "</table>";
 
 $conn->close();
?>
     
 </body>
</html>
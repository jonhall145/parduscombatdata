<html>
<body>

Data submitted<br>

</body>
</html>

<?php
$servername = "localhost";
$username = "asdwtbdf_combatstats";
$password = "*.Xswx[QW;v?";
$dbname = "asdwtbdf_parduscombatdata";
$npclist = ["0","Lucidi Squad", "Lucidi Warship", "Lucidi Mothership", "Inexperienced Pirate", "Experienced Pirate", "Hidden Drug Stash", "Lone Smuggler", "Escorted Smuggler", "Slave Trader", "Famous Pirate", "Pirate Captain", "Fuel Tanker", "Biks", "X-993 Squad", "X-993 Battlecruiser", "X-993 Mothership", "Z15 Scout", "Z15 Repair Drone", "Z15 Fighter", "Z15 Spacepad", "Z16 Repair Drone", "Z16 Fighter", "Blue Crystal", "Frost Crystal", "Space Crystal", "Rive Crystal", "Ancient Crystal", "Energy Minnow", "Swarm of Energy Bees", "Energy Sparker", "Verdant Manifestation", "Developed Manifestation", "Ripe Manifestation", "Solar Banshee", "Oblivion Vortex", "Space Maggot", "Bio Scavenger", "Space Clam", "Space Worm", "Space Worm Albino", "Mutated Space Maggot", "Eulerian", "Mutated Space Worm", "Space Snail", "Roidworm Horde", "Sarracenia", "Drosera", "Xalgucennia", "Ceylacennia", "Preywinder", "Gorefangling", "Swarm of Gorefanglings", "Blood Amoeba", "Gorefang", "Nebula Mole", "Dreadscorp", "Medusa", "Medusa Swarmlings", "Mutated Medusa", "Starclaw", "Stheno", "Stheno Swarmlings", "Euryale", "Euryale Swarmlings", "Nebula Serpent", "Asp Hatchlings", "Asp Mother", "Feral Serpent", "Young Space Dragon", "Elder Space Dragon", "Space Dragon Queen", "Space Locust", "Nebula Locust", "Energy Locust", "Locust Hive", "Exocrab", "Wormhole Monster", "Xhole Monster", "Vyrex Larva", "Vyrex Assassin", "Vyrex Stinger", "Vyrex Mutant Mauler", "Vyrex Hatcher", "Ice Beast", "Cyborg Manta", "Infected Creature", "Glowprawn", "Shadow"];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed$ " . $conn->connect_error);
}
echo "Connected successfully <br>";

echo "<br>";

$attacker = $_POST["attacker"] ;
$ship =  $_POST["ship"];
$crits = $_POST["crits"];
$critsm = $_POST["critsm"];
$jams = $_POST["jams"];
$shots = $_POST["shots"];
$shotsm = $_POST["shotsm"];
$crits2 = $_POST["crits2"];
$critsm2 = $_POST["critsm2"];
$jams2 = $_POST["jams2"];
$shots2 = $_POST["shots2"];
$shotsm2 = $_POST["shotsm2"];
$logid = $_POST["logid"];
$defender = $_POST["defender"];
$tac = (float)$_POST["tactics"];
$ha = (float)$_POST["hit_accuracy"];
$man = (float)$_POST["maneuver"];
$weap = (float)$_POST["weaponry"];
$eng = (float)$_POST["engineering"];
$hits = $_POST["hits"];
$hits2 = $_POST["hits2"];
$hitsm = $_POST["hitsm"];
$hitsm2 = $_POST["hitsm2"];
$time = time();
$ship2 =  $_POST["ship2"];


$evasion = isset($_POST['evasion']) ? $_POST['evasion'] : null;
$ecm = isset($_POST['ECM']) ? $_POST['ECM'] : "unknown";
$eccm = isset($_POST['ECCM']) ? $_POST['ECCM'] : "unknown";

$sqlinsert = $conn->prepare("INSERT INTO `combat_data` (`ID`, `attacker`, `ship`, `crits`, `critsm`, `hits`, `jams`, `shots`, `shotsm`, `defender`, `d_crits`, `d_hits`, `d_critsm`, `d_jams`, `d_shots`, `d_shotsm`, `log_id`, `log_time`, `tactics`, `hit_accuracy`, `maneuver`, `weaponry`, `engineering`,`hitsm`,`d_hitsm`,`evasion`,`ECM`,`ECCM`)
VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)");

//test if defender is NPC and if not, switch it around

if (array_search($defender,$npclist)) {
    //$defender as expected - go ahead
    $attacker = "anon"; //anonymise data
  //  error_log("attacker = ". $attacker . "ship = ". $ship . "crits = ". $crits . "critsm = ". $critsm . "hits  = ". $hits  . "jams = ". $jams . "shots = ". $shots . "shotsm = ". $shotsm . "defender = ". $defender . "crits2 = ". $crits2 . "hits2 = ". $hits2 . "critsm2 = ". $critsm2 . "jams2 = ". $jams2 . "shots2 = ". $shots2 . "shotsm2 = ". $shotsm2 . "logid = ". $logid . "time = ". $time . "tac = ". $tac . "ha = ". $ha . "man = ". $man . "weap = ". $weap . "eng = ". $eng . "hitsm = ". $hitsm . "hitsm2 = ". $hitsm2 . "evasion = ". $evasion . "ecm = ". $ecm . "eccm = ". $eccm);
    $sqlinsert->bind_param("ssiiiiiisiiiiiiiidddddiidss", $attacker, $ship, $crits, $critsm, $hits , $jams, $shots, $shotsm, $defender, $crits2,$hits2, $critsm2, $jams2, $shots2, $shotsm2,$logid,$time,$tac,$ha,$man,$weap,$eng,$hitsm,$hitsm2,$evasion,$ecm,$eccm);
    //error_log("Submitted log at " . time());
    ;

} elseif ($shots2 == 0) {
    //we have a retreat hold - do some swapping
    $anon = "anon";
    $sqlinsert->bind_param("ssiiiiiisiiiiiiiidddddiidss", $anon, $ship2, $crits2, $critsm2, $hits2 , $jams2, $shots2, $shotsm2, $attacker, $crits,$hits, $critsm, $jams, $shots, $shotsm,$logid,$time,$tac,$ha,$man,$weap,$eng,$hitsm2,$hitsm,$evasion,$ecm,$eccm);
    
} else {
    //not sure what's happened here - break! and don't load the data.
    die("Bad data");
}




$sqlinsert->execute();
//if ($result = $conn -> query("SELECT * FROM `combat_data` WHERE `ID`=(SELECT max(`ID`) FROM `combat_data`);")) {
//  error_log("Returned rows are: " . $sqlinsert -> error);
  // Free result set
//  $result -> free_result();
//}


$conn->close();

echo "<br>";

?>
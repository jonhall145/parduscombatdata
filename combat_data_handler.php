<html>
<body>

Data submitted<br>

</body>
</html>

<?php
require_once 'config.php';

$npclist = ["0","Lucidi Squad", "Lucidi Warship", "Lucidi Mothership", "Inexperienced Pirate", "Experienced Pirate", "Hidden Drug Stash", "Lone Smuggler", "Escorted Smuggler", "Slave Trader", "Famous Pirate", "Pirate Captain", "Fuel Tanker", "Biks", "X-993 Squad", "X-993 Battlecruiser", "X-993 Mothership", "Z15 Scout", "Z15 Repair Drone", "Z15 Fighter", "Z15 Spacepad", "Z16 Repair Drone", "Z16 Fighter", "Blue Crystal", "Frost Crystal", "Space Crystal", "Rive Crystal", "Ancient Crystal", "Energy Minnow", "Swarm of Energy Bees", "Energy Sparker", "Verdant Manifestation", "Developed Manifestation", "Ripe Manifestation", "Solar Banshee", "Oblivion Vortex", "Space Maggot", "Bio Scavenger", "Space Clam", "Space Worm", "Space Worm Albino", "Mutated Space Maggot", "Eulerian", "Mutated Space Worm", "Space Snail", "Roidworm Horde", "Sarracenia", "Drosera", "Xalgucennia", "Ceylacennia", "Preywinder", "Gorefangling", "Swarm of Gorefanglings", "Blood Amoeba", "Gorefang", "Nebula Mole", "Dreadscorp", "Medusa", "Medusa Swarmlings", "Mutated Medusa", "Starclaw", "Stheno", "Stheno Swarmlings", "Euryale", "Euryale Swarmlings", "Nebula Serpent", "Asp Hatchlings", "Asp Mother", "Feral Serpent", "Young Space Dragon", "Elder Space Dragon", "Space Dragon Queen", "Space Locust", "Nebula Locust", "Energy Locust", "Locust Hive", "Exocrab", "Wormhole Monster", "Xhole Monster", "Vyrex Larva", "Vyrex Assassin", "Vyrex Stinger", "Vyrex Mutant Mauler", "Vyrex Hatcher", "Ice Beast", "Cyborg Manta", "Infected Creature", "Glowprawn", "Shadow"];

// Create connection
$conn = getDatabaseConnection();
echo "Connected successfully <br>";

echo "<br>";

// Validate required POST parameters
$required_params = ["attacker", "ship", "crits", "critsm", "jams", "shots", "shotsm",
                    "crits2", "critsm2", "jams2", "shots2", "shotsm2", "logid", "defender",
                    "tactics", "hit_accuracy", "maneuver", "weaponry", "engineering",
                    "hits", "hits2", "hitsm", "hitsm2", "ship2"];

foreach ($required_params as $param) {
    if (!isset($_POST[$param])) {
        die("Missing required parameter: " . htmlspecialchars($param, ENT_QUOTES, 'UTF-8'));
    }
}

$attacker = $_POST["attacker"];
$ship = $_POST["ship"];
$crits = (int)$_POST["crits"];
$critsm = (int)$_POST["critsm"];
$jams = (int)$_POST["jams"];
$shots = (int)$_POST["shots"];
$shotsm = (int)$_POST["shotsm"];
$crits2 = (int)$_POST["crits2"];
$critsm2 = (int)$_POST["critsm2"];
$jams2 = (int)$_POST["jams2"];
$shots2 = (int)$_POST["shots2"];
$shotsm2 = (int)$_POST["shotsm2"];
$logid = $_POST["logid"];
$defender = $_POST["defender"];
$tac = (float)$_POST["tactics"];
$ha = (float)$_POST["hit_accuracy"];
$man = (float)$_POST["maneuver"];
$weap = (float)$_POST["weaponry"];
$eng = (float)$_POST["engineering"];
$hits = (int)$_POST["hits"];
$hits2 = (int)$_POST["hits2"];
$hitsm = (int)$_POST["hitsm"];
$hitsm2 = (int)$_POST["hitsm2"];
$time = time();
$ship2 = $_POST["ship2"];

$evasion = isset($_POST['evasion']) ? $_POST['evasion'] : null;
$ecm = isset($_POST['ECM']) ? $_POST['ECM'] : "unknown";
$eccm = isset($_POST['ECCM']) ? $_POST['ECCM'] : "unknown";

$sqlinsert = $conn->prepare("INSERT INTO `combat_data` (`ID`, `attacker`, `ship`, `crits`, `critsm`, `hits`, `jams`, `shots`, `shotsm`, `defender`, `d_crits`, `d_hits`, `d_critsm`, `d_jams`, `d_shots`, `d_shotsm`, `log_id`, `log_time`, `tactics`, `hit_accuracy`, `maneuver`, `weaponry`, `engineering`,`hitsm`,`d_hitsm`,`evasion`,`ECM`,`ECCM`)
VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)");

//test if defender is NPC and if not, switch it around

if (array_search($defender, $npclist, true) !== false) {
    //$defender as expected - go ahead
    $attacker = "anon"; //anonymise data
    $sqlinsert->bind_param("ssiiiiiisiiiiiiiidddddiidss", $attacker, $ship, $crits, $critsm, $hits , $jams, $shots, $shotsm, $defender, $crits2,$hits2, $critsm2, $jams2, $shots2, $shotsm2,$logid,$time,$tac,$ha,$man,$weap,$eng,$hitsm,$hitsm2,$evasion,$ecm,$eccm);

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
<html>
<body>

Data submitted<br>

</body>
</html>

<?php
require_once 'config.php';

$npclist = ["0","Lucidi Squad", "Lucidi Warship", "Lucidi Mothership", "Inexperienced Pirate", "Experienced Pirate", "Hidden Drug Stash", "Lone Smuggler", "Escorted Smuggler", "Slave Trader", "Famous Pirate", "Pirate Captain", "Fuel Tanker", "Biks", "X-993 Squad", "X-993 Battlecruiser", "X-993 Mothership", "Z15 Scout", "Z15 Repair Drone", "Z15 Fighter", "Z15 Spacepad", "Z16 Repair Drone", "Z16 Fighter", "Blue Crystal", "Frost Crystal", "Space Crystal", "Rive Crystal", "Ancient Crystal", "Energy Minnow", "Swarm of Energy Bees", "Energy Sparker", "Verdant Manifestation", "Developed Manifestation", "Ripe Manifestation", "Solar Banshee", "Oblivion Vortex", "Space Maggot", "Bio Scavenger", "Space Clam", "Space Worm", "Space Worm Albino", "Mutated Space Maggot", "Eulerian", "Mutated Space Worm", "Space Snail", "Roidworm Horde", "Sarracenia", "Drosera", "Xalgucennia", "Ceylacennia", "Preywinder", "Gorefangling", "Swarm of Gorefanglings", "Blood Amoeba", "Gorefang", "Nebula Mole", "Dreadscorp", "Medusa", "Medusa Swarmlings", "Mutated Medusa", "Starclaw", "Stheno", "Stheno Swarmlings", "Euryale", "Euryale Swarmlings", "Nebula Serpent", "Asp Hatchlings", "Asp Mother", "Feral Serpent", "Young Space Dragon", "Elder Space Dragon", "Space Dragon Queen", "Space Locust", "Nebula Locust", "Energy Locust", "Locust Hive", "Exocrab", "Wormhole Monster", "Xhole Monster", "Vyrex Larva", "Vyrex Assassin", "Vyrex Stinger", "Vyrex Mutant Mauler", "Vyrex Hatcher", "Ice Beast", "Cyborg Manta", "Infected Creature", "Glowprawn", "Shadow"];

//inArray ensures desired behaviour of array seraches in below
//debugged
function inArray($search, $arr): int {
    if (($loc = array_search($search, $arr)) === false){
        return -1;
    } else {
        return $loc;
    }
}


// Create connection
$conn = getDatabaseConnection();
echo "Connected successfully <br>";

echo "<br>";

// Beta testing file - uses hardcoded test data instead of POST parameters
// Uncomment the POST parameter lines below and comment the test data to use real data

// Test data setup
$attacker = "TestPlayer";
$ship = "Test Ship";
$crits = 5;
$critsm = 2;
$hits = 10;
$jams = 1;
$shots = 15;
$shotsm = 3;
$crits2 = 3;
$critsm2 = 1;
$hits2 = 8;
$jams2 = 0;
$shots2 = 12;
$shotsm2 = 2;
$logid = 101010101010;
$defender = "Euryale";
$tac = 1.01;
$ha = 1.01;
$man = 1.01;
$weap = 1.01;
$eng = 1.01;
$hitsm = 2;
$hitsm2 = 1;
$time = time();
$ship2 = "NPC Ship";

// Uncomment below to use real POST data instead of test data
//$attacker = $_POST["attacker"];
//$ship = $_POST["ship"];
//$crits = (int)$_POST["crits"];
//$critsm = (int)$_POST["critsm"];
//$hits = (int)$_POST["hits"];
//$jams = (int)$_POST["jams"];
//$shots = (int)$_POST["shots"];
//$shotsm = (int)$_POST["shotsm"];
//$crits2 = (int)$_POST["crits2"];
//$critsm2 = (int)$_POST["critsm2"];
//$hits2 = (int)$_POST["hits2"];
//$jams2 = (int)$_POST["jams2"];
//$shots2 = (int)$_POST["shots2"];
//$shotsm2 = (int)$_POST["shotsm2"];
//$logid = $_POST["logid"];
//$defender = $_POST["defender"];
//$tac = (float)$_POST["tactics"];
//$ha = (float)$_POST["hit_accuracy"];
//$man = (float)$_POST["maneuver"];
//$weap = (float)$_POST["weaponry"];
//$eng = (float)$_POST["engineering"];
//$hitsm = (int)$_POST["hitsm"];
//$hitsm2 = (int)$_POST["hitsm2"];
//$ship2 = $_POST["ship2"];

$evasion = isset($_POST['evasion']) ? $_POST['evasion'] : null;
$ecm = isset($_POST['ECM']) ? $_POST['ECM'] : "unknown";
$eccm = isset($_POST['ECCM']) ? $_POST['ECCM'] : "unknown";

$sqlinsert = $conn->prepare("INSERT INTO `combat_data` (`id`, `attacker`, `ship`, `crits`, `critsm`, `hits`, `jams`, `shots`, `shotsm`, `defender`, `d_crits`, `d_hits`, `d_critsm`, `d_jams`, `d_shots`, `d_shotsm`, `logid`, `submission_time`, `tactics`, `hit_accuracy`, `maneuver`, `weaponry`, `engineering`,`hitsm`,`d_hitsm`,`evasion`,`ECM`,`ECCM`)
VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)");

//test if defender is NPC and if not, switch it around

if (array_search($defender, $npclist, true) !== false) {
    //$defender as expected - go ahead
    $attacker = "anon";  //anonymise data
    $sqlinsert->bind_param("ssiiiiiisiiiiiiiidddddiidss", $attacker, $ship, $crits, $critsm, $hits , $jams, $shots, $shotsm, $defender, $crits2,$hits2, $critsm2, $jams2, $shots2, $shotsm2,$logid,$time,$tac,$ha,$man,$weap,$eng,$hitsm,$hitsm2,$evasion,$ecm,$eccm);

} elseif ($shots2 == 0) {
    //we have a retreat hold - do some swapping
    $anon = "anon";
    $sqlinsert->bind_param("ssiiiiiisiiiiiiiidddddiidss", $anon, $ship2, $crits2, $critsm2, $hits2 , $jams2, $shots2, $shotsm2, $attacker, $crits,$hits, $critsm, $jams, $shots, $shotsm,$logid,$time,$tac,$ha,$man,$weap,$eng,$hitsm2,$hitsm,$evasion,$ecm,$eccm);
    
} else {
    //not sure what's happened here - break! and don't load the data.
    http_response_code(400);
    die("Bad Request: Invalid combat data - defender is not in NPC list and shots2 is not 0");
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
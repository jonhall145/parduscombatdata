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

//$attacker = $_POST["attacker"] ;
//$ship =  $_POST["ship"];
//$crits = $_POST["crits"];
//$critsm = $_POST["critsm"];
//$jams = $_POST["jams"];
//$shots = $_POST["shots"];
//$shotsm = $_POST["shotsm"];
//$crits2 = $_POST["crits2"];
//$critsm2 = $_POST["critsm2"];
// $jams2 = $_POST["jams2"];
// $shots2 = $_POST["shots2"];
// $shotsm2 = $_POST["shotsm2"];
//spoof logid
// $logid = $_POST["logid"];
 $logid =   101010101010;
 
// $defender = $_POST["defender"];
 //spoof skills
 $tac =     1.01;
 $ha =      1.01;
 $man =     1.01;
 $weap =    1.01;
 $eng =     1.01;
 // real skills below
//  $tac = (float)$_POST["tactics"];
//  $ha = (float)$_POST["hit_accuracy"];
//  $man = (float)$_POST["maneuver"];
//  $weap = (float)$_POST["weaponry"];
//  $eng = (float)$_POST["engineering"];
// $hits = $_POST["hits"];
// $hits2 = $_POST["hits2"];
// $hitsm = $_POST["hitsm"];
// $hitsm2 = $_POST["hitsm2"];
 $time = time();
// $ship2 =  $_POST["ship2"];
// $cr = $_POST["CR"];
// spoof CR sent from script
$cr = "Asdwolf;scorpion_xmas.png;525;362;0;Euryale;euryale.png;2000;1430;540;A;L;32|1;10 MW Mining Laser;MWmin010.png;L;32|1;10 MW Mining Laser;MWmin010.png;L;32|1;10 MW Mining Laser;MWmin010.png;L;32|1;10 MW Mining Laser;MWmin010.png;B;L;90|2;Outer Tentacles;;L;91|3;Inner Tentacles;;L;91|3;Inner Tentacles;;M;m26;Energy Shockwave;M;m26;Energy Shockwave;M;m26;Energy Shockwave;M;m26;Energy Shockwave;R1;S1;S2;R2;S1;S2;m26;29 armor damage;R3;S1;S2;90;14 armor damage (50% efficiency);R4;S1;S2;R5;S1;32;6 shield damage;S2;91;5 armor damage (50% efficiency);R6;S1;S2;R7;S1;S2;R8;S1;S2;R9;S1;S2;R10;S1;32;6 shield damage;S2;91;C6 armor damage (50% efficiency);R11;S1;S2;R12;S1;S2;91;C5 armor damage (50% efficiency);R13;S1;S2;R14;S1;S2;R15;S1;S2;R16;S1;S2;R17;S1;S2;R18;S1;S2;91;5 armor damage (50% efficiency);R19;S1;32;6 shield damage;S2;R20;S1;32;6 shield damage;S2;90;C14 armor damage (50% efficiency);E;525;284;0;F;2000;1430;516;";

$evasion = isset($_POST['evasion']) ? $_POST['evasion'] : null;
$ecm = isset($_POST['ECM']) ? $_POST['ECM'] : "unknown";
$eccm = isset($_POST['ECCM']) ? $_POST['ECCM'] : "unknown";

$sqlinsert = $conn->prepare("INSERT INTO `combat_data` (`ID`, `attacker`, `ship`, `crits`, `critsm`, `hits`, `jams`, `shots`, `shotsm`, `defender`, `d_crits`, `d_hits`, `d_critsm`, `d_jams`, `d_shots`, `d_shotsm`, `log_id`, `log_time`, `tactics`, `hit_accuracy`, `maneuver`, `weaponry`, `engineering`,`hitsm`,`d_hitsm`,`evasion`,`ECM`,`ECCM`)
VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)");

//test if defender is NPC and if not, switch it around

if (array_search($defender,$npclist)) {
    //$defender as expected - go ahead
    // error_log("attacker = ". $attacker . "ship = ". $ship . "crits = ". $crits . "critsm = ". $critsm . "hits  = ". $hits  . "jams = ". $jams . "shots = ". $shots . "shotsm = ". $shotsm . "defender = ". $defender . "crits2 = ". $crits2 . "hits2 = ". $hits2 . "critsm2 = ". $critsm2 . "jams2 = ". $jams2 . "shots2 = ". $shots2 . "shotsm2 = ". $shotsm2 . "logid = ". $logid . "time = ". $time . "tac = ". $tac . "ha = ". $ha . "man = ". $man . "weap = ". $weap . "eng = ". $eng . "hitsm = ". $hitsm . "hitsm2 = ". $hitsm2 . "evasion = ". $evasion . "ecm = ". $ecm . "eccm = ". $eccm);
    $attacker = "anon";  //anonymise data
    $sqlinsert->bind_param("ssiiiiiisiiiiiiiidddddiidss", $attacker, $ship, $crits, $critsm, $hits , $jams, $shots, $shotsm, $defender, $crits2,$hits2, $critsm2, $jams2, $shots2, $shotsm2,$logid,$time,$tac,$ha,$man,$weap,$eng,$hitsm,$hitsm2,$evasion,$ecm,$eccm);
    //error_log("Submitted log at " . time());

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
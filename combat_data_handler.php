<?php
header('Content-Type: application/json');
require_once 'config.php';

$npclist = ["0","Lucidi Squad", "Lucidi Warship", "Lucidi Mothership", "Inexperienced Pirate", "Experienced Pirate", "Hidden Drug Stash", "Lone Smuggler", "Escorted Smuggler", "Slave Trader", "Famous Pirate", "Pirate Captain", "Fuel Tanker", "Biks", "X-993 Squad", "X-993 Battlecruiser", "X-993 Mothership", "Z15 Scout", "Z15 Repair Drone", "Z15 Fighter", "Z15 Spacepad", "Z16 Repair Drone", "Z16 Fighter", "Blue Crystal", "Frost Crystal", "Space Crystal", "Rive Crystal", "Ancient Crystal", "Energy Minnow", "Swarm of Energy Bees", "Energy Sparker", "Verdant Manifestation", "Developed Manifestation", "Ripe Manifestation", "Solar Banshee", "Oblivion Vortex", "Space Maggot", "Bio Scavenger", "Space Clam", "Space Worm", "Space Worm Albino", "Mutated Space Maggot", "Eulerian", "Mutated Space Worm", "Space Snail", "Roidworm Horde", "Sarracenia", "Drosera", "Xalgucennia", "Ceylacennia", "Preywinder", "Gorefangling", "Swarm of Gorefanglings", "Blood Amoeba", "Gorefang", "Nebula Mole", "Dreadscorp", "Medusa", "Medusa Swarmlings", "Mutated Medusa", "Starclaw", "Stheno", "Stheno Swarmlings", "Euryale", "Euryale Swarmlings", "Nebula Serpent", "Asp Hatchlings", "Asp Mother", "Feral Serpent", "Young Space Dragon", "Elder Space Dragon", "Space Dragon Queen", "Space Locust", "Nebula Locust", "Energy Locust", "Locust Hive", "Exocrab", "Wormhole Monster", "Xhole Monster", "Vyrex Larva", "Vyrex Assassin", "Vyrex Stinger", "Vyrex Mutant Mauler", "Vyrex Hatcher", "Ice Beast", "Cyborg Manta", "Infected Creature", "Glowprawn", "Shadow"];
function respond(int $code, string $message, array $extra = []): void {
    http_response_code($code);
    echo json_encode(array_merge(['message' => $message], $extra));
    exit;
}

function require_string(array $source, string $key): string {
    if (!isset($source[$key])) {
        respond(400, "Missing required parameter: {$key}");
    }
    $value = trim((string)$source[$key]);
    if ($value === '') {
        respond(400, "Parameter {$key} cannot be empty");
    }
    return $value;
}

function require_number(array $source, string $key, bool $integer = true, float $min = 0.0, ?float $max = null) {
    if (!isset($source[$key]) || $source[$key] === '') {
        respond(400, "Missing required parameter: {$key}");
    }
    if (!is_numeric($source[$key])) {
        respond(400, "Parameter {$key} must be numeric");
    }
    $num = $integer ? (int)$source[$key] : (float)$source[$key];
    if ($num < $min || ($max !== null && $num > $max)) {
        respond(400, "Parameter {$key} out of allowed range");
    }
    return $num;
}

// Validate and normalize input
$attacker = require_string($_POST, 'attacker');
$ship = require_string($_POST, 'ship');
$ship2 = require_string($_POST, 'ship2');
$defender = require_string($_POST, 'defender');

$crits = require_number($_POST, 'crits');
$critsm = require_number($_POST, 'critsm');
$jams = require_number($_POST, 'jams');
$shots = require_number($_POST, 'shots');
$shotsm = require_number($_POST, 'shotsm');
$crits2 = require_number($_POST, 'crits2');
$critsm2 = require_number($_POST, 'critsm2');
$jams2 = require_number($_POST, 'jams2');
$shots2 = require_number($_POST, 'shots2');
$shotsm2 = require_number($_POST, 'shotsm2');
$hits = require_number($_POST, 'hits');
$hits2 = require_number($_POST, 'hits2');
$hitsm = require_number($_POST, 'hitsm');
$hitsm2 = require_number($_POST, 'hitsm2');

$tac = require_number($_POST, 'tactics', false, 0, 200);
$ha = require_number($_POST, 'hit_accuracy', false, 0, 200);
$man = require_number($_POST, 'maneuver', false, 0, 200);
$weap = require_number($_POST, 'weaponry', false, 0, 200);
$eng = require_number($_POST, 'engineering', false, 0, 200);

$logid = require_string($_POST, 'logid');
$time = time();

// Optional parameters with default values
$evasion = isset($_POST['evasion']) && $_POST['evasion'] !== '' ? (float)$_POST['evasion'] : null;
$ecmRaw = isset($_POST['ECM']) ? trim((string)$_POST['ECM']) : 'unknown';
$ecm = ($ecmRaw === '' || strcasecmp($ecmRaw, 'unknown') === 0) ? null : $ecmRaw;
$eccmRaw = isset($_POST['ECCM']) ? trim((string)$_POST['ECCM']) : 'unknown';
$eccm = ($eccmRaw === '' || strcasecmp($eccmRaw, 'unknown') === 0) ? null : $eccmRaw;

$conn = getDatabaseConnection();

$sqlinsert = $conn->prepare("INSERT INTO `combat_data` (`id`, `attacker`, `ship`, `crits`, `critsm`, `hits`, `jams`, `shots`, `shotsm`, `defender`, `d_crits`, `d_hits`, `d_critsm`, `d_jams`, `d_shots`, `d_shotsm`, `logid`, `submission_time`, `tactics`, `hit_accuracy`, `maneuver`, `weaponry`, `engineering`,`hitsm`,`d_hitsm`,`evasion`,`ECM`,`ECCM`)
VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)");

if (!$sqlinsert) {
    $conn->close();
    respond(500, 'Failed to prepare statement');
}

if (array_search($defender, $npclist, true) !== false) {
    // Defender as expected - anonymise attacker
    $attackerAnon = 'anon';
    $sqlinsert->bind_param("ssiiiiiisiiiiiiiidddddiidss", $attackerAnon, $ship, $crits, $critsm, $hits , $jams, $shots, $shotsm, $defender, $crits2,$hits2, $critsm2, $jams2, $shots2, $shotsm2,$logid,$time,$tac,$ha,$man,$weap,$eng,$hitsm,$hitsm2,$evasion,$ecm,$eccm);
} elseif ($shots2 == 0) {
    // Retreat hold - swap roles
    $attackerAnon = 'anon';
    $sqlinsert->bind_param("ssiiiiiisiiiiiiiidddddiidss", $attackerAnon, $ship2, $crits2, $critsm2, $hits2 , $jams2, $shots2, $shotsm2, $attacker, $crits,$hits, $critsm, $jams, $shots, $shotsm,$logid,$time,$tac,$ha,$man,$weap,$eng,$hitsm2,$hitsm,$evasion,$ecm,$eccm);
} else {
    $sqlinsert->close();
    $conn->close();
    respond(400, 'Invalid combat data - defender is not in NPC list and shots2 is not 0');
}

if (!$sqlinsert->execute()) {
    $errno = $sqlinsert->errno;
    $error = $sqlinsert->error;
    $sqlinsert->close();
    $conn->close();
    if ($errno === 1062) {
        respond(409, 'Duplicate combat log id', ['logid' => $logid]);
    }
    error_log("combat_data insert failed: {$errno} {$error}");
    respond(500, 'Failed to save combat data');
}

$insertId = $sqlinsert->insert_id;
$sqlinsert->close();
$conn->close();

respond(200, 'ok', ['id' => $insertId]);
?>
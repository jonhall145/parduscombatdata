<?php
require_once 'config.php';

    $tac_min = isset($_POST["tac_min"]) ? (float)$_POST["tac_min"] : 0;
    $tac_max = isset($_POST["tac_max"]) ? (float)$_POST["tac_max"] : 200;
    $ha_min = isset($_POST["ha_min"]) ? (float)$_POST["ha_min"] : 0;
    $ha_max = isset($_POST["ha_max"]) ? (float)$_POST["ha_max"] : 200;
    $man_min = isset($_POST["man_min"]) ? (float)$_POST["man_min"] : 0;
    $man_max = isset($_POST["man_max"]) ? (float)$_POST["man_max"] : 200;
    $weap_min = isset($_POST["weap_min"]) ? (float)$_POST["weap_min"] : 0;
    $weap_max = isset($_POST["weap_max"]) ? (float)$_POST["weap_max"] : 200;
    $eng_min = isset($_POST["eng_min"]) ? (float)$_POST["eng_min"] : 0;
    $eng_max = isset($_POST["eng_max"]) ? (float)$_POST["eng_max"] : 200;
    $opponent = isset($_POST["opponent"]) ? $_POST["opponent"] : "All opponents";
    $skill = isset($_POST["focusskill"]) ? $_POST["focusskill"] : "tactics";
    $y_axis = isset($_POST["y_axis"]) ? $_POST["y_axis"] : "Attacker hit rate";
    // Define valid skills with explicit column name mapping for security
    $validskills = [
        "tactics" => "tactics",
        "hit_accuracy" => "hit_accuracy", 
        "maneuver" => "maneuver",
        "weaponry" => "weaponry",
        "engineering" => "engineering"
    ];
    $validopponents = array("0","All opponents");
    
    $conn = getDatabaseConnection();
    $npcsquery = "SELECT DISTINCT `defender` FROM `combat_data` WHERE 1 ORDER BY `defender`";
    $npcresults = $conn->query($npcsquery);
    $npcresultscopy = $npcresults;

    $npclist = ["0","Lucidi Squad", "Lucidi Warship", "Lucidi Mothership", "Inexperienced Pirate", "Experienced Pirate", "Hidden Drug Stash", "Lone Smuggler", "Escorted Smuggler", "Slave Trader", "Famous Pirate", "Pirate Captain", "Fuel Tanker", "Biks", "X-993 Squad", "X-993 Battlecruiser", "X-993 Mothership", "Z15 Scout", "Z15 Repair Drone", "Z15 Fighter", "Z15 Spacepad", "Z16 Repair Drone", "Z16 Fighter", "Blue Crystal", "Frost Crystal", "Space Crystal", "Rive Crystal", "Ancient Crystal", "Energy Minnow", "Swarm of Energy Bees", "Energy Sparker", "Verdant Manifestation", "Developed Manifestation", "Ripe Manifestation", "Solar Banshee", "Oblivion Vortex", "Space Maggot", "Bio Scavenger", "Space Clam", "Space Worm", "Space Worm Albino", "Mutated Space Maggot", "Eulerian", "Mutated Space Worm", "Space Snail", "Roidworm Horde", "Sarracenia", "Drosera", "Xalgucennia", "Ceylacennia", "Preywinder", "Gorefangling", "Swarm of Gorefanglings", "Blood Amoeba", "Gorefang", "Nebula Mole", "Dreadscorp", "Medusa", "Medusa Swarmlings", "Mutated Medusa", "Starclaw", "Stheno", "Stheno Swarmlings", "Euryale", "Euryale Swarmlings", "Nebula Serpent", "Asp Hatchlings", "Asp Mother", "Feral Serpent", "Young Space Dragon", "Elder Space Dragon", "Space Dragon Queen", "Space Locust", "Nebula Locust", "Energy Locust", "Locust Hive", "Exocrab", "Wormhole Monster", "Xhole Monster", "Vyrex Larva", "Vyrex Assassin", "Vyrex Stinger", "Vyrex Mutant Mauler", "Vyrex Hatcher", "Ice Beast", "Cyborg Manta", "Infected Creature", "Glowprawn", "Shadow"];

    //$validopponents = mysqli_fetch_array($npcresults);
    //$validopponents[] = "All opponents";
    while($row = $npcresults->fetch_row())
    {
        if (array_search($row[0],$npclist,TRUE)){
        $validopponents[] = $row[0];
        }
    }

    
    // Validate skill parameter to prevent SQL injection - use whitelist mapping
    if (!isset($validskills[$skill])) {
        die("Invalid skill parameter");
    }
    // Get the validated column name from the whitelist
    $skillColumn = $validskills[$skill];
    
    if (array_search($opponent, $validopponents,TRUE) && isset($validskills[$skill]) && is_numeric($tac_min) && is_numeric($tac_max) && is_numeric($ha_min) && is_numeric($ha_max) && is_numeric($man_min) && is_numeric($man_max) && is_numeric($weap_min) && is_numeric($weap_max) && is_numeric($eng_min) && is_numeric($eng_max))
    { //numbers are numeric, Opponent is acceptable, Skill is acceptable
        if ($opponent == "All opponents") {
            $defenderselect = "%";
            } else {
                $defenderselect = $opponent;
            }
        

        // Use validated skill column from whitelist - safe from SQL injection
        $sqlcustomquery = $conn->prepare("
        SELECT FLOOR(`" . $skillColumn . "`), SUM(`shots`), SUM(`hits`),SUM(`crits`),SUM(`d_shots`),SUM(`d_hits`),SUM(`d_crits`),`defender`
        FROM `combat_data`
        WHERE
        `tactics` BETWEEN ? AND ?
        AND `hit_accuracy` BETWEEN ? AND ?
        AND `maneuver` BETWEEN ? AND ?
        AND `weaponry` BETWEEN ? AND ?
        AND `engineering` BETWEEN ? AND ?
        AND `defender` LIKE ?
        GROUP BY FLOOR(`" . $skillColumn . "`), `defender`;");
        
        $sqlcustomquery->bind_param("dddddddddds",$tac_min,$tac_max,$ha_min,$ha_max,$man_min,$man_max,$weap_min,$weap_max,$eng_min,$eng_max,$defenderselect);
        $sqlcustomquery->execute();
        $customresult = $sqlcustomquery->get_result();
        echo "<div id=\"htmlContent\">";
        echo "<table><tr><th></th><th colspan=\"3\">Attacker stats</th><th colspan=\"3\">Defender stats</th></tr><tr><th>". htmlspecialchars(ucfirst($skill), ENT_QUOTES, 'UTF-8') . "</th><th>Shots</th><th>Hits</th><th>Crits</th><th>Shots</th><th>Hits</th><th>Crits</th><th>Opponent</th></tr>";
        while($content = $customresult->fetch_assoc()) {
            if(array_search($content["defender"],$npclist,TRUE)) {
            echo "<tr>";
            foreach ($content as $key=>$value) {
                echo "<td>" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "</td>";
            }
            echo "</tr>";
            }
             
        }
        $customresult -> data_seek(0);
        $skillkey = "FLOOR(`".$skillColumn."`)";
        echo "</table></div>";
        //Now script to draw chart
        if ($opponent == "All opponents")
        {
            echo "<script id=\"scriptContent\"></script>
                <div id=\"chart_div\"></div>";
        } else {
        switch ($y_axis)
        {
            case "Attacker hit rate":
                $key1 = "SUM(`hits`)";
                $key2 = "SUM(`shots`)";
                break;
            case "Attacker crit rate":
                $key1 = "SUM(`crits`)";
                $key2 = "SUM(`hits`)";
                break;
            case "Defender hit rate":
                $key1 = "SUM(`d_hits`)";
                $key2 = "SUM(`d_shots`)";
                break;
            case "Defender crit rate":
                $key1 = "SUM(`d_crits`)";
                $key2 = "SUM(`d_hits`)";
                break;
            default:
                $key1 = "SUM(`hits`)";
                $key2 = "SUM(`shots`)";
                break;
        }
        $chartscript =
            "<script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\" id=\"scriptContent\">
            
                google.charts.load('current', {
                    packages: ['corechart', 'line']
                });
                function drawChart() {
                var chartdata = new google.visualization.DataTable();
                chartdata.addColumn('number', 'x');";
        if ($opponent == "All opponents") {
            // add row for each NPC - add later
        } else {
            //echo "entering else <br>";
            
            $chartscript .= "
                chartdata.addColumn('number', '" . addslashes($opponent) ."' );";
            $chartscript .= "
                chartdata.addColumn({id:'lb', type:'number', role:'interval'});
                chartdata.addColumn({id:'ub', type:'number', role:'interval'});";
                //var_dump($customresult->fetch_row());
                echo "<br>";
                while($content = $customresult->fetch_assoc()) {
                    //var_dump($content);
                    $top = $content[$key1];
                    $bottom = $content[$key2];
                    if($bottom != 0)
                    {
                        $rate = $top / $bottom;
                        $delta = 1.960*sqrt($top*($bottom-$top)/($bottom*$bottom*$bottom));
                        if($top == 0) {
                            $ratelb = 0;
                            $rateub = 1-pow(0.05,1/$bottom);
                        } elseif ($top==$bottom) {
                            $ratelb = pow(0.05,1/$top);
                            $rateub = 1;
                        
                        } else {
                        $ratelb = max([0,$rate - $delta]);
                        $rateub = min([1,$rate + $delta]);
                        }
                    } else {
                        $rate = 0;
                        $ratelb = 0;
                        $rateub = 0;
                    }
                    $chartscript.= "
                chartdata.addRows([";
                    $chartscript.= ("[" . $content[$skillkey] .", " . $rate .", " . $ratelb . ", " . $rateub . "]");
                    $chartscript.="]);";
                }
            //add a single row
        }
        $chartoptions = "
                var options = {
                'width':600,
                intervals: { style: 'area' },
                    hAxis: {
                        format: 'decimal',
                        title: '".addslashes($skill)."',
                        gridlines: {interval: 1}
                    },
                    vAxis: {
                        format: 'percent',
                        title: '".addslashes($y_axis)."',
                        minValue: 0
                        
                    },
                };";
        $chartscript .= $chartoptions;
        $chartscript .= "
                var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
                chart.draw(chartdata, options);
                }
                google.charts.setOnLoadCallback(drawChart)
            </script>
        <div id=\"chart_div\"></div>";
        echo $chartscript;
        }
    } else { //something went wrong
    echo "Validation failed<br>";
        
    }
?>
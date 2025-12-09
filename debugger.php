<html>
<body>
<?php
echo 'PHP version: ' . phpversion();

//timer funciton to follow


//functions to use in interpreter

function inArray($search, $arr): int {
    if (($loc = array_search($search, $arr)) === false){
        return -1;
    } else {
        return $loc;
    }
}

function extractID($id): string {
    if (($pos = strpos($id,"|")) === false) {
        return $id;
    } else {
        return substr($id,0,$pos);
    }
}

function extractRate($id) {
    if (($pos = strpos($id,"|")) === false) {
        return 0;
    } else {
        return substr($id,$pos+1,1);
    }
}

//testing functions

function test_extractRate(){
echo "<br>Testing extractRate<br>";
$teststring1 = "ab|2|defg";
$teststring2 = "abcdefg";
echo "extractRate($teststring1) = " . extractRate($teststring1) . "<br>";
echo "extractRate($teststring2) = " . extractRate($teststring2) . "<br>";
echo "<br>";
echo "Testing extractRate complete<br><br><br>";
}

function test_inArray() {
    $testarr = array("a","b","test");
    echo "Testing inArray <br>";
    echo "inArray(a, $testarr) = " . inArray("a",$testarr) ."<br>";
    echo "inArray(b, $testarr) = " . inArray("b",$testarr) ."<br>";
    echo "inArray(test, $testarr) = " . inArray("test",$testarr) ."<br>";
    echo "inArray(c, $testarr) = " . inArray("c",$testarr) . "<br>";
    echo "inArray tests complete <br><br><br>";
}

function test_extractID(){
echo "Testing extractID<br>";
$teststring1 = "ab|c|defg";
$teststring2 = "abcdefg";
echo "extractID($teststring1) = " . extractID($teststring1) . "<br>";
echo "extractID($teststring2) = " . extractID($teststring2) . "<br>";
echo "<br>";
echo "Testing extractID complete<br><br><br>";
}

function print_hard_inputs($logid, $tac, $ha, $man, $weap, $eng, $time, $cr){
echo "logid = $logid, tac = $tac, ha = $ha, man = $man, weap = $weap, eng = $eng, time = $time";
echo "<br><br>";
echo "cr = $cr";
echo "<br>";
echo "Manaul data inputs complete<br><br><br>";
}

function test_interpreter($cr){
    echo "<br>Testing interpreter<br>";
    echo "<br>Input cr is:<br>$cr<br>";
    [$stats1, $stats2] = interpreter($cr, true);
    echo "<br>Stats1 is <br>";
    var_dump($stats1);
    echo "<br>Stats2 is <br>";
    var_dump($stats2);
}

$logid =   101010101010;
$tac =     1.01;
$ha =      2.01;
$man =     3.01;
$weap =    4.01;
$eng =     5.01;
$time = time();
$cr = "Asdwolf;scorpion_xmas.png;525;362;0;Euryale;euryale.png;2000;1430;540;A;L;32|1;10 MW Mining Laser;MWmin010.png;L;32|1;10 MW Mining Laser;MWmin010.png;L;32|1;10 MW Mining Laser;MWmin010.png;L;32|1;10 MW Mining Laser;MWmin010.png;B;L;90|2;Outer Tentacles;;L;91|3;Inner Tentacles;;L;91|3;Inner Tentacles;;M;m26;Energy Shockwave;M;m26;Energy Shockwave;M;m26;Energy Shockwave;M;m26;Energy Shockwave;R1;S1;S2;R2;S1;S2;m26;29 armor damage;R3;S1;S2;90;14 armor damage (50% efficiency);R4;S1;S2;R5;S1;32;6 shield damage;S2;91;5 armor damage (50% efficiency);R6;S1;S2;R7;S1;S2;R8;S1;S2;R9;S1;S2;R10;S1;32;6 shield damage;S2;91;C6 armor damage (50% efficiency);R11;S1;S2;R12;S1;S2;91;C5 armor damage (50% efficiency);R13;S1;S2;R14;S1;S2;R15;S1;S2;R16;S1;S2;R17;S1;S2;R18;S1;S2;91;5 armor damage (50% efficiency);R19;S1;32;6 shield damage;S2;R20;S1;32;6 shield damage;S2;90;C14 armor damage (50% efficiency);E;525;284;0;F;2000;1430;516;";

//Actual tests performed below - comment to turn tests on or off
//test_inArray();
//print_hard_inputs($logid, $tac, $ha, $man, $weap, $eng, $time, $cr);
//test_extractID();
//test_extractRate();
test_interpreter($cr);

function interpreter(
    //Does the bulk of the work - turns the dense combat string into useful information
    //To add later: collect weapon / opponent / damage info
    $cr, //the cr string with all info
    $debug = false //set to true to get output
    ){
    [$stats1, $stats2] = [[],[]]; //initialise variables to return.  stats1 = attacker.  stats2 = defender
    // initialise variables to use later
    $wtype1 = []; 
    $wtype2 = [];
    $windices1 = [];
    $windices2 = [];
    $p = 0;
    $wstats1 = [];
    $wstats2 = [];
    $damages;
    $shots;
    $stats1 = [];
    $stats2 = [];
    $det_stats1 = "";
    $det_stats2 = "";
    
    $cr = explode(";",$cr);
    
    $shipname1 = $cr[0];
    $shipimage1 = $cr[1];
    $hull1a = $cr[2];
    $armor1a = $cr[3];
    $shield1a = $cr[4];
    $shipname2 = $cr[5];
    $shipimage2 = $cr[6];
    $hull2a = $cr[7];
    $armor2a = $cr[8];
    $shield2a = $cr[9];
    $weapons1 = "";
    $weapons2 = "";
    $w1 = [];
    $w2 = [];
    $hits1 = [];
    $hits2 = [];
    $raid = [];
    $lindices1 = [];
    $lindices2 = [];
    $cr_size = sizeof($cr);
    
    $l = 0;
    $i = 11;
    while ($cr[$i] != "B") {
        if($cr[$i] == "L" or $cr[$i] == "M") {
            $id = extractId($cr[$i+1]);
            $lindices1[$l] = $id;
            if ($debug) {
                echo "<br>cr[$i] = $cr[$i]";
                echo "<br>lindices1[$l] = $lindices1[$l]";
            }
            $l++;
            $weapons1 = $weapons1 .  ("<img src='//static.pardus.at/img/xolarix/equipment/");
            $weapons1 = $weapons1 .  $cr[($i+3)];
            $weapons1 = $weapons1 . ("' alt=''> ");
            $weapons1 = $weapons1 . ($cr[($i+2)]);
            $weapons1 = $weapons1 . ("<br>");
        }
        $i++;
        if ($debug) {
            echo "<br>i set to $i";
        }
    }
    
    if ($debug) {
        echo "<br>weapons1 = $weapons1<br>";
        echo "<br>shipname1 = $shipname1<br>";
        echo "<br>shipimage1 = $shipimage1<br>";
        echo "<br>hulla = $hull1a<br>";
        echo "<br>armor1a = $armor1a<br>";
        echo "<br>shield1a = $shield1a<br>";
        echo "<br>shipname2 = $shipname2<br>";
        echo "<br>shipimage2 = $shipimage2<br>";
        echo "<br>hull2a = $hull2a<br>";    
        echo "<br>armor2a = $armor2a";
        echo "<br>shield2a = $shield2a<br>";
        echo "<br> cr_size = $cr_size";
    }
    
    return  [$stats1, $stats2]; // return array of results
}






?>
</body>
</html>
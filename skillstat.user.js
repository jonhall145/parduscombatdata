// ==UserScript==
// @name        Skill and stat analyser testing version
// @namespace   asdwolf.com
// @author      Asdwolf (Orion) Ranker Five (Artemis), and a strong assist from Miche
// @version	    0.71
// @description Manually store key stats from combat logs and combat skills at time of log.
// @include		http*://*.pardus.at/ship2opponent_combat.php*
// @updateURL 	https://www.asdwolf.com/skillstat.user.js
// @downloadURL https://www.asdwolf.com/skillstat.user.js
// @grant       GM_xmlhttpRequest
// ==/UserScript==



'use strict';

var combatLinkList = document.getElementsByTagName("a");
var str = document.location.hostname;
var universe = str.substring(0, str.indexOf("."));
var skillsLink = "https://"+universe+".pardus.at/overview_stats.php";
var shipLink = "https://"+universe+".pardus.at/overview_ship.php";
var skillTags = ["tactics_actual","hit_actual","maneuver_actual","weaponry_actual","engineering_actual"];
var skillsToSend = [0,0,0,0,0];
var shipInfo = []
shipInfo.evasion = "not set";
shipInfo.ECM = "none";
shipInfo.ECCM = "none";
var serverURL = "https://asdwolf.com/combat_data_handler.php";
var combatToSubmit = [];
var cr_interpreted = [];

function parseXHTTPResponseText(data) {
    //Turns text into HTML
    var httpRT = data.replace(/^.*\>(?=<body\b)/, '');
    var fragment = document.createRange().createContextualFragment(httpRT);
    return fragment;
}

function append_input(form, name, value) {
    var input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value;
    form.appendChild(input);
}

function inArray(search, arr)
{
    for (var z = 0; z < arr.length; z++) {
        if (arr[z] == search) {
            return z;
        }
    }
    return -1;
}

function extractId(id)
{
    if (id.indexOf('|') > 0) {
        id = id.substring(0, id.indexOf('|'));
    }
    return id;
}

function extractRate(id)
{
    if (id.indexOf('|') > 0) {
        return id.substr(id.indexOf('|')+1);
    } else {
        return 0;
    }
}

function interpretcr (cr) {

    var wtype1 = {};
    var wtype2 = {};
    var windices1 = [];
    var windices2 = [];
    var p = 0;
    var wstats1 = {};
    var wstats2 = {};
    var damages;
    var shots;
    var stats1 = {};
    var stats2 = {};
    var det_stats1 = "";
    var det_stats2 = "";
    var shipname1 = cr[0];
    var shipimage1 = cr[1];
    var hull1a = cr[2];
    var armor1a = cr[3];
    var shield1a = cr[4];
    var shipname2 = cr[5];
    var shipimage2 = cr[6];
    var hull2a = cr[7];
    var armor2a = cr[8];
    var shield2a = cr[9];
    var weapons1 = "";
    var weapons2 = "";
    var w1 = {};
    var w2 = {};
    var hits1 = [];
    var hits2 = [];
    var raid = [];
    var lindices1 = [];
    var lindices2 = [];
    cr = cr.split(";");
    var cr_size = cr.length;

    var l = 0;
    var i = 11;
    while (cr[i] != 'B') {
        if (cr[i] == 'L' || cr[i] == 'M') {
            var id = extractId(cr[i+1]);
            lindices1[l] = id;
            l++;
            weapons1 += "<img src='//static.pardus.at/img/xolarix/equipment/" + cr[i+3] + "' alt=''> " + cr[i+2] + "<br>";
            w1[id] = "<img src='//static.pardus.at/img/xolarix/equipment/" + cr[i+3] + "' title='" + cr[i+2] + "' alt='" + cr[i+2] + "'>";
            if (inArray(id, windices1) == -1) {
                wtype1[id] = {};
                wtype1[id].type = cr[i];
                wtype1[id].rate = extractRate(cr[i+1]);
                windices1[p] = id;
                p++;
                wtype1[id].shots = 1;
            } else {
                wtype1[id].shots++;
            }
        }
        i++;
    }
    p = 0;
    l = 0;
    while (cr[i] != "R1" && cr[i] != 'E') {
        if (cr[i] == 'L' || cr[i] == 'M') {
            id = extractId(cr[i+1]);
            lindices2[l] = id;
            l++;
            weapons2 += cr[i+2] + "<br>";
            w2[id] = cr[i+2];
            if (inArray(id, windices2) == -1) {
                wtype2[id] = {};
                wtype2[id].type = cr[i];
                wtype2[id].rate = extractRate(cr[i+1]);
                windices2[p] = id;
                p++;
                wtype2[id].shots = 1;
            } else {
                wtype2[id].shots++;
            }
        }
        i++;
    }
    for (p = 0; p < windices1.length; p++) {
        wstats1[windices1[p]] = {};
        wstats1[windices1[p]].hits = 0;
        wstats1[windices1[p]].crits = 0;
        wstats1[windices1[p]].jams = 0;
        wstats1[windices1[p]].hull = 0;
        wstats1[windices1[p]].armor = 0;
        wstats1[windices1[p]].shield = 0;
    }
    for (p = 0; p < windices2.length; p++) {
        wstats2[windices2[p]] = {};
        wstats2[windices2[p]].hits = 0;
        wstats2[windices2[p]].crits = 0;
        wstats2[windices2[p]].jams = 0;
        wstats2[windices2[p]].hull = 0;
        wstats2[windices2[p]].armor = 0;
        wstats2[windices2[p]].shield = 0;
    }
    var round = 2;
    while (cr[i] != 'E' && cr[i] != 'L') {
        i = i + 2;
        hits1[round-2] = "";
        while (cr[i] != "S2" && cr[i] != 'E' && cr[i] != 'L') {
            var hitStr = "Hit";
            var dmgStr = cr[i+1];
            if (dmgStr == 'J') {
                wstats1[cr[i]].jams++;
                i = i + 2;
                continue;
            }
            if (dmgStr.charAt(0) == 'C') {
                hitStr = "<b>Critical Hit</b>";
                dmgStr = dmgStr.substr(1);
                wstats1[cr[i]].crits++;
            }
            hits1[round-2] += "<font color='green'>" + hitStr + " with " + w1[cr[i]] + " for: " + dmgStr + "</font><br>";
            wstats1[cr[i]].hits++;
            // damages = extractDamage(dmgStr);
            // wstats1[cr[i]].hull += parseInt(damages.hull);
            // wstats1[cr[i]].armor += parseInt(damages.armor);
            // wstats1[cr[i]].shield += parseInt(damages.shield);
            i = i + 2;
        }
        if (cr[i] == 'E' || cr[i] == 'L') {
            break;
        }
        i++;
        hits2[round-2] = "";
        while (cr[i] != 'R' + round && cr[i] != 'E' && cr[i] != 'L') {
            hitStr = "Hit";
            dmgStr = cr[i+1];
            if (dmgStr == 'J') {
                wstats2[cr[i]].jams++;
                i = i + 2;
                continue;
            }
            if (dmgStr.charAt(0) == 'C') {
                hitStr = "<b>Critical Hit</b>";
                dmgStr = dmgStr.substr(1);
                wstats2[cr[i]].crits++;
            }
            hits2[round-2] += "<font color='green'>" + hitStr + " with " + w2[cr[i]] + " for: " + dmgStr + "</font><br>";
            wstats2[cr[i]].hits++;
            //damages = extractDamage(dmgStr);
            //wstats2[cr[i]].hull += parseInt(damages.hull);
            //wstats2[cr[i]].armor += parseInt(damages.armor);
            //wstats2[cr[i]].shield += parseInt(damages.shield);
            i = i + 2;
        }
        round++;
    }
    if (cr[i] == 'L') {
        var who = cr[i+1];
        var last_hit_windex = cr[i+2];
        var last_hit_wrate = cr[i+3];
        i = i + 4;
    } else {
        who = -1;
    }
    i++;
    var hull1b = cr[i];
    var ch_hull1 = hull1a - hull1b;
    if (ch_hull1 > 0) {
        hull1b += " (change: <font color='red'>-" + ch_hull1 + "</font>)";
    } else if (ch_hull1 < 0) {
        ch_hull1 = ch_hull1 * (-1);
        hull1b += " (change: <font color='green'>+" + ch_hull1 + "</font>)";
    }
    i++;
    var armor1b = cr[i];
    var ch_armor1 = armor1a - armor1b;
    if (ch_armor1 > 0) {
        armor1b += " (change: <font color='red'>-" + ch_armor1 + "</font>)";
    } else if (ch_armor1 < 0) {
        ch_armor1 = ch_armor1 * (-1);
        armor1b += " (change: <font color='green' title='Robots'>+" + ch_armor1 + "</font>)";
    }
    i++;
    var shield1b = cr[i];
    var ch_shield1 = shield1a - shield1b;
    if (ch_shield1 > 0) {
        shield1b += " (change: <font color='red'>-" + ch_shield1 + "</font>)";
    } else if (ch_shield1 < 0) {
        ch_shield1 = ch_shield1 * (-1);
        shield1b += " (change: <font color='green' title='Charge'>+" + ch_shield1 + "</font>)";
    }
    i = i + 2;
    var hull2b = cr[i];
    var ch_hull2 = hull2a - hull2b;
    if (ch_hull2 > 0) {
        hull2b += " (change: <font color='red'>-" + ch_hull2 + "</font>)";
    } else if (ch_hull2 < 0) {
        ch_hull2 = ch_hull2 * (-1);
        hull2b += " (change: <font color='green'>+" + ch_hull2 + "</font>)";
    }
    i++;
    var armor2b = cr[i];
    var ch_armor2 = armor2a - armor2b;
    if (ch_armor2 > 0) {
        armor2b += " (change: <font color='red'>-" + ch_armor2 + "</font>)";
    } else if (ch_armor2 < 0) {
        ch_armor2 = ch_armor2 * (-1);
        armor2b += " (change: <font color='green' title='Robots'>+" + ch_armor2 + "</font>)";
    }
    i++;
    var shield2b = cr[i];
    var ch_shield2 = shield2a - shield2b;
    if (ch_shield2 > 0) {
        shield2b += " (change: <font color='red'>-" + ch_shield2 + "</font>)";
    } else if (ch_shield2 < 0) {
        ch_shield2 = ch_shield2 * (-1);
        shield2b += " (change: <font color='green' title='Charge'>+" + ch_shield2 + "</font>)";
    }
    i++;
    if (i < cr_size && cr[i] == 'R') {
        i++;
        var j = 0;
        while (i + 2 < cr_size) {
            raid[j] = {};
            raid[j].name = cr[i];
            raid[j].image = cr[i+1];
            raid[j].num = cr[i+2];
            j++;
            i = i + 3;
        }
    }
    stats1.hits = 0;
    stats1.hitsm = 0;
    stats1.crits = 0;
    stats1.critsm = 0;
    stats1.jams = 0;
    stats1.shots = 0;
    stats1.shotsm = 0;
    stats1.hull = 0;
    stats1.hullm = 0;
    stats1.armor = 0;
    stats1.armorm = 0;
    stats1.shield = 0;
    stats1.shieldm = 0;
    stats1.player = cr[0];
    stats1.ship = cr[1];
    var hits1_size = hits1.length;
    var hits2_size = hits2.length;

    for (p = 0; p < windices1.length; p++) {
        if (wtype1[windices1[p]].type == 'L') {
            stats1.hits += wstats1[windices1[p]].hits;
            stats1.crits += wstats1[windices1[p]].crits;
            if (who == 1) {
                var det_shots = (hits1_size - 1) * wtype1[windices1[p]].rate * wtype1[windices1[p]].shots;
                for (var p2 = 0; p2 <= lindices1.length; p2++) {
                    if (p2 > last_hit_windex) {
                        break;
                    }
                    if (windices1[p] == lindices1[p2]) {
                        if (p2 == last_hit_windex) {
                            det_shots += parseInt(last_hit_wrate) + 1;
                        } else {
                            det_shots += parseInt(wtype1[windices1[p]].rate);
                        }
                    }
                }
            } else {
                det_shots = hits1_size * wtype1[windices1[p]].rate * wtype1[windices1[p]].shots;
            }
            det_shots = parseInt(det_shots);
            det_shots -= wstats1[windices1[p]].jams;
            stats1.shots += det_shots;
            stats1.jams += wstats1[windices1[p]].jams;
            stats1.hull += wstats1[windices1[p]].hull;
            stats1.armor += wstats1[windices1[p]].armor;
            stats1.shield += wstats1[windices1[p]].shield;
            if (hits1_size * wtype1[windices1[p]].rate == 0) {
                det_ratio = "N/A";
            }
            else {
                var det_ratio = Math.round(wstats1[windices1[p]].hits / det_shots * 10000) / 100;
            }
        } else {
            stats1.hitsm += wstats1[windices1[p]].hits;
            stats1.critsm += wstats1[windices1[p]].crits;
            det_shots = wtype1[windices1[p]].shots;
            stats1.shotsm += det_shots;
            stats1.hullm += wstats1[windices1[p]].hull;
            stats1.armorm += wstats1[windices1[p]].armor;
            stats1.shieldm += wstats1[windices1[p]].shield;
            det_ratio = Math.round((wstats1[windices1[p]].hits / det_shots) * 10000) / 100;
        }
    }
    stats2.hits = 0;
    stats2.hitsm = 0;
    stats2.crits = 0;
    stats2.critsm = 0;
    stats2.jams = 0;
    stats2.shots = 0;
    stats2.shotsm = 0;
    stats2.hull = 0;
    stats2.hullm = 0;
    stats2.armor = 0;
    stats2.armorm = 0;
    stats2.shield = 0;
    stats2.shieldm = 0;
    stats2.player = cr[5];
    stats2.ship = cr[6];

    for (p = 0; p < windices2.length; p++) {
        if (wtype2[windices2[p]].type == 'L') {
            stats2.hits += wstats2[windices2[p]].hits;
            stats2.crits += wstats2[windices2[p]].crits;
            if (who == 2) {
                det_shots = (hits2_size - 1) * wtype2[windices2[p]].rate * wtype2[windices2[p]].shots;
                for (p2 = 0; p2 <= lindices1.length; p2++) {
                    if (p2 > last_hit_windex) {
                        break;
                    }
                    if (windices2[p] == lindices1[p2]) {
                        if (p2 == last_hit_windex) {
                            det_shots += parseInt(last_hit_wrate) + 1;
                        } else {
                            det_shots += parseInt(wtype2[windices2[p]].rate);
                        }
                    }
                }
            } else {
                det_shots = hits2_size * wtype2[windices2[p]].rate * wtype2[windices2[p]].shots;
            }
            det_shots = parseInt(det_shots);
            det_shots -= wstats2[windices2[p]].jams;
            stats2.shots += det_shots;
            stats2.jams += wstats2[windices2[p]].jams;
            stats2.hull += wstats2[windices2[p]].hull;
            stats2.armor += wstats2[windices2[p]].armor;
            stats2.shield += wstats2[windices2[p]].shield;
            if (hits2_size * wtype2[windices2[p]].rate == 0) {
                det_ratio = "N/A";
            }
            else {
                det_ratio = Math.round(wstats2[windices2[p]].hits / det_shots * 10000) / 100;
            }
        } else {
            stats2.hitsm += wstats2[windices2[p]].hits;
            stats2.critsm += wstats2[windices2[p]].crits;
            det_shots = wtype2[windices2[p]].shots;
            stats2.shotsm += det_shots;
            stats2.hullm += wstats2[windices2[p]].hull;
            stats2.armorm += wstats2[windices2[p]].armor;
            stats2.shieldm += wstats2[windices2[p]].shield;
            det_ratio = Math.round((wstats2[windices2[p]].hits / det_shots) * 10000) / 100;
        }
    }
    return [stats1, stats2];

}

try {
    var combatLink = combatLinkList[1].href;
} catch (error) {
    return;
}

function loadCombatLog(combatLink) {
    GM_xmlhttpRequest({
        method: "GET",
        url: combatLink,
        onload: function(response) {
            var htmlFragment = parseXHTTPResponseText(response.responseText);
            var cr = getCombatData(response.responseText);
            console.log('>> cr = ', cr);
            cr_interpreted = interpretcr(cr);
            var statstouse = cr_interpreted[0];
            var oppstatstouse = cr_interpreted[1];
            updateSkillStatButton(cr_interpreted);
        }
    });
    updateSkillStatButton(cr_interpreted);

}

function getCombatData(responseText) {
    var strt = responseText.indexOf('var cr = "') + 'var cr = "'.length;
    var end = strt;
    while (responseText[end] !== '"') end++;
    return responseText.slice(strt, end);
}

function loadShipInfo(combatStats, skillsToSend) {
     GM_xmlhttpRequest({
        method: "GET",
        url: shipLink,
        onload: function(response) {
            var htmlFragment = parseXHTTPResponseText(response.responseText);
            try {
                shipInfo.evasion = parseFloat(response.responseText.match(/(?<=Evasion Bonus )[0-9,.]*(?=%)/gm))
            } catch (error) {}
            
            try {
                // Check for Strong ECM first, then regular ECM
                if (response.responseText.match(/Strong ECM Jammer/gm)) {
                    shipInfo.ECM = response.responseText.match(/Strong ECM Jammer/gm)[0];
                } else if (response.responseText.match(/ECM Jammer/gm)) {
                    shipInfo.ECM = response.responseText.match(/ECM Jammer/gm)[0];
                }
            } catch (error) {}
            
            try {
                shipInfo.ECCM = response.responseText.match(/ECCM Jammer/gm)[0];
            } catch (error) {}

            submitToServer(combatStats, skillsToSend, shipInfo);

        }
     });
}

function loadCombatSkills(combatStats) {
    GM_xmlhttpRequest({
        method: "GET",
        url: skillsLink,
        onload: function(response) {
            var htmlFragment = parseXHTTPResponseText(response.responseText);
            for (var i = 0; i < 5 ; i++) {
                try {
                    skillsToSend[i] = parseFloat(htmlFragment.getElementById(skillTags[i]).outerHTML.match(/\>[0-9][^\<]*/g)[1].replace(/\>/,""));
                } catch (error) {
                    skillsToSend[i] = parseFloat(htmlFragment.getElementById(skillTags[i]).textContent);
                }
            }
            loadShipInfo(combatStats, skillsToSend);
            //submitToServer(combatStats, skillsToSend, shipInfo);
        }
    });
}

function submitToServer(combatStats, skills, shipInfo) {
    console.log(combatStats[0], combatStats[1],skills, shipInfo);
    var s1 = combatStats[0];
    var s2 = combatStats[1];
    var form = document.createElement("form");
    form.style.display = "none";
    form.action = serverURL;
    form.target = "submissionResult";
    //form.target = "_blank";
    form.method = "post";
    form.enctype = "multipart/form-data";

    append_input(form, "attacker", s1.player);
    append_input(form, "ship", s1.ship);
    append_input(form, "crits", s1.crits);
    append_input(form, "critsm", s1.critsm);
    append_input(form, "hits", s1.hits);
    append_input(form, "hitsm", s1.hitsm);
    append_input(form, "shots", s1.shots);
    append_input(form, "jams", s1.jams);
    append_input(form, "shotsm", s1.shotsm);
    append_input(form, "defender", s2.player);
    append_input(form, "ship2", s2.ship);
    append_input(form, "crits2", s2.crits);
    append_input(form, "critsm2", s2.critsm);
    append_input(form, "hits2", s2.hits);
    append_input(form, "hitsm2", s2.hitsm);
    append_input(form, "shots2", s2.shots);
    append_input(form, "jams2", s2.jams);
    append_input(form, "shotsm2", s2.shotsm);
    append_input(form, "tactics", skills[0]);
    append_input(form, "hit_accuracy", skills[1]);
    append_input(form, "maneuver", skills[2]);
    append_input(form, "weaponry", skills[3]);
    append_input(form, "engineering", skills[4]);
    append_input(form, "logid", combatLink.split('=')[1]);
    append_input(form, "evasion", shipInfo.evasion);
    append_input(form, "ECM", shipInfo.ECM);
    append_input(form, "ECCM", shipInfo.ECCM);

    var frame = document.createElement("iframe");
    frame.name = "submissionResult";
    frame.id = frame.name;
    frame.style.display = "none";
    document.body.appendChild(frame)

    document.body.appendChild(form);
    form.submit();
}

function updateSkillStatButton(combatToSubmit) {
    skillstatbutton.value = "load stats and submit"
    skillstatbutton.onclick = function() {
        loadCombatSkills(combatToSubmit);
        skillstatbutton.value = "stats loaded, combat submitted";
        skillstatbutton.onclick = null;
    }

}
/////////////Actual execution
if (combatLink === null) {
    return;
} else {
    var div = document.createElement("div");
    var skillstatbutton = document.createElement("input");
    skillstatbutton.type = "button"
    skillstatbutton.value = "load combat log"
    skillstatbutton.id = "skillstatbar";
    skillstatbutton.onclick = function() {
        combatToSubmit = loadCombatLog(combatLink, updateSkillStatButton);
    };
    skillstatbutton.setAttribute('style','background-color:#EDE275;color:#000;font-weight:bold;font-size:12px;border-radius:5px;padding:3px 6px;margin:0 5px;');
    var skillstatresult = document.createElement("div");
    skillstatresult.id = "combatlogresult";
    div.appendChild(skillstatbutton);
    div.appendChild(document.createElement("br"));
    div.appendChild(skillstatresult);
    div.appendChild(document.createElement("br"));
    var br = document.getElementsByTagName("br")[1];
    br.parentNode.insertBefore(div, br.nextSibling);
    skillstatresult.addEventListener("keydown", handleKeyPress);
    window.addEventListener("keydown", handleKeyPress);
    return;
}

function handleKeyPress(event){
    if(event.keyCode === 89) {
        skillstatbutton.click();
    }
}


<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_eduvidual
 * @copyright  2020 Center for Learningmanagement (http://www.lernmanagement.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This program is called directly within the login-procedure of auth_shibboleth_link.
 * It receives all profile data from the Shibboleth-Login and managers enrolments to organisations.
 */

if (empty($idpparams['userinfo']['username'])) {
    return;
}

global $DB, $USER;


// update userdetails
if ($idpparams['userinfo']['firstname'] && $idpparams['userinfo']['lastname'] && isloggedin() && !isguestuser()) {
    $update = [
        'firstname' => $idpparams['userinfo']['firstname'],
        'lastname' => $idpparams['userinfo']['lastname'],
        'id' => $USER->id,
    ];
    // middlename nur anfassen, wenn der IdP das Feld überhaupt mappt - dann aber voll
    // spiegeln (auch ein leerer Wert löscht einen entfernten middlename). Ist das Feld
    // nicht gemappt (Key fehlt), bleibt ein vorhandener middlename unangetastet.
    if (isset($idpparams['userinfo']['middlename'])) {
        $update['middlename'] = $idpparams['userinfo']['middlename'];
    }
    $DB->update_record('user', $update);
}


// If data is missing - fill with random data and store back to cache.
// TODO: für was wird das noch benötigt?!?
if (empty($idpparams['userinfo']['firstname'])) {
    $colors = file($CFG->dirroot . '/local/eduvidual/templates/names.colors', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $color_key = array_rand($colors, 1);
    $idpparams['userinfo']['firstname'] = $colors[$color_key];
}
if (empty($idpparams['userinfo']['lastname'])) {
    $animals = file($CFG->dirroot . '/local/eduvidual/templates/names.animals', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $animal_key = array_rand($animals, 1);
    $idpparams['userinfo']['lastname'] = $animals[$animal_key];
}
if (empty($idpparams['userinfo']['email'])) {
    $dummydomain = \local_eduvidual\locallib::get_dummydomain();
    $pattern = 'e-' . date("Ym") . '-';
    $usernameformat = $pattern . '%1$04d';
    $lasts = $DB->get_records_sql('SELECT username FROM {user} WHERE username LIKE ? ORDER BY username DESC LIMIT 0,1', [$pattern . '%']);
    if ((count($lasts)) > 0) {
        foreach ($lasts as $last) {
            $usernumber = intval(str_replace($pattern, '', $last->username)) + 1;
        }
    } else {
        $usernumber = 1;
    }

    $fictiveusername = sprintf($usernameformat, $usernumber++);
    $idpparams['userinfo']['email'] = $fictiveusername . $dummydomain;
}


\auth_shibboleth_link\lib::link_data_store_cache($idpparams);


// schulzugehörigkeit updaten
if (!empty($idpparams['userinfo']['affiliation']) && isloggedin() && !isguestuser()) {
    // affiliation: "rolle@SKZ;rolle@SKZ;..."
    $wantedbiproles = [];
    foreach (explode(';', $idpparams['userinfo']['affiliation']) as $affiliation) {
        [$bip_role, $orgid] = explode('@', trim($affiliation));

        // sanity checks
        if (!$bip_role || !$orgid) {
            continue;
        }

        $wantedbiproles[(int)$orgid][] = $bip_role;
    }

    // $wantedbiproles[999999] = ['tch'];

    // Die affiliation ist der komplette Schnappschuss aller Schulzugehörigkeiten: bei allen
    // angeführten Schulen eintragen, aus allen anderen austragen (Nicht-BIP-Orgs und Manager
    // sind über die Regeln in sync_user_orgs geschützt).
    //
    // Zuerst den BIP-Spiegel aktualisieren (hält Matching-Seite und Nachtlauf tagesaktuell),
    // dann denselben per-User-Abgleich anwenden wie der nächtliche update_users-Lauf -
    // beim Login für alle Rollen, nicht nur Schüler:innen.
    //
    // idpusername (= bpkbf) ist beim Shibboleth-Login immer gesetzt - der Login-Flow startet
    // nur mit vorhandenem user_attribute. Der Guard bleibt trotzdem: Mit leerer bpkbf wären
    // $rows leer und update_user würde den User aus allen BIP-Schulen austragen.
    $bpkbf = trim((string)($idpparams['idpusername'] ?? ''));
    if ($bpkbf) {
        \local_eduvidual\bip_helper::update_bip_user_mirror(
            $bpkbf,
            $wantedbiproles,
            $idpparams['userinfo']['firstname'] ?? '',
            $idpparams['userinfo']['lastname'] ?? '',
            $idpparams['userinfo']['email'] ?? '',
        );
        $rows = $DB->get_records('local_eduvidual_bip_user', ['bpkbf' => $bpkbf]);
        \local_eduvidual\bip_helper::update_user($USER->id, array_values($rows));
    }
}

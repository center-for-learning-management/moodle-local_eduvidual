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

if (empty($idpparams['userinfo']['username']))
    return;

global $DB, $USER;


// update userdetails
if ($idpparams['userinfo']['firstname'] && $idpparams['userinfo']['lastname'] && isloggedin() && !isguestuser()) {
    $DB->update_record('user', [
        'firstname' => $idpparams['userinfo']['firstname'],
        'lastname' => $idpparams['userinfo']['lastname'],
        'id' => $USER->id,
    ]);
}


// If data is missing - fill with random data and store back to cache.
// TODO: für was wird das noch benötigt?!?
if (empty($idpparams['userinfo']['firstname'])) {
    $colors = file($CFG->dirroot.'/local/eduvidual/templates/names.colors', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $color_key = array_rand($colors, 1);
    $idpparams['userinfo']['firstname'] = $colors[$color_key];
}
if (empty($idpparams['userinfo']['lastname'])) {
    $animals = file($CFG->dirroot.'/local/eduvidual/templates/names.animals', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $animal_key = array_rand($animals, 1);
    $idpparams['userinfo']['lastname'] = $animals[$animal_key];
}
if (empty($idpparams['userinfo']['email'])) {
    $dummydomain = \local_eduvidual\locallib::get_dummydomain();
    $pattern = 'e-'.date("Ym").'-';
    $usernameformat = $pattern.'%1$04d';
    $lasts = $DB->get_records_sql('SELECT username FROM {user} WHERE username LIKE ? ORDER BY username DESC LIMIT 0,1', array($pattern.'%'));
    if ((count($lasts)) > 0) {
        foreach ($lasts as $last) {
            $usernumber = intval(str_replace($pattern, '', $last->username)) + 1;
        }
    } else {
        $usernumber = 1;
    }

    $fictiveusername = sprintf($usernameformat, $usernumber++);
    $idpparams['userinfo']['email'] = $fictiveusername.$dummydomain;
}


\auth_shibboleth_link\lib::link_data_store_cache($idpparams);


function local_eduvidual_get_highest_role($memberships) {
    $highest = '';
    foreach ($memberships as $membership) {
        switch ($membership->role) {
            case \local_eduvidual\locallib::ROLE_PARENT:
            case \local_eduvidual\locallib::ROLE_STUDENT:
                if (empty($highest)) {
                    $highest = $membership->role;
                }
                break;
            case \local_eduvidual\locallib::ROLE_TEACHER:
                if (empty($highest) || $highest == \local_eduvidual\locallib::ROLE_STUDENT) {
                    $highest = $membership->role;
                }
                break;
            case \local_eduvidual\locallib::ROLE_MANAGER:
                $highest = $membership->role;
                break;
        }
    }

    return $highest;
}

// schulzugehörigkeit updaten
if (!empty($idpparams['userinfo']['affiliation']) && isloggedin() && !isguestuser()) {
    $affiliations = explode(';', $idpparams['userinfo']['affiliation']);
    $myorgs = $DB->get_records('local_eduvidual_orgid_userid', array('userid' => $USER->id));

    $wantedOrgs = [];
    foreach ($affiliations as $affiliation) {
        [$bip_role, $orgid] = explode('@', trim($affiliation));

        // sanity checks
        if (!$bip_role || !$orgid) {
            continue;
        }

        if (in_array($bip_role, ['adm', 'alr', 'dir', 'emp', 'tch', 'sqm', 'sqb'])) {
            $eduvidual_role = \local_eduvidual\locallib::ROLE_TEACHER;
        } elseif ($bip_role == 'lgn') {
            $eduvidual_role = \local_eduvidual\locallib::ROLE_PARENT;
        } elseif ($bip_role == 'std') {
            $eduvidual_role = \local_eduvidual\locallib::ROLE_STUDENT;
        } else {
            continue;
        }

        $org = \local_eduvidual\locallib::get_org('orgid', $orgid);
        if (!$org || !$org->authenticated) {
            // schule nicht gefunden oder nicht registriert
            continue;
        }

        if (empty($wantedOrgs[$orgid])) {
            $wantedOrgs[$orgid] = $org;
            $org->_roles = [];
        }

        $wantedOrgs[$orgid]->_roles[$eduvidual_role] = $eduvidual_role;
    }

    // $wantedOrgs[] = (object)[
    //     '_roles' => ['Teacher'],
    //     'orgid' => 999999,
    // ];

    // Scheint eine in eduvidual.at zugeordnete Schule nicht mehr in der Liste auf, soll die Schulzuordnung auch in eduvidual.at gelöst werden.
    foreach ($myorgs as $myorg) {
        if (strlen($myorg->orgid) != 6) {
            // Die ungültigen Schulkennzahlen (nicht 6-stellig) sollen von dieser Regel ausgenommen sein.
            // Hierbei handelt es sich um diverse Projektgruppen und den Ressourcenkatalog, bzw. die Spielwiese.
        }
        $stillWanted = array_filter($wantedOrgs, function($org) use ($myorg) {
            return $org->orgid == $myorg->orgid;
        });

        if (!$stillWanted) {
            // remove
            $org = \local_eduvidual\locallib::get_org('orgid', $myorg->orgid);
            \local_eduvidual\lib_enrol::role_set($USER->id, $org, 'remove');
        }
    }

    // Die eduvidual.at Nutzer/innen sollen allen Schulen, die in der Liste angeführt sind zugeordnet werden.
    foreach ($wantedOrgs as $wantedOrg) {
        $org = \local_eduvidual\locallib::get_org('orgid', $wantedOrg->orgid);
        if (!$org || !$org->authenticated) {
            // schule nicht gefunden oder nicht registriert
            continue;
        }

        $currentRoles = array_filter($myorgs, function($org) use ($wantedOrg) {
            return $org->orgid == $wantedOrg->orgid;
        });

        $currentHighestRole = local_eduvidual_get_highest_role($currentRoles);

        $highestRole = local_eduvidual_get_highest_role(array_merge($currentRoles, array_map(function($role) {
            return (object)['role' => $role];
        }, $wantedOrg->_roles)));

        if ($currentHighestRole == \local_eduvidual\locallib::ROLE_MANAGER) {
            // if user is manager, keep it
            continue;
        }

        // always reassign
        \local_eduvidual\lib_enrol::role_set($USER->id, $org, $highestRole);
    }
}

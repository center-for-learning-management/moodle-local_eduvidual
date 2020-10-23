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

if (empty($idpparams['userinfo']['username'])) return;

global $DB, $USER;

// 1.) If data is missing - fill with random data and store back to cache.
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
    $usernameformat= $pattern . '%1$04d';
    $lasts = $DB->get_records_sql('SELECT username FROM {user} WHERE username LIKE ? ORDER BY username DESC LIMIT 0,1', array($pattern . '%'));
    if ((count($lasts)) > 0) {
        foreach($lasts AS $last){
            $usernumber = intval(str_replace($pattern, '', $last->username)) + 1;
        }
    } else {
        $usernumber = 1;
    }

    $fictiveusername = sprintf($usernameformat, $usernumber++);
    $idpparams['userinfo']['email'] = $fictiveusername . $dummydomain;
}


\auth_shibboleth_link\lib::link_data_store_cache($idpparams);

// 2.) if institution is given, determine role and enrol to organization

if (!empty($idpparams['userinfo']['institution'])) {
    $role = 'Student';
    if (!empty($idpparams['userinfo']['appList'])) {
         $applist = explode('\;', $idpparams['userinfo']['appList']);
         foreach ($applist as $app) {
             $approle = explode('@', $app);
             if (count($approle) > 1 && $approle[1] == 'EVI') {
                 switch ($approle[0]) {
                     case 'std': $role = 'Student'; break;
                     case 'tch': $role = 'Teacher'; break;
                     case 'adm': $role = 'Manager'; break;
                 }
             }
         }
    }
    $org = $DB->get_record('local_eduvidual_org', array('orgid' => $idpparams['userinfo']['institution']));

    if (!empty($org->id) && !empty($org->authenticated)) {
        $membership = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $idpparams['userinfo']['institution'], 'userid' => $USER->id));
        if (empty($membership->role)) {
            \local_eduvidual\lib_enrol::role_set($USER->id, $org, $role);
        } else {
            switch ($membership->role) {
                // Manager cannot get better - keep...
                case 'Teacher':
                    if (in_array($role, array('Manager'))) {
                        \local_eduvidual\lib_enrol::role_set($USER->id, $org, $role);
                    }
                break;
                case 'Student':
                case 'Parent':
                    if (in_array($role, array('Manager', 'Teacher'))) {
                        \local_eduvidual\lib_enrol::role_set($USER->id, $org, $role);
                    }
                break;
            }
        }
        // 3.) If there is a department create a cohort for that department
        if (!empty($idpparams['userinfo']['department'])) {
            \local_eduvidual\lib_enrol::cohorts_add($USER->id, $org, $idpparams['userinfo']['department']);
        }
    }
}

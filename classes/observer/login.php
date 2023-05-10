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
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual\observer;

defined('MOODLE_INTERNAL') || die;


class login {
    public static function event($event) {
        global $CFG, $DB, $PAGE, $SESSION, $USER;
        $debug = false; // ($USER->id == 3707); //false;
        $data = (object)$event->get_data();
        //error_log(json_encode($data, JSON_NUMERIC_CHECK));

        $user = $DB->get_record('user', array('id' => $data->userid));
        require_once($CFG->dirroot . '/user/profile/lib.php');
        profile_load_data($user);

        // hack for https://github.com/center-for-learning-management/moodle-local_eduvidual/issues/176
        // Error: Class "local_eduvidual\locallib" not found
        require_once __DIR__.'/../locallib.php';

        \local_eduvidual\locallib::get_user_secret($user->id);
        //error_log(json_encode($user, JSON_NUMERIC_CHECK));

        if ($user->id != intval($user->idnumber)) {
            $user->idnumber = str_pad($user->id, 7, 0, STR_PAD_LEFT);
            $DB->set_field('user', 'idnumber', $user->idnumber, array('id' => $user->id));
        }

        // We only check for roles managed by profile for mnet accounts
        if ($user->auth == 'mnet') {
            $org;
            $orgs_ = $DB->get_records('local_eduvidual_org', array('mnetid' => $user->mnethostid));
            // Create Array of orgs that use this mnethost.
            $orgs = array();
            $orgids = array();
            foreach($orgs_ AS $org) {
                $orgs[] = $org;
                $orgids[] = $org->orgid;
            }
            if ($user->mnethostid == 3) {
                $additionalorg = $DB->get_record('local_eduvidual_org', array('orgid' => '601457'));
                $orgs[] = $additionalorg;
                $orgids[] = $additionalorg->orgid;
            }
            if ($debug) error_log('This user comes via MNet, ' . count($orgs) . ' possible orgs, checking for ' . $user->institution);
            if (count($orgs) > 0) {
                // This mnet host belongs to at least one org
                if (count($orgs) == 1) {
                    if ($debug) error_log('Only 1 org - ' . $org->orgid);
                    // There is only 1 possible org - we set this orgid in profile
                    $org = $orgs[0];
                    $user->institution = $org->orgid;
                } elseif(!empty($user->institution)) {
                    // There are more than 1 possible orgs and we have an institution
                    // Determine if the institution given is valid!
                    $org = -1;
                    if ($debug) error_log(count($orgs) . ' orgs');
                    foreach($orgs AS $org_) {
                        if ($debug) error_log('Compare ' . $org_->orgid . ' to ' . $user->institution);
                        if ($org_->orgid == $user->institution) {
                            $org = $org_;
                        }
                    }
                    if ($debug) error_log('Selected org - ' . $org->orgid);
                    if (empty($org->orgid)) {
                        $user->institution = '';
                    }
                } else {
                    if ($debug) error_log('Multiple options and no institution given. Can not auto enrol to org.');
                }
            }
            // Update user record
            $DB->update_record('user', $user);

            // Proceed only if we still have a valid institution.
            //$org = $DB->get_record('oer_local_eduvidual_org', array('orgid' => $user->institution));
            if (!empty($org->orgid) && $org->orgid == $user->institution) {
                // Check if we already have a role.
                $currentrole = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $org->orgid, 'userid' => $user->id));
                // If department is empty default to Student
                $setrole = $user->department;
                $roles = array('Manager', 'Teacher', 'Student', 'Parent');
                if (!in_array($setrole, $roles) && empty($currentrole->role)) {
                    $setrole = 'Student';
                }
                if (!empty($setrole)) {
                    $reply = \local_eduvidual\lib_enrol::role_set($user->id, $org, $setrole);
                    $success = isset($reply['enrolments']) ? count($reply['enrolments']) : 0;
                    if ($debug) error_log('Set role from mnet: ' . $setrole . ' on user ' . $user->id . ' for org ' . $org->orgid . ' => ' . $success . ' enrolments done');
                    //error_log(print_r($reply, 1));
                } else {
                    if ($debug) error_log('No role to assign');
                    //error_log(print_r($org, 1));
                    //error_log(print_r($user, 1));
                }
            } else {
                if ($debug) error_log('No valid org for user found with institution ' . $user->institution);
            }
        }

        // For all users - check maildomain
        $maildomain = explode('@', strtolower($user->email));
        $maildomain = '@' . $maildomain[1];
        if (strlen($maildomain) > 3) {
            //error_log('SELECT * FROM oer_local_eduvidual_org WHERE maildomain="' . $maildomain . '" OR maildomainteacher="' . $maildomain . '"');
            $chkorgs = $DB->get_records_sql('SELECT * FROM {local_eduvidual_org} WHERE maildomain LIKE ? OR maildomainteacher LIKE ?', array('%' . $maildomain . '%', '%' . $maildomain . '%'));
            foreach($chkorgs AS $chkorg) {
                $member = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $chkorg->orgid, 'userid' => $user->id));
                if (!isset($member->role) || empty($member->role)) {
                    $setrole = (strpos($chkorg->maildomainteacher, $maildomain) !== false) ? 'Teacher': 'Student';
                    if (!isset($reply['enrolments'])) {
                        $reply['enrolments'] = array();
                    }
                    if ($member->role == 'Manager') $setrole = 'Manager';
                    if ($debug) error_log('Set role from maildomain ' . $maildomain . ': ' . $setrole . ' on user ' . $user->id . ' for org ' . $chkorg->orgid);
                    $reply['enrolments'][] = \local_eduvidual\lib_enrol::role_set($user->id, $chkorg, $setrole);
                }
            }
        }

        // CODE BELOW HERE IS OUTSIDE OF LOGIN AND IS EXECUTED EACH TIME A TRACKED EVENT OCCURS
        // Force redirect to profile if name is empty!
        if (empty($USER->firstname) || empty($USER->lastname)) {
            // This caused an issue with oauth providers (redirect loop).
            // Therefore, if auth is oauth2 we will set dummy names.
            if ($USER->auth == 'oauth2') {
                $colors = file($CFG->dirroot . '/local/eduvidual/templates/names.colors', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $animals = file($CFG->dirroot . '/local/eduvidual/templates/names.animals', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $color_key = array_rand($colors, 1);
                $animal_key = array_rand($animals, 1);
                $USER->firstname = $colors[$color_key];
                $USER->lastname = $animals[$animal_key];
                $DB->update_record('user', $USER);
            } else {
                redirect($CFG->wwwroot . '/user/profile.php?id=' . $USER->id);
            }
        }
    }
}

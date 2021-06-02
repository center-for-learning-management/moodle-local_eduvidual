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
* @copyright  2018 Digital Education Society (http://www.dibig.at)
* @author     Robert Schrenk
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die;

if (!is_siteadmin()) {
    $reply['error'] = get_string('access_denied', 'local_eduvidual');
} else {
    $act = optional_param('act', '', PARAM_TEXT);
    switch ($act) {
        case 'allmanagerscourses':
            $allmanagerscourses = optional_param('allmanagerscourses', '', PARAM_TEXT);
            if (set_config('allmanagerscourses', $allmanagerscourses, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
        break;
        case 'blockfooter':
            $blockfooter = optional_param('blockfooter', '', PARAM_TEXT);
            if (set_config('blockfooter', $blockfooter, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
        break;
        case 'coursebasements':
            $courseempty = optional_param('courseempty', 0, PARAM_INT);
            $courserestore = optional_param('courserestore', 0, PARAM_INT);
            $coursetemplate = optional_param('coursetemplate', 0, PARAM_INT);

            set_config('coursebasementempty', $courseempty, 'local_eduvidual');
            set_config('coursebasementrestore', $courserestore, 'local_eduvidual');
            set_config('coursebasementtemplate', $coursetemplate, 'local_eduvidual');

            $reply['status'] = 'ok';
        break;
        case 'defaultrole':
            $role = optional_param('role', 0, PARAM_INT);
            $type = optional_param('type', '', PARAM_TEXT);
            $types = array('parent', 'student', 'teacher');
            if (in_array($type, $types)) {
                set_config('defaultrole' . $type, $role, 'local_eduvidual');
                $reply['status'] = 'ok';
            } else {
                $reply['error'] = 'invalid_type';
            }
        break;
        case 'dropzonepath':
            $dropzonepath = optional_param('dropzonepath', '', PARAM_TEXT);
            if (empty($dropzonepath)) {
                set_config('dropzonepath', '', 'local_eduvidual');
                $reply['status'] = 'ok';
            } elseif (is_dir($dropzonepath)) {
                if (is_writable($dropzonepath)) {
                    if (set_config('dropzonepath', $dropzonepath, 'local_eduvidual')) {
                        $reply['status'] = 'ok';
                    } else {
                        $reply['status'] = 'Could not set config!';
                    }
                }
            } else {
                $reply['status'] = 'Does not exist!';
            }
        break;
        case 'ltiresourcekey':
            $ltiresourcekey = optional_param('ltiresourcekey', '', PARAM_TEXT);
            if (set_config('ltiresourcekey', $ltiresourcekey, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
        break;
        case 'manageorgs_search':
            $search = optional_param('search', '', PARAM_TEXT);
            if (!empty($search)) $search = '%' . $search . '%';
            $fields = array('id', 'categoryid', 'city', 'country', 'orgid', 'name', 'mail', 'phone', 'street', 'zip');
            $sql = "SELECT o." . implode(',o.', $fields) . ",og.lon,og.lat
                        FROM {local_eduvidual_org} o
                            LEFT JOIN {local_eduvidual_org_gps} og ON o.orgid=og.orgid
                        WHERE o." . implode(' LIKE ? OR o.', $fields);
            $params = array();
            foreach($fields AS $field) {
                $params[] = $search;
            }
            $sql .= " LIKE ? ORDER BY name ASC";

            $reply['sql'] = $sql;
            $reply['orgs'] = $DB->get_records_sql($sql, $params);
            $reply['status'] = 'ok';
        break;
        case 'manageorgs_store':
            $params = optional_param_array('fields', '', PARAM_TEXT);
            $fields = array_keys($params);
            // Validate fields
            $valid = true;
            $reply['errors'] = array();
            $reply['errors_reasons'] = array();
            // Check if required fields are set.
            $required = array('orgid', 'mail', 'name');
            foreach($required AS $_required) {
                if (empty($params[$_required])) {
                    $valid = false;
                    $reply['errors'][] = $_required;
                    $reply['errors_reasons'][] = 'required';
                }
            }
            if (!filter_var($params['mail'], FILTER_VALIDATE_EMAIL)) {
                $valid = false;
                $reply['errors'][] = 'mail';
                $reply['errors_reasons'][] = 'no valid mail';
            }

            $checkorg = $DB->get_record('local_eduvidual_org', array('orgid' => $params['orgid']));
            if (empty($params['id']) && !empty($checkorg->orgid) && $checkorg->id != $params['id']) {
                $valid = false;
                $reply['errors'][] = 'orgid';
                $reply['errors_reasons'][] = 'orgid already given to another org';
            }
            if ($valid) {
                if (!empty($params['id'])) {
                    $org = $DB->get_record('local_eduvidual_org', array('id' => $params['id']));
                    foreach ($fields AS $field) {
                        $org->{$field} = $params[$field];
                    }
                    $DB->update_record('local_eduvidual_org', $org);
                    $reply['status'] = 'ok';
                } else {
                    $params['subcats1'] = get_string('createcourse:subcat1:defaults', 'local_eduvidual');
                    $params['subcats2'] = '';
                    $params['subcats3'] = '';
                    $params['customcss'] = '';

                    $id = $DB->insert_record('local_eduvidual_org', $params, true);
                    if ($id > 0) {
                        $reply['status'] = 'ok';
                    }
                }
                if (!empty($params['lat']) && !empty($params['lon'])) {
                    $gps = $DB->get_record('local_eduvidual_org_gps', array('orgid' => $org->orgid));
                    if (!empty($gps->id)) {
                        $gps->lat = $params['lat'];
                        $gps->lon = $params['lon'];
                        $gps->modified = time();
                        $gps->failed = 0;
                        $DB->update_record('local_eduvidual_org_gps', $gps);
                    } else {
                        $gps = (object) array(
                            'orgid' => $org->orgid,
                            'lat' => $params['lat'],
                            'lon' => $params['lon'],
                            'modified' => time(),
                            'failed' => 0,
                        );
                        $DB->insert_record('local_eduvidual_org_gps', $gps);
                    }
                } else {
                    $DB->delete_records('local_eduvidual_org_gps', array('orgid' => $params['orgid']));
                }
            } else {
                $reply['status'] = 'error';
                $reply['error'] = 'Invalid data';
            }
        break;
        case 'phplistconfig':
            $field = optional_param('field', '', PARAM_TEXT);
            $content = optional_param('content', '', PARAM_TEXT);
            if (!empty($field) && set_config('phplist_' . $field, $content, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
        break;
        case 'protectedorgs':
            $protectedorgs = optional_param('protectedorgs', '', PARAM_TEXT);
            if (set_config('protectedorgs', $protectedorgs, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
        break;
        case 'orgrole':
            $role = optional_param('role', 0, PARAM_INT);
            $type = optional_param('type', '', PARAM_TEXT);
            $types = array('manager', 'teacher', 'student', 'parent');
            // Set to 0 if you require at least one!
            if (in_array($type, $types)) {
                // We test if the new role is already used by another role.
                $roleinuse = false;
                foreach ($types AS $testtype) {
                    if ($testtype == $type) continue;
                    if (get_config('local_eduvidual', 'defaultorgrole' . $testtype) == $role) {
                        $roleinuse = true;
                    };
                }
                if (!$roleinuse) {
                    $previousrole = get_config('local_eduvidual', 'defaultorgrole' . $type);
                    //$reply['previousrole'] = $previousrole;
                    if (!empty($previousrole) && $previousrole != $role) {
                        // We remove the previously set roles.
                        //$reply['unassigning'] = array();
                        $members = $DB->get_records('local_eduvidual_orgid_userid', array('role' => ucfirst($type)), 'orgid ASC', '*');
                        $orgid = 0; $contextid = 0;
                        foreach ($members AS $member) {
                            if ($member->orgid != $orgid) {
                                $org = $DB->get_record('local_eduvidual_org', array('orgid' => $member->orgid));
                                if (empty($org->categoryid)) continue; // Skip this org - no category.
                                $context = \context_coursecat::instance($org->categoryid, IGNORE_MISSING);
                                $contextid = $context->id;
                            }
                            if (empty($contextid)) continue;
                            //$reply['unassigning'][] = $member->userid . ' / ' . $contextid;
                            role_unassign($previousrole, $member->userid, $contextid);
                        }
                    }
                    if (!empty($role)) {
                        set_config('defaultorgrole' . $type, $role, 'local_eduvidual');
                        //$reply['assigning'] = array();
                        $members = $DB->get_records('local_eduvidual_orgid_userid', array('role' => ucfirst($type)), 'orgid ASC', '*');
                        $orgid = 0; $contextid = 0;
                        foreach ($members AS $member) {
                            if ($member->orgid != $orgid) {
                                $org = $DB->get_record('local_eduvidual_org', array('orgid' => $member->orgid));
                                if (empty($org->categoryid)) continue; // Skip this org - no category.
                                $context = \context_coursecat::instance($org->categoryid, IGNORE_MISSING);
                                $contextid = $context->id;
                            }
                            if (empty($contextid)) continue;
                            // Check if this user still exists.
                            $user = \core_user::get_user($member->userid, 'id,deleted', IGNORE_MISSING);
                            if (empty($user->id) || $user->deleted) continue;
                            //$reply['assigning'][] = $member->userid . ' / ' . $contextid;
                            role_assign($role, $member->userid, $contextid);
                        }
                    } else {
                        // How to remove a plugin-config?
                        //remove_config('defaultorgrole' . $type, 'local_eduvidual');
                    }
                    $reply['status'] = 'ok';
                } else {
                    $reply['error'] = get_string('orgrole:role_already_in_use', 'local_eduvidual');
                }


            } else {
                $reply['error'] = 'config_not_set';
            }
        break;
        case 'globalrole':
            $role = optional_param('role', 0, PARAM_INT);
            $type = optional_param('type', '', PARAM_TEXT);
            $types = array('manager', 'teacher', 'student', 'parent');
            // Set to 0 if you require at least one!
            if (in_array($type, $types)) {
                // We test if the new role is already used by another role.
                $roleinuse = false;
                foreach ($types AS $testtype) {
                    if ($testtype == $type) continue;
                    if (get_config('local_eduvidual', 'defaultglobalrole' . $testtype) == $role) {
                        $roleinuse = true;
                    };
                }
                if (!$roleinuse) {
                    $context = \context_system::instance();
                    $previousrole = get_config('local_eduvidual', 'defaultglobalrole' . $type);
                    //$reply['previousrole'] = $previousrole;
                    if (!empty($previousrole) && $previousrole != $role) {
                        // We remove the previously set roles.
                        $assignments = $DB->get_records('role_assignments', array('roleid' => $previousrole, 'contextid' => $context->id));
                        foreach ($assignments AS $assignment) {
                            role_unassign($previousrole, $assignment->userid, $context->id);
                        }
                    }
                    if (!empty($role)) {
                        set_config('defaultglobalrole' . $type, $role, 'local_eduvidual');
                        //$reply['assigning'] = array();
                        $members = $DB->get_records('local_eduvidual_orgid_userid', array('role' => ucfirst($type)));
                        foreach ($members AS $member) {
                            // Check if this user still exists.
                            $user = \core_user::get_user($member->userid, 'id,deleted', IGNORE_MISSING);
                            if (empty($user->id) || $user->deleted) continue;
                            //$reply['assigning'][] = $member->userid . ' / ' . $contextid;
                            role_assign($role, $member->userid, $context->id);
                        }
                    } else {
                        // How to remove a plugin-config?
                        //remove_config('defaultglobalrole' . $type, 'local_eduvidual');
                    }
                    $reply['status'] = 'ok';
                } else {
                    $reply['error'] = get_string('defaultroles:global:inuse', 'local_eduvidual');
                }
            } else {
                $reply['error'] = 'config_not_set';
            }
        break;
        case 'modifylogin':
            $setto = optional_param('setto', 0, PARAM_INT);
            if (set_config('modifylogin', $setto, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
        break;
        case 'modulecatform':
            $categoryid = optional_param('categoryid', 0, PARAM_INT);
            $reply['category'] = $DB->get_record('local_eduvidual_modulescat', array('id' => $categoryid));
            $reply['status'] = (isset($reply['category']) && isset($reply['category']->id))?'ok':'error';
        break;
        case 'modulecatstore':
            $categoryid = optional_param('categoryid', -1, PARAM_INT);
            $parentid = optional_param('parentid', -1, PARAM_INT);
            $entry = new \stdClass;
            if ($categoryid > 0) {
                $entry = $DB->get_record('local_eduvidual_modulescat', array('id' => $categoryid));
            } else {
                if ($parentid > -1) {
                    $entry->parentid = $parentid;
                } else {
                    exit;
                }
            }
            $entry->active = optional_param('active', 0, PARAM_INT);
            $entry->name = optional_param('name', '', PARAM_TEXT);
            $entry->description = optional_param('description', '', PARAM_TEXT);
            $chk = false;
            if ($categoryid > -1) {
                $chk = $DB->update_record('local_eduvidual_modulescat', $entry);
            } else {
                $entry->id = $DB->insert_record('local_eduvidual_modulescat', $entry, true);
                $chk =  ($entry->id > 0);
            }
            if ($chk) {
                $reply['status'] = 'ok';
                $reply['category'] = $entry;
            }
        break;
        case 'moolevels':
            $moolevels = optional_param_array('moolevels', NULL, PARAM_INT);
            // Set to 0 if you require at least one!
            if (count($moolevels) > -1) {
                set_config('moolevels', implode(",", $moolevels), 'local_eduvidual');
                $reply['status'] = 'ok';
            } else {
                $reply['error'] = 'config_not_set';
            }
        break;
        case 'navbar':
            $navbar = optional_param('navbar', '', PARAM_TEXT);
            if (set_config('navbar', $navbar, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
        break;
        case 'orgcoursebasement':
            $basement = optional_param('basement', 0, PARAM_INT);

            if (\local_eduvidual\lib_enrol::is_valid_course_basement('system', $basement)) {
                set_config('orgcoursebasement', $basement, 'local_eduvidual');
                $reply['status'] = 'ok';
            } else {
                $reply['error'] = 'invalid_orgcoursebasement';
            }
        break;
        case 'questioncategories':
            $qc = optional_param_array('questioncategories', NULL, PARAM_INT);
            $sc = optional_param_array('supportcourses', NULL, PARAM_INT);
            // Set to 0 if you require at least one!
            $setrole = get_config('local_eduvidual', 'defaultrolestudent');
            if (count($qc) > -1) {
                set_config('questioncategories', implode(",", $qc), 'local_eduvidual');
                for ($a = 0; $a < count($qc); $a++) {
                    $osc = get_config('local_eduvidual', 'questioncategory_' . $qc[$a] . '_supportcourse');
                    $nsc = $sc[$a];
                    if (!empty($nsc) && $nsc != $osc) {
                        $ctx = \context_course::instance($nsc, IGNORE_MISSING);
                        if (!empty($ctx)) {
                            set_config('questioncategory_' . $qc[$a] . '_supportcourse', $nsc, 'local_eduvidual');
                            $sql = "SELECT userid FROM {local_eduvidual_userqcats} WHERE categoryid = :categoryid";
                            $userids = array_keys($DB->get_records_sql($sql, array('categoryid' => $qc[$a])));
                            \local_eduvidual\lib_enrol::course_manual_enrolments([$nsc], $userids, $setrole);
                            $reply['enrolled_to_' . $nsc] = count($userids);
                        }
                    }
                    if (empty($nsc)) {
                        set_config('questioncategory_' . $qc[$a] . '_supportcourse', '', 'local_eduvidual');
                    }
                }
                $reply['status'] = 'ok';
            } else {
                $reply['error'] = 'config_not_set';
            }
        break;
        case 'requirecapability':
            $requirecapability = optional_param('requirecapability', 0, PARAM_INT);
            if (set_config('requirecapability', $requirecapability, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
        break;
        case 'registrationcc':
            $registrationcc = optional_param('registrationcc', '', PARAM_TEXT);
            if (set_config('registrationcc', $registrationcc, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
        break;
        case 'registrationsupport':
            $registrationsupport = optional_param('registrationsupport', '', PARAM_TEXT);
            if (set_config('registrationsupport', $registrationsupport, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
        break;
        case 'trashcategory':
            $category = optional_param('trashcategory', 0, PARAM_TEXT);
            set_config('trashcategory', $category, 'local_eduvidual');
            $reply['status'] = 'ok';
        break;
        default:
            $reply['error'] = 'Unknown action';
    }
}

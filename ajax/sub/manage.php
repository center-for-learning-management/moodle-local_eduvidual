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
 * @package    block_eduvidual
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Used to determine if we can manage this org
$current_orgid = optional_param('orgid', 0, PARAM_INT);
$orgas = block_eduvidual::get_organisations('Manager');
if (count($orgas) == 1 && $current_orgid == 0) {
	foreach($orgas AS $orga) {
		$current_orgid = $orga->orgid;
	}
}
$org = block_eduvidual::get_organisations_check($orgas, $current_orgid);
if (!$org) {
	$reply['error'] = get_string('access_denied', 'block_eduvidual');
	$reply['orgid'] = $current_orgid;
	$reply['orgas'] = $orgas;
} else {
	block_eduvidual::set_org($org->orgid);

    $act = optional_param('act', '', PARAM_TEXT);
    switch ($act) {
        case 'accesscode_create':
            $code = optional_param('code', '', PARAM_TEXT);
            $maturity = optional_param('maturity', '2000-01-01 12:00:00', PARAM_TEXT);
            $role = optional_param('role', '', PARAM_TEXT);
            $roles = array('Student', 'Teacher', 'Manager', 'Parent');
            $maturity = strtotime($maturity);
            if (!empty($code) && $maturity > time() && in_array($role, $roles)) {
                $cnt = $DB->count_records_sql('SELECT COUNT(id) FROM {block_eduvidual_org_codes} WHERE orgid=? AND code=? AND maturity>UNIX_TIMESTAMP(NOW())', array($org->orgid, $code));
                $reply['cnt'] = $cnt;
                if ($cnt == 0) {
                    $code = (object) array(
                        'orgid' => $org->orgid,
                        'userid' => $USER->id,
                        'code' => $code,
                        'role' => $role,
                        'maturity' => $maturity,
                    );
                    $code->id = $DB->insert_record('block_eduvidual_org_codes', $code, true);
                    $reply['code'] = $code;
                    if ($code->id > 0) {
                        $reply['status'] = 'ok';
                    } else {
                        $reply['error'] = 'could_not_store';
                    }
                } else {
                    $reply['error'] = 'code_already_in_use';
                }
            } else {
                $reply['error'] = 'invalid_params';
            }
        break;
        case 'accesscode_revoke':
            $id = optional_param('id', 0, PARAM_INT);
            if ($id > 0) {
                // Check if this code belongs to the correct org
                $code = $DB->get_record('block_eduvidual_org_codes', array('id' => $id, 'orgid' => $org->orgid));
                if (!empty($code->id) && $code->id == $id) {
                    $code->maturity = time();
                    $DB->update_record('block_eduvidual_org_codes', $code);
                    $reply['status'] = 'ok';
                } else {
                    $reply['error'] = 'accesscode_does_not_belong_to_this_org';
                }
            } else {
                $reply['error'] = 'no_accesscode_given';
            }
        break;
        case 'addcategory':
        case 'editcategory':
        case 'removecategory':
            // Check if this category really belongs to the given org
            $parentid = optional_param('parentid', 0, PARAM_INT);
            $cats = $DB->get_records_sql('SELECT * FROM {course_categories} WHERE path LIKE ? AND id=?', array('/' . $org->categoryid . '%', $parentid));
            // Take first result as $cat
            foreach($cats AS $cat) {}
            if (isset($cat) && $cat->id == $parentid) {
                // If so, add or remove it
                require_once($CFG->dirroot . '/lib/coursecatlib.php');
                if ($act == 'addcategory') {
                    $name = optional_param('name', '', PARAM_TEXT);
                    if (empty($name) || strlen($name) < 3) {
                        $reply['error'] = 'name_too_short';
                    } elseif (strlen($name) > 255) {
                        $reply['error'] = 'name_too_long';
                    } else {
                        $data = array(
                            'name' => $name,
                            'description' => '',
                            'parent' => $parentid,
                            'visible' => 1
                        );
                        $reply['catdata'] = $data;
                        $catid = coursecat::create($data);
                        $reply['catid'] = $catid;
                        if ($catid > 0) {
                            $reply['status'] = 'ok';
                        }
                    }
                }
                if ($act == 'editcategory') {
                    if ($parentid == $org->categoryid) {
                        $reply['error'] = 'root_category_can_not_be_touched';
                    } else {
                        $name = optional_param('name', '', PARAM_TEXT);
                        if (empty($name) || strlen($name) < 3) {
                            $reply['error'] = 'name_too_short';
                        } elseif (strlen($name) > 255) {
                            $reply['error'] = 'name_too_long';
                        } else {
                            $cat = coursecat::get($parentid);
                            $reply['editedcat'] = $DB->get_record('course_categories', array('id' => $parentid));
                            $data = $cat->get_db_record();
                            $data->name = $name;
                            $cat->update($data);
                            $reply['status'] = 'ok';
                        }
                    }
                }
                if ($act == 'removecategory') {
                    if ($parentid == $org->categoryid) {
                        $reply['error'] = 'root_category_can_not_be_touched';
                    } else {
                        $cat = coursecat::get($parentid);
                        $reply['removedcat'] = $DB->get_record('course_categories', array('id' => $parentid));
                        $trashcategory = get_config('block_eduvidual', 'trashcategory');
                        if (!empty($trashcategory)) {
                            $cat->delete_move($trashcategory);
                        } else {
                            $cat->delete_full();
                        }
                        $reply['status'] = 'ok';
                    }
                }
            } else {
                $reply['error'] = 'category_does_not_belong_to_org';
                $reply['cats'] = $cats;
            }

        break;
        case 'addparent':
            $orgid = optional_param('orgid', 0, PARAM_INT);
            $studentid = optional_param('studentid', 0, PARAM_INT);
            $parentid = optional_param('parentid', 0, PARAM_INT);

            if ($orgid > 0 && $studentid > 0 && $parentid > 0) {
                $chk = $DB->get_record('block_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $USER->id));
                if (block_eduvidual::get('role') != 'Administrator' && $chk->role != 'Manager') {
                    $reply['error'] = 'not_member_of_this_org';
                } else {
                    $chk_student = $DB->get_record('block_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $studentid));
                    $chk_parent = $DB->get_record('block_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $parentid));
                    if ($chk_student->userid != $studentid) {
                        $reply['error'] = 'student_not_member_of_this_org';
                    } elseif($chk_parent->userid != $parentid) {
                        $reply['error'] = 'parent_not_member_of_this_org';
                    } else {
                        $studentcontext = context_user::instance($studentid);
                        $parentrole = get_config('block_eduvidual', 'defaultroleparent');
                        $hasrole = $DB->get_record('role_assignments', array('userid' => $parentid, 'roleid' => $parentrole, 'contextid' => $studentcontext->id));
                        if (isset($hasrole->id) && $hasrole->id > 0) {
                            role_unassign($parentrole, $parentid, $studentcontext->id);
                        } else {
                            role_assign($parentrole, $parentid, $studentcontext->id);
                        }

                    }
                }
            } else {
                $reply['error'] = 'missing_data';
            }
        break;
        case 'addparent_filter':
            $orgid = optional_param('orgid', 0, PARAM_INT);
            $studentid = optional_param('studentid', 0, PARAM_INT);
            $chk = $DB->get_record('block_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $USER->id));
            if (block_eduvidual::get('role') != 'Administrator' && $chk->role != 'Manager') {
                $reply['error'] = 'not_member_of_this_org';
            } else {
                $filter = '%' . optional_param('filter', 'zzzzzz', PARAM_TEXT) . '%';
                $entries = $DB->get_records_sql('SELECT u1.id,CONCAT(u1.firstname, " ", u1.lastname) AS userfullname, u1.email FROM {user} AS u1, {block_eduvidual_orgid_userid} AS ou WHERE ou.orgid=? AND ou.userid = u1.id AND u1.id IN (SELECT u2.id FROM {user} AS u2 WHERE u2.firstname LIKE ? OR u2.lastname LIKE ? OR u2.email LIKE ? OR CONCAT(u2.firstname, " ", u2.lastname) LIKE ?) ORDER BY u1.lastname ASC, u1.firstname ASC', array($orgid, $filter, $filter, $filter, $filter));
                $reply['users'] = array();
                if ($studentid > 0) {
                    $parents = array();
                    $users = array();
                    $studentcontext = context_user::instance($studentid);
                    $parentrole = get_config('block_eduvidual', 'defaultroleparent');
                    $reply['parentrole'] = $parentrole;
                    $reply['studentcontext'] = $studentcontext->id;
                    $reply['orgid'] = $orgid;

                    foreach($entries AS $user) {
                        $hasrole = $DB->get_record('role_assignments', array('userid' => $user->id, 'roleid' => $parentrole, 'contextid' => $studentcontext->id));
                        if (isset($hasrole->id) && $hasrole->id > 0) {
                            $user->isparent = true;
                            $parents[] = $user;
                        } else {
                            $user->isparent = false;
                            $users[] = $user;
                        }
                    }
                    $reply['users'] = array_merge($parents, $users);
                } else {
                    $reply['users'] = $entries;
                }
                $reply['status'] = 'ok';
            }
        break;
        case 'adduser_anonymous':
            $maximum = 50; // How many accounts can be created at once.
            $orgid = optional_param('orgid', 0, PARAM_INT);
            $role = optional_param('role', '', PARAM_TEXT);
            $bunch = optional_param('bunch', '', PARAM_TEXT);
            $amount = optional_param('amount', 0, PARAM_INT);

            $success = 0; $failed = 0;

            if (block_eduvidual::get('orgrole') == 'Manager' || block_eduvidual::get('role') == 'Administrator') {
                if ($amount <= $maximum) {
                    require_once($CFG->dirroot . '/user/lib.php');
                    $colors = file($CFG->dirroot . '/blocks/eduvidual/templates/names.colors', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $animals = file($CFG->dirroot . '/blocks/eduvidual/templates/names.animals', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $pattern = 'e-' . date("Ym") . '-';
                    $usernameformat= $pattern . '%1$04d';
                    $domain = str_replace(array('https://', 'http://', 'www.'), '', $CFG->wwwroot);
                    $userbunch = new stdClass();
                    $userbunch->orgid = $orgid;
                    $userbunch->bunch = $bunch;

                    $lasts = $DB->get_records_sql('SELECT username FROM {user} WHERE username LIKE ? ORDER BY username DESC LIMIT 0,1', array($pattern . '%'));
                    if (count($lasts) > 0) {
                        foreach($lasts AS $last){
                            $usernumber = intval(str_replace($pattern, '', $last->username)) + 1;
                        }
                    } else {
                        $usernumber = 1;
                    }
                    for ($a = 0; $a < $amount; $a++) {
                        $color_key = array_rand($colors, 1);
                        $animal_key = array_rand($animals, 1);

                        $u = new stdClass();
                        $u->confirmed = 1;
                        $u->mnethostid = 1;
                        $u->username = sprintf($usernameformat, $usernumber++);
                        $u->firstname = $colors[$color_key];
                        $u->lastname = $animals[$animal_key];
                        $u->email = $u->username . '@doesnotexist.' . $domain;
                        $u->auth = 'manual';
                        $u->lang = 'de';
                        $u->calendartype = 'gregorian';

                        $u->id = user_create_user($u, false, false);
                        $u->secret = block_eduvidual::get_user_secret($u->id);
                        $user = $DB->get_record('user', array('id' => $u->id));
                        update_internal_user_password($user, $u->secret, false);

                        if (!empty($bunch)) {
                            $userbunch->bunch = $bunch;
                            $userbunch->userid = $u->id;
                            $DB->insert_record('block_eduvidual_userbunches', $userbunch);
                            block_eduvidual_lib_enrol::bunch_set($u->id, $org, $userbunch->bunch);
                        }

                        require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                        block_eduvidual_lib_enrol::role_set($u->id, $orgid, $role);
                        block_eduvidual_lib_enrol::choose_background($u->id);
                        // Trigger event.
                        \core\event\user_created::create_from_userid($u->id)->trigger();
                        if ($u->id > 0) {
                            $success++;
                        } else {
                            $failed++;
                        }
                    }
                    $reply['success'] = $success;
                    $reply['failed'] = $failed;
                    if ($failed == 0) { $reply['status'] = 'ok'; }
                } else {
                    $reply['error'] = get_string('manage:createuseranonymous:exceededmax:text', 'block_eduvidual', array('maximum' => $maximum));
                }
            } else {
                $reply['error'] = get_string('access_denied', 'block_eduvidual');
            }
        break;
		case 'adduser':
			$secrets = explode(' ', optional_param('secret', '', PARAM_TEXT));
        case 'setuserrole':
            if (!isset($secrets)) {
                $secrets = optional_param_array('secrets', NULL, PARAM_TEXT);
            }
            $reply['org'] = $org;
            $reply['updated'] = array();
            $reply['failed'] = array();
            $reply['enrolments'] = array();
            $reply['unenrolments'] = array();

            foreach ($secrets AS $secret_) {
                $secret = explode('#', $secret_);
                $chkuser = $DB->get_record('user', array('id' => $secret[0]));
                if (isset($chkuser) && $chkuser->id == $secret[0]) {
                    $dbsecret = block_eduvidual::get_user_secret($secret[0]);
        			if ($dbsecret == $secret[1]) {
        				$roles = array('Manager', 'Teacher', 'Student', 'Parent', 'remove');
        				$role = optional_param('role', '', PARAM_TEXT);
                        if (in_array($role, $roles)) {
                            require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                            $reply = array_merge($reply, block_eduvidual_lib_enrol::role_set($secret[0], $org, $role));
                            $reply['updated'][] = $secret_;
                        } else {
                            $reply['invalid_role_' . $role] = true;
                        }
        			} else {
                        $reply['failed'][] = $secret_;
        			}
                } else {
                    $reply['failed'][] = $secret_;
                }
            }

            if (count($reply['failed']) == 0) {
                $reply['status'] = 'ok';
            }
        break;
        case 'customcss':
            $org->customcss = optional_param('customcss', '', PARAM_TEXT);
            if ($DB->execute('UPDATE {block_eduvidual_org} SET customcss=? WHERE id=?', array($org->customcss, $org->id))) {
                $reply['status'] = 'ok';
            } else {
                $reply['error'] = 'db_error';
            }
        break;
        case 'force_enrol':
            $courseid = optional_param('courseid', 0, PARAM_INT);
            if ($courseid > 0) {
                $org = block_eduvidual::set_org_by_courseid($courseid);
                if (!empty($org->orgid) && (block_eduvidual::get('orgrole') == 'Manager' || block_eduvidual::get('role') == 'Administrator')) {
                    require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                    block_eduvidual_lib_enrol::course_manual_enrolments(array($courseid), array($USER->id), get_config('block_eduvidual', 'defaultroleteacher'));
                    $reply['status'] = 'ok';
                } else {
                    $reply['error'] = get_string('access_denied', 'block_eduvidual');
                }
            } else {
                $reply['error'] = 'no_courseid';
            }
        break;
        case 'maildomain':
            if (is_siteadmin()) {
                $maildomain = strtolower(optional_param('maildomain', '', PARAM_TEXT));
                $type = optional_param('type', '', PARAM_TEXT);
                if ($maildomain == '' || strlen($maildomain) > 2) {
                    if ($maildomain == '' || substr($maildomain, 0, 1) == '@') {
                        if (true) { // Disabled check for invalid characters
                            if (block_eduvidual::get('role') == 'Administrator') {
                                $field = 'maildomain' . $type;
                                $org->{$field} = $maildomain;
                                $reply['org'] = $org;
                                $DB->update_record('block_eduvidual_org', $org);
                                $reply['status'] = 'ok';
                            } else {
                                $reply['error'] = get_string('access_denied', 'block_eduvidual');
                            }
                        } else {
                            $reply['error'] = 'invalid_character';
                        }

                    } else {
                        $reply['error'] = 'start_with_at';
                    }
                }
            } else {
                $reply['error'] = 'not_siteadmin';
            }
        break;
        case 'maildomain_apply':
            if (is_siteadmin()) {
                require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                $types = array('maildomain', 'maildomainteacher');
                $reply['updated'] = array(
                    'Student' => 0,
                    'Teacher' => 0,
                );
                foreach ($types AS $type) {
                    // Now we look for all users of that domain.
                    if (empty($org->{$type})) continue;
                    $domains = explode(',', $org->{$type});
                    $role = ($type == 'maildomainteacher') ? 'Teacher' : 'Student';
                    $sql = "SELECT id
                                FROM {user}
                                WHERE email LIKE ?";
                    foreach ($domains AS $domain) {
                        $users = $DB->get_records_sql($sql, array('%' . $domain));
                        foreach ($users AS $user) {
                            $hasrole = $DB->get_record('block_eduvidual_orgid_userid', array('orgid' => $org->orgid, 'userid' => $user->id));
                            if (empty($hasrole->id)) {
                                $reply['updated'][$role]++;
                                \block_eduvidual_lib_enrol::role_set($user->id, $org, $role);
                            }
                        }
                    }
                }
            } else {
                $reply['error'] = 'not_siteadmin';
            }
        break;
        case 'setpwreset':
            if (!isset($secrets)) {
                $secrets = optional_param_array('secrets', NULL, PARAM_TEXT);
            }
            $resetfor = array("manual", "email");
            $reply['org'] = $org;
            $reply['updated'] = array();
            $reply['failed'] = array();

            foreach ($secrets AS $secret_) {
                $secret = explode('#', $secret_);
                $chkuser = $DB->get_record('user', array('id' => $secret[0]));
                if (isset($chkuser) && $chkuser->id == $secret[0]) {
                    $dbsecret = block_eduvidual::get_user_secret($secret[0]);
        			if ($dbsecret == $secret[1]) {
                        // Secret is ok, check account type.
                        if (in_array($chkuser->auth, $resetfor)) {
                            update_internal_user_password($chkuser, $secret[1], false);
                            $reply['updated'][] = fullname($chkuser) . ' => <strong>' . $secret[1] . '</strong>';
                        } else {
                            $reply['failed'][] = $secret_ . ': ' . $chkuser->auth;
                        }
        			} else {
                        $reply['failed'][] = $secret_ . ': wrong secret';
        			}
                } else {
                    $reply['failed'][] = $secret_ . ': no such user';
                }
            }

            if (count($reply['failed']) == 0) {
                $reply['status'] = 'ok';
            }
        break;
		case 'setuserrole_search':
            $minimum = 2;
			$search = optional_param('search', '', PARAM_TEXT);
            $reply['users'] = array();
			if (strlen($search) > $minimum) {
				$search = "%" . $search . "%";
                $CONCAT = 'CONCAT("[",ou.role,"] ",u.firstname," ",u.lastname,IF(ub.bunch IS NULL,"",CONCAT(" #",ub.bunch)))';
                if (false && block_eduvidual::get('role') == 'Administrator') {
                    $users = $DB->get_records_sql('SELECT u.id,u.email,' . $CONCAT . ' AS userfullname FROM {user} AS u INNER JOIN {block_eduvidual_orgid_userid} AS ou ON u.id=ou.userid LEFT JOIN {block_eduvidual_userbunches} AS ub ON u.id=ub.userid WHERE ' . $CONCAT . ' LIKE ? AND u.suspended=0', array($search));
                } else {
                    $users = $DB->get_records_sql('SELECT u.id,u.email,' . $CONCAT . ' AS userfullname FROM {user} AS u INNER JOIN {block_eduvidual_orgid_userid} AS ou ON u.id=ou.userid LEFT JOIN {block_eduvidual_userbunches} AS ub ON u.id=ub.userid WHERE ou.orgid=? AND ' . $CONCAT . ' LIKE ? AND u.suspended=0', array($org->orgid, $search));
                }
                require_once($CFG->dirroot . '/user/profile/lib.php');
    			foreach($users AS $user) {
    				profile_load_data($user);
    				$item = array(
    					"id" => $user->id,
    					"secret" => $user->id . '#' . $user->profile_field_secret,
    					"userfullname" => $user->userfullname,
    					"email" => $user->email
    				);
                    /*
                    if (block_eduvidual::get('role') == 'Administrator') {
                        $currentrole = $DB->get_record('block_eduvidual_orgid_userid', array('orgid' => $org->orgid, 'userid' => $user->id));
                        if (empty($currentrole->role)) {
                            $item['userfullname'] = '! ' . $item['userfullname'];
                        }
                    }
                    */
                    $reply['users'][] = $item;
    			}
			} else {
                $reply['users'][] = array(
                    "id" => 0,
                    "secret" => '#',
                    "userfullname" => get_string('minimum_x_chars', 'block_eduvidual', $minimum),
                    "email" => '',
                );
            }

			$reply['status'] = 'ok';
		break;
    }
}

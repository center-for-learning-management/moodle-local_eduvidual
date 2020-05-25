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

$current_orgid = optional_param('orgid', 0, PARAM_INT);
$orgas = block_eduvidual::get_organisations('*');
if (count($orgas) == 1 && $current_orgid == 0) {
	foreach($orgas AS $orga) {
		$current_orgid = $orga->orgid;
	}
}
$org = block_eduvidual::get_organisations_check($orgas, $current_orgid);
if (isset($org->orgid) && $org->orgid > 0) {
    block_eduvidual::set_org($org->orgid);
}
$act = optional_param('act', '', PARAM_TEXT);
switch ($act) {
    case 'accesscode':
        if (isguestuser($USER)) {
            $reply['error'] = 'guestuser:nopermission';
        } else {
            $orgid = optional_param('orgid', 0, PARAM_INT);
            $code = optional_param('code', '', PARAM_TEXT);
            $entries = $DB->get_records('block_eduvidual_org_codes', array('orgid' => $orgid, 'code' => $code));
            if (count($entries) > 0) {
                $role = '';
                foreach($entries AS $entry) {
                    if ($entry->maturity > time()) {
                        $role = $entry->role;
                    }
                }
                if (!empty($role)) {
                    // Code is ok - enrol user
                    require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                    $reply['enrolment'] = block_eduvidual_lib_enrol::role_set($USER->id, $orgid, $role);
                    $reply['orgid'] = $orgid;
                    $reply['status'] = 'ok';
                } else {
                    $reply['error'] = 'accesscard:code_obsolete';
                }
            } else {
                $reply['error'] = 'accesscard:code_invalid';
            }
        }
    break;
    case 'autologin':
        $token = optional_param('token', '', PARAM_ALPHANUM);
        $userid = $USER->id;
        if (!empty($token) || $userid > 0) {
            if ($userid == 0) {
                // Retrieve userid from token
                $conditions = array(
                    'token' => $token,
                );
                $et = $DB->get_record('external_tokens', $conditions);
                if (isset($et->token) && isset($et->userid) && $et->userid > 0) {
                    $userid = $et->userid;
                }
            }

            if ($userid > 0) {
                $reply['userid'] = $userid;
                delete_user_key('tool_mobile', $userid);
                // Create a new key.
                $iprestriction = getremoteaddr();
                $validuntil = time() + 60;
                $reply['key'] = create_user_key('tool_mobile', $userid, null, $iprestriction, $validuntil);
                $autologinurl = new moodle_url("/$CFG->admin/tool/mobile/autologin.php");
                $reply['autologinurl'] = $autologinurl->out(false);
                $reply['status'] = 'ok';
            } else  {
                $reply['error'] = 'token_seems_invalid';
            }
        } else  {
            $reply['error'] = 'no_token';
        }
    break;
    case 'categories':
        $categoryid = optional_param('categoryid', $org->categoryid, PARAM_INT);
        $reply['category'] = $DB->get_record('course_categories', array('id' => $categoryid));
        $reply['parent'] = $DB->get_record('course_categories', array('id' => $reply['category']->parent));
        $reply['categories'] = array();
        $reply['courses'] = array();
        $reply['status'] = 'ok';
        $reply['role'] = block_eduvidual::get('role');
        $reply['orgrole'] = block_eduvidual::get('orgrole');
        $reply['orgid'] = $org->orgid;
        $reply['orgcategoryid'] = $org->categoryid;
        $cats = $DB->get_records_sql('SELECT * FROM {course_categories} WHERE parent=? AND path LIKE ? ORDER BY name ASC', array($categoryid, '/' . $org->categoryid . '%'));
        foreach($cats AS $cat) {
            if ($cat->visible == 0) {
                if (!in_array(block_eduvidual::get('role'), array('Administrator','Manager'))) {
                    continue;
                }
            }
            $reply['categories'][] = $cat;
        }
        $courses = $DB->get_records_sql('SELECT * FROM {course} WHERE category=? ORDER BY fullname ASC', array($categoryid));
        foreach($courses AS $course) {
            if ($course->visible == 0) {
                $context = context_course::instance($course->id);
                if (!has_capability('moodle/course:update', $context) && !in_array(block_eduvidual::get('role'), array('Administrator','Manager'))) {
                    continue;
                }
            }
            // Create a course_in_list object to use the get_course_overviewfiles() method.
            require_once($CFG->libdir . '/coursecatlib.php');
            //$reply['coursecatlib'] = $CFG->libdir . '/coursecatlib.php';
            $_course = new course_in_list($course);

            $course->image = '';
            foreach ($_course->get_course_overviewfiles() as $file) {
                if (@$file->is_valid_image()) {
                    $imagepath = '/' . $file->get_contextid() .
                            '/' . $file->get_component() .
                            '/' . $file->get_filearea() .
                            $file->get_filepath() .
                            $file->get_filename();
                    $course->image = file_encode_url($CFG->wwwroot . '/pluginfile.php', $imagepath,
                            false);
                    // Use the first image found.
                    break;
                }
            }
            $reply['courses'][] = $course;
        }
    break;
    case 'courselist_sethidden':
        if (isguestuser($USER)) {
            $reply['error'] = 'guestuser:nopermission';
        } else {
            $courseid = optional_param('courseid', 0, PARAM_INT);
            $setto = optional_param('setto', 0, PARAM_INT);
            $data = new stdClass();
            $data->userid = $USER->id;
            $data->courseid = $courseid;
            $reply['setto'] = $setto;
            $reply['data'] = $data;
            if ($courseid > 0) {
                if ($setto) {
                    // Set hidden
                    $DB->insert_record('block_eduvidual_courseshow', $data);
                } else {
                    // Remove hidden
                    $DB->delete_records('block_eduvidual_courseshow', (array)$data);
                }
                $reply['status'] = 'ok';
            }
        }
    break;
    case 'defaultorg':
        if (isguestuser($USER)) {
            $reply['error'] = 'guestuser:nopermission';
        } else {
            $validorgas = block_eduvidual::get_organisations('*');
            $orgid = optional_param('orgid', 0, PARAM_INT);
            $org = block_eduvidual::get_organisations_check($validorgas, $orgid);

            if (isset($org) && $org->orgid == $orgid) {
				$chk = set_user_preference('block_eduvidual_defaultorg', $orgid);
                $reply['status'] = ($chk) ? 'ok' : 'error';
            } else {
                $reply['error'] = 'invalid_org';
            }
        }
    break;
    case 'login':
        $token = optional_param('token', '', PARAM_ALPHANUM);
        $userid = optional_param('userid', 0, PARAM_INT);
        $entry = $DB->get_record('block_eduvidual_usertoken', array('token' => $token, 'userid' => $userid));
        if ($entry && $entry->userid > 0) {
            $entry->used = time();
            $DB->update_record('block_eduvidual_usertoken', $entry);

            // Do the login magic
            $user = core_user::get_user($entry->userid, '*', MUST_EXIST);
            core_user::require_active_user($user, true, true);
            // Do the user log-in.
            if (!$user = get_complete_user_data('id', $user->id)) {
                throw new moodle_exception('cannotfinduser', '', '', $user->id);
            }
            complete_user_login($user);
            \core\session\manager::apply_concurrent_login_limit($user->id, session_id());
            $reply['status'] = 'ok';
            $reply['userid'] = $USER->id;
            block_eduvidual::set_is_app(1);
        } else {
            $reply['error'] = 'invalid token';
        }
    break;
    case 'seteditor':
        if (isguestuser($USER)) {
            $reply['error'] = 'guestuser:nopermission';
        } else {
            $editor = optional_param('editor', '', PARAM_TEXT);
            $valid = array('', 'atto', 'tinymce', 'textarea');
            if (!in_array($editor, $valid)) {
                $reply['error'] = 'invalid choice';
            } else {
                $entry = $DB->get_record('user_preferences', array('userid' => $USER->id, 'name' => 'htmleditor'));
                if (isset($entry->id) && $entry->id > 0) {
                    $entry->value = $editor;
                    $DB->update_record('user_preferences', $entry);
                    $reply['status'] = 'ok';
                } else {
                    $DB->insert_record('user_preferences', (object) array('userid' => $USER->id, 'name' => 'htmleditor', 'value' => $editor));
                    $reply['status'] = 'ok';
                }
            }
        }
    break;
    case 'whoami':
        $reply['status'] = 'ok';
        $reply['userid'] = $USER->id;
    break;
}

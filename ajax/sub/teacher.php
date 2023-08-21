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

$orgid = required_param('orgid', PARAM_INT);
$org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));
$context = \context_coursecat::instance($org->categoryid);
require_capability('moodle/category:viewcourselist', $context);

$act = optional_param('act', '', PARAM_TEXT);
switch ($act) {
    case 'course_hideshow':
    case 'course_remove':
        $courseid = optional_param('courseid', 0, PARAM_INT);
        $context = \context_course::instance($courseid);
        $canedit = has_capability('moodle/course:update', $context) || is_siteadmin();
        if ($canedit) {
            $course = $DB->get_record('course', array('id' => $courseid));
            $reply['course_before'] = $course;
            require_once($CFG->dirroot . '/course/lib.php');
            switch ($act) {
                case 'course_hideshow':
                    $course->visible = ($course->visible == 1) ? 0 : 1;
                    update_course($course);
                    $reply['status'] = 'ok';
                    break;
                case 'course_remove':
                    delete_course($course);
                    $reply['status'] = 'ok';
                    break;
            }
            $reply['course_after'] = $course;
        } else {
            $reply['error'] = get_string('access_denied', 'local_eduvidual');
        }
        break;
    case 'createcourse_basements':
        $reply['status'] = 'ok';
        $reply['basements'] = \local_eduvidual\lib_enrol::get_course_basements('all');
        $orgid = optional_param('orgid', 0, PARAM_INT);
        $membership = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $USER->id));
        $reply['canmanage'] = is_siteadmin() || (isset($membership->role) && $membership->role == 'Manager');
        break;
    case 'createcourse_now':
        $categoryid = optional_param('categoryid', 0, PARAM_INT);
        $category = $DB->get_record('course_categories', array('id' => $categoryid));
        $path = explode("/", $category->path);
        $reply['msgs'] = array();
        if ($path[1] == $org->categoryid) {
            if (in_array(\local_eduvidual\locallib::get_orgrole($org->orgid), array('Manager', 'Teacher')) || is_siteadmin()) {
                // Now check if basement is valid
                $basement = optional_param('basement', 0, PARAM_INT);
                if (\local_eduvidual\lib_enrol::is_valid_course_basement('all', $basement)) {
                    // Create course here
                    $fullname = optional_param('name', '', PARAM_TEXT);
                    if (strlen($fullname) > 5) {
                        $shortname = "[" . $USER->id . "-" . date('YmdHis') . "] " . strtolower($fullname);
                        if (strlen($shortname) > 30)
                            $shortname = substr($shortname, 0, 30);
                        $course = \local_eduvidual\lib_helper::duplicate_course($basement, $fullname, $shortname, $categoryid, 1);

                        if (!empty($course->id)) {
                            $context = \context_course::instance($course->id);
                            $role = get_config('local_eduvidual', 'defaultroleteacher');
                            $enroluser = optional_param('setteacher', 0, PARAM_INT);
                            if (empty($enroluser) || $enroluser == 0)
                                $enroluser = $USER->id;
                            $reply['enrolments'][] = 'course: user ' . $enroluser . ' roleid ' . $role . ' courseid ' . $course->id;
                            \local_eduvidual\lib_enrol::course_manual_enrolments(array($course->id), array($enroluser), $role);
                            // Set the start date of this course to sep 1st of the school year
                            $course = $DB->get_record('course', array('id' => $course->id));
                            $course->startdate = (date("m") < 6) ? strtotime((date("Y") - 1) . '0901000000') : strtotime(date("Y") . '0901000000');
                            $course->enddate = (date("m") < 6) ? strtotime((date("Y")) . '0831000000') : strtotime((date("Y") + 1) . '0831000000');
                            $DB->update_record('course', $course);

                            $reply['course'] = $course;
                            $reply['status'] = 'ok';
                        } else {
                            $reply['error'] = 'error_creating_course';
                        }
                    } else {
                        $reply['error'] = 'name_too_short';
                    }
                } else {
                    $reply['error'] = 'invalid_basement';
                }

            } else {
                $reply['error'] = get_string('access_denied', 'local_eduvidual');

            }
        } else {
            $reply['error'] = 'category_does_not_belong_to_org';
        }
        break;
    case 'createcourse_category':
        $categoryid = optional_param('categoryid', 0, PARAM_INT);
        $category = $DB->get_record('course_categories', array('id' => $categoryid));
        $path = explode("/", $category->path);
        if ($path[1] == $org->categoryid) {
            if (in_array(\local_eduvidual\locallib::get_orgrole($org->orgid), array('Manager', 'Teacher')) || is_siteadmin()) {
                $parent = $DB->get_record('course_categories', array('id' => $category->parent));
                $reply['parent'] = $parent;
                $reply['category'] = $category;
                $reply['children'] = array();
                $cs = $DB->get_records('course_categories', array('parent' => $categoryid));
                foreach ($cs as $c) {
                    $reply['children'][] = $c;
                }
                $reply['status'] = 'ok';
            } else {
                $reply['error'] = get_string('access_denied', 'local_eduvidual');
            }
        } else {
            $reply['error'] = 'category_does_not_belong_to_org';
        }
        break;
    case 'createcourse_create':
        $categoryid = optional_param('categoryid', 0, PARAM_INT);
        $category = $DB->get_record('course_categories', array('id' => $categoryid));
        $path = explode("/", $category->path);
        if ($path[1] == $org->categoryid) {
            if (in_array(\local_eduvidual\locallib::get_orgrole($org->orgid), array('Manager', 'Teacher')) || is_siteadmin()) {
                // You can savely create the course here
                $reply['status'] = 'ok';
            } else {
                $reply['error'] = get_string('access_denied', 'local_eduvidual');
            }
        } else {
            $reply['error'] = 'category_does_not_belong_to_org';
        }
        break;
    case 'createcourse_loadteacher':
        $orgid = optional_param('orgid', 0, PARAM_INT);
        $search = optional_param('search', '', PARAM_TEXT);

        if (strlen($search) > 3) {
            $search = "%" . $search . "%";
        }
        $reply['users'] = array();
        $CONCAT = 'CONCAT("[",ou.role,"] ",u.firstname," ",u.lastname)';
        $users = $DB->get_records_sql('SELECT u.id,u.email,' . $CONCAT . ' AS userfullname FROM {user} AS u,{local_eduvidual_orgid_userid} AS ou WHERE u.id=ou.userid AND ou.orgid=? AND ' . $CONCAT . ' LIKE ?', array($orgid, $search));
        require_once($CFG->dirroot . '/user/profile/lib.php');
        foreach ($users as $user) {
            profile_load_data($user);
            $reply['users'][] = array(
                "id" => $user->id,
                "userfullname" => $user->userfullname,
                "email" => $user->email,
            );
        }
        $reply['status'] = 'ok';
        break;
    case 'user_search':
        if (!in_array(\local_eduvidual\locallib::get_orgrole($org->orgid), array('Manager', 'Teacher')) && !is_siteadmin()) {
            $reply['error'] = 'No_permission';
        } else {
            $courseid = optional_param('courseid', 0, PARAM_INT);
            $context = \context_course::instance($courseid);
            $type = optional_param('type', '', PARAM_TEXT);
            $searchfor = '%' . optional_param('searchfor', '', PARAM_TEXT) . '%';
            $CONCAT = 'CONCAT(u.firstname," ",u.lastname," (",u.email,")")';
            $reply['users'] = array();
            if ($type == 'orgusers') {
                $users = $DB->get_records_sql('SELECT u.id,u.email,' . $CONCAT . ' AS userfullname FROM {user} AS u,{local_eduvidual_orgid_userid} AS ou WHERE u.id=ou.userid AND ou.orgid=? AND ' . $CONCAT . ' LIKE ? ORDER BY u.lastname ASC,u.firstname ASC', array($org->orgid, $searchfor));
            } else {
                $users = array();
                $userids = array_keys(get_enrolled_users($context));
                foreach ($userids as $userid) {
                    $entry = $DB->get_records_sql('SELECT u.id,u.email,' . $CONCAT . ' AS userfullname FROM {user} AS u,{local_eduvidual_orgid_userid} AS ou WHERE u.id=ou.userid AND ou.orgid=? AND ' . $CONCAT . ' LIKE ? AND ou.userid=? ORDER BY u.lastname ASC,u.firstname ASC', array($org->orgid, $searchfor, $userid));
                    foreach ($entry as $e) {
                        $users[$e->id] = $e;
                    }
                }
            }
            if (count(array_keys($users)) > 200) {
                $fakeuser = new stdClass();
                $fakeuser->userid = 0;
                $fakeuser->email = '';
                $fakeuser->name = get_string('courses:enrol:searchtoomuch', 'local_eduvidual');
                $reply['users'] = array($fakeuser);
            } else {
                /*
                $defaultroleparent = get_config('local_eduvidual', 'defaultroleparent');
                $defaultrolestudent = get_config('local_eduvidual', 'defaultrolestudent');
                $defaultroleteacher = get_config('local_eduvidual', 'defaultroleteacher');
                */
                foreach ($users as $user) {
                    $reply['users'][] = array(
                        "userid" => $user->id,
                        "name" => $user->userfullname,
                        "email" => $user->email,
                    );
                }
            }
            $reply['status'] = 'ok';
        }
        break;
    case 'user_set':
        if (!in_array(\local_eduvidual\locallib::get_orgrole($org->orgid), array('Manager', 'Teacher')) && !is_siteadmin()) {
            $reply['error'] = get_string('access_denied', 'local_eduvidual');
        } else {
            $courseid = optional_param('courseid', 0, PARAM_INT);
            $course = $DB->get_record('course', array('id' => $courseid));
            if (isset($course->id) && $course->id > 0) {
                $category = $DB->get_record('course_categories', array('id' => $course->category));
                $path = explode('/', $category->path);
                if ($path[1] == $org->categoryid) {
                    $context = \context_course::instance($course->id);
                    $canedit = has_capability('moodle/course:update', $context) || is_siteadmin() || \local_eduvidual\locallib::get('orgrole') == 'Manager';
                    if ($canedit) {
                        $roleid = 0;
                        switch (optional_param('role', '', PARAM_TEXT)) {
                            case 'Parent':
                                $roleid = get_config('local_eduvidual', 'defaultroleparent');
                                break;
                            case 'Student':
                                $roleid = get_config('local_eduvidual', 'defaultrolestudent');
                                break;
                            case 'Teacher':
                                $roleid = get_config('local_eduvidual', 'defaultroleteacher');
                                break;
                            case 'remove':
                                $roleid = -1;
                                break;
                        }
                        if ($roleid > 0 || $roleid == -1) {
                            $userids = optional_param_array('userids', 0, PARAM_INT);
                            $userinorg = array();
                            foreach ($userids as $userid) {
                                $chk = $DB->get_record('local_eduvidual_orgid_userid', array('userid' => $userid));
                                if (isset($chk->userid) && $chk->userid == $userid) {
                                    $userinorg[] = $userid;
                                }
                            }
                            $reply['failures'] = \local_eduvidual\lib_enrol::course_manual_enrolments(array($courseid), $userinorg, $roleid);
                            $reply['updates'] = array(array($courseid), $userinorg, $roleid);
                            $reply['updateduserids'] = $userinorg;
                            $reply['status'] = 'ok';
                        } else {
                            $reply['error'] = 'Invalid_role';
                        }
                    } else {
                        $reply['error'] = get_string('access_denied', 'local_eduvidual');
                    }
                } else {
                    $reply['error'] = 'Course_does_not_belong_to_org';
                }
            } else {
                $reply['error'] = 'No_such_course';
            }
        }
        break;
}

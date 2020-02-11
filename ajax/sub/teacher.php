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
$orgas = block_eduvidual::get_organisations('Teacher');
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
        case 'course_hideshow':
        case 'course_remove':
            $courseid = optional_param('courseid', 0, PARAM_INT);
            $context = context_course::instance($courseid);
            $canedit = has_capability('moodle/course:update', $context) || block_eduvidual::get('role') == 'Administrator';
            if ($canedit) {
                $course = $DB->get_record('course', array('id' => $courseid));
                $reply['course_before'] = $course;
                require_once($CFG->dirroot . '/course/lib.php');
                switch($act) {
                    case 'course_hideshow':
                        $course->visible = ($course->visible == 1)?0:1;
                        update_course($course);
                        $reply['status'] = 'ok';
                    break;
                    case 'course_remove':
                        delete_course($course);
                        $trashcategory = get_config('block_eduvidual', 'trashcategory');
                        if ($trashcategory > 0) {
                            $course->category = $trashcategory;
                            $trashbinstring = '(' . get_string('manage:archive:trashbin', 'block_eduvidual') . ') ';
                            $course->fullname = $trashbinstring . str_replace($trashbinstring, '', $course->fullname);
                            update_course($course);
                        } else {
                            delete_course($course);
                        }
                        $reply['status'] = 'ok';
                    break;
                }
                $reply['course_after'] = $course;
            } else {
                $reply['error'] = get_string('access_denied', 'block_eduvidual');
            }
        break;
        case 'createcourse_basements':
            $reply['status'] = 'ok';
            require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
            $reply['basements'] = block_eduvidual_lib_enrol::get_course_basements('all');
            $orgid = optional_param('orgid', 0, PARAM_INT);
            $membership = $DB->get_record('block_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $USER->id));
            $reply['canmanage'] = block_eduvidual::get('role') == 'Administrator' || (isset($membership->role) && $membership->role == 'Manager');
        break;
        case 'createcourse_now':
            $categoryid = optional_param('categoryid', 0, PARAM_INT);
            $category = $DB->get_record('course_categories', array('id' => $categoryid));
            $path = explode("/", $category->path);
            $reply['msgs'] = array();
            if ($path[1] == $org->categoryid) {
                if (in_array(block_eduvidual::get('orgrole'), array('Administrator', 'Manager', 'Teacher')) || block_eduvidual::get('role') == 'Administrator') {
                    // Now check if basement is valid
                    require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                    $basement = optional_param('basement', 0, PARAM_INT);
                    if(block_eduvidual_lib_enrol::is_valid_course_basement('all', $basement)){
                        // Create course here
                        $fullname = optional_param('name', '', PARAM_TEXT);
                        if (strlen($fullname) > 5) {
                            require_once($CFG->dirroot . '/course/externallib.php');
                            $shortname = "[" . $USER->id . "-" . date('YmdHis') . "] " . strtolower($fullname);
                            if (strlen($shortname) > 30) $shortname = substr($shortname, 0, 30);
                            // Grant a role that allows course duplication in source and target category
                            $basecourse = $DB->get_record('course', array('id' => $basement));
                            $sourcecontext = context_coursecat::instance($basecourse->category);
                            $targetcontext = context_coursecat::instance($category->id);
                            $roletoassign = 1; // Manager
                            $revokesourcerole = true;
                            $revoketargetrole = true;
                            $roles = get_user_roles($sourcecontext, $USER->id, false);
                            foreach($roles AS $role) {
                                if ($role->roleid == $roletoassign) {
                                    // User had this role before - we do not revoke!
                                    $revokesourcerole = false;
                                    $reply['msgs'][] = 'Has already been manager in source';
                                }
                            }
                            $roles = get_user_roles($targetcontext, $USER->id, false);
                            foreach($roles AS $role) {
                                if ($role->roleid == $roletoassign) {
                                    // User had this role before - we do not revoke!
                                    $revoketargetrole = false;
                                    $reply['msgs'][] = 'Has already been manager in target';
                                }
                            }
                            $reply['msgs'][] = 'Assigning role manager in source/target';
                            role_assign($roletoassign, $USER->id, $sourcecontext->id);
                            role_assign($roletoassign, $USER->id, $targetcontext->id);

                            // DO THE MAGIC! CLONE THE COURSE!
                            $course = core_course_external::duplicate_course($basement, $fullname, $shortname, $categoryid);
                            // ATTENTION - Revoking the role is MANDATORY and is done AFTER the roles are set in the course!
                            if (isset($course['id']) && $course['id'] > 0) {
                                $context = context_course::instance($course['id']);
                                $role = get_config('block_eduvidual', 'defaultroleteacher');
                                $enroluser = optional_param('setteacher', 0, PARAM_INT);
                                if (empty($enroluser) || $enroluser == 0) $enroluser = $USER->id;
                                $reply['enrolments'][] = 'course: user ' . $enroluser . ' roleid ' . $role . ' courseid ' . $course['id'];
                                block_eduvidual_lib_enrol::course_manual_enrolments(array($course['id']), array($enroluser), $role);
                                // Set the start date of this course to sep 1st of the school year
                                $course = $DB->get_record('course', array('id' => $course['id']));
                                $course->startdate = (date("m") < 6)?strtotime((date("Y")-1) . '0901000000'):strtotime(date("Y") . '0901000000');
                                $DB->update_record('course', $course);

                                $reply['course'] = $course;
                                $reply['status'] = 'ok';
                            } else {
                                $reply['error'] = 'error_creating_course';
                            }

                            // Revoke role that allows course duplication in source and target category
                            if ($revokesourcerole) {
                                role_unassign($roletoassign, $USER->id, $sourcecontext->id);
                                $reply['msgs'][] = 'Revoke role from source';
                            }
                            if ($revoketargetrole) {
                                role_unassign($roletoassign, $USER->id, $targetcontext->id);
                                $reply['msgs'][] = 'Revoke role from target';
                            }

                        } else {
                            $reply['error'] = 'name_too_short';
                        }
                    } else {
                        $reply['error'] = 'invalid_basement';
                    }

                } else {
                    $reply['error'] = get_string('access_denied', 'block_eduvidual');

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
                if (in_array(block_eduvidual::get('orgrole'), array('Administrator', 'Manager', 'Teacher')) || block_eduvidual::get('role') == 'Administrator') {
                    $parent = $DB->get_record('course_categories', array('id' => $category->parent));
                    $reply['parent'] = $parent;
                    $reply['category'] = $category;
                    $reply['children'] = array();
                    $cs = $DB->get_records('course_categories', array('parent' => $categoryid));
                    foreach($cs AS $c) {
                        $reply['children'][] = $c;
                    }
                    $reply['status'] = 'ok';
                } else {
                    $reply['error'] = get_string('access_denied', 'block_eduvidual');
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
                if (in_array(block_eduvidual::get('orgrole'), array('Administrator', 'Manager', 'Teacher'))) {
                    // You can savely create the course here
                    $reply['status'] = 'ok';
                } else {
                    $reply['error'] = get_string('access_denied', 'block_eduvidual');
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
			$users = $DB->get_records_sql('SELECT u.id,u.email,' . $CONCAT . ' AS userfullname FROM {user} AS u,{block_eduvidual_orgid_userid} AS ou WHERE u.id=ou.userid AND ou.orgid=? AND ' . $CONCAT . ' LIKE ?', array($orgid, $search));
            require_once($CFG->dirroot . '/user/profile/lib.php');
			foreach($users AS $user) {
				profile_load_data($user);
				$reply['users'][] = array(
					"id" => $user->id,
					"userfullname" => $user->userfullname,
					"email" => $user->email
				);
			}
			$reply['status'] = 'ok';
        break;
        case 'createmodule_category':
            $categoryid = optional_param('categoryid', 0, PARAM_INT);
            $category = $DB->get_record('block_eduvidual_modulescat', array('id' => $categoryid));
            $reply['parentid'] = (!isset($category->parentid) || empty($category->parentid))?-1:$category->parentid;
            $reply['category'] = $category;
            $reply['children'] = array();
            require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_helper.php');

            $cs = $DB->get_records('block_eduvidual_modulescat', array('parentid' => $categoryid, 'active' => 1));
            $reply['children'] = block_eduvidual_lib_helper::natsort($cs, 'name', 'modulescat');
            /*
            foreach($cs AS $c) {
                $reply['children'][] = $c;
            }
            */
            $reply['modules'] = array();
            $ms = $DB->get_records_sql('SELECT * FROM {block_eduvidual_modules} WHERE categoryid=? AND active=1 ORDER BY name ASC', array($categoryid));

            $reply['modules'] = block_eduvidual_lib_helper::natsort($ms, 'name', 'modules');
            /*
            foreach($ms AS $m) {
                $payload = json_decode($m->payload);
                unset($payload->defaults);
                $m->payload = json_encode($payload);
                $reply['modules'][] = $m;
            }
            */
            $reply['status'] = 'ok';
        break;
        case 'createmodule_create':
            $formdata = (object)json_decode(optional_param('formdata', '{}', PARAM_TEXT));

            $courseid = $formdata->course;
            $sectionid = $formdata->section;

            $moduleid = optional_param('moduleid', 0, PARAM_INT);

            $mod = $DB->get_record('block_eduvidual_modules', array('id' => $moduleid));
            $payload = json_decode($mod->payload);
            $missing = array();
            $customize = (isset($payload->customize)?$payload->customize:new stdClass());
            $defaults = (isset($payload->defaults)?$payload->defaults:new stdClass());

            $fields = array_keys((array)$customize);
            foreach($fields AS $field) {
                if (isset($fields[$field]->required) && $fields[$field]->required && $formdata[$field] == '') {
                    $missing[] = $field;
                }
            }
            if (count($missing) == 0) {
                $context = context_course::instance($courseid);
                if (has_capability('moodle/course:update', $context)) {
                    $section = $DB->get_record('course_sections', array('course' => $courseid, 'section' => $sectionid));
                    if (isset($section->id) && $section->id > 0) {
                        $reply['courseid'] = $courseid;
                        $reply['sectionid'] = $sectionid;
                        $reply['formdata'] = $formdata;
                        $reply['defaults'] = $defaults;

                        require_once($CFG->dirroot . '/blocks/eduvidual/classes/module_compiler.php');
                        $item = block_eduvidual_module_compiler::compile($mod->type, $formdata, $defaults);
                        $reply['item'] = $item;

                        try {
                            $mod = block_eduvidual_module_compiler::create($item);
                            if (isset($mod->course)) {
                                $DB->execute('UPDATE {block_eduvidual_modules} SET amountused=amountused+1 WHERE id=?',array($moduleid));
                                $reply['modcourse'] = $mod->course;
                            } else {
                                $reply['error'] = 'creation_failed';
                            }
                        } catch(Exception $e) {
                            $reply['exception'] = $e;
                        }
                    } else {
                        $reply['error'] = 'invalid_section';
                    }
                } else {
                    $reply['error'] = get_string('access_denied', 'block_eduvidual');
                }
                // ok - import!
            } else {
                $reply['missing'] = $missing;
            }
        break;
        case 'createmodule_modules':
            $categoryid = optional_param('categoryid', 0, PARAM_INT);
            $sortmodules = array();
            $ms = $DB->get_records_sql('SELECT * FROM {block_eduvidual_modules} WHERE categoryid=? ORDER BY name ASC', array($categoryid));
            require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_helper.php');
            $reply['modules'] = block_eduvidual_lib_helper::natsort($ms, 'name');

            $reply['status'] = 'ok';
        break;
        case 'createmodule_payload':
            $moduleid = optional_param('moduleid', 0, PARAM_INT);
            $reply['module'] = $DB->get_record('block_eduvidual_modules', array('id' => $moduleid));
            $reply['status'] = 'ok';
        break;
        case 'createmodule_search':
            $courseid = optional_param('courseid', 0, PARAM_INT);
            $sectionid = optional_param('sectionid', 0, PARAM_INT);

            $searchkeys = explode(' ', optional_param('searchkeys', '', PARAM_TEXT));
            if (optional_param('searchkeys', '', PARAM_TEXT) != '') {
                $combine = optional_param('combine', 'AND', PARAM_TEXT);
                $channels = optional_param_array('channels', array(), PARAM_TEXT);
                $channels[] = 'default';

                $reply['inputdata'] = array(
                    'courseid' => $courseid,
                    'sectionid' => $sectionid,
                    'searchkeys' => $searchkeys,
                    'channels' => $channels,
                );

                $SQL = 'SELECT package, COUNT(package) AS cnt FROM {block_edupublisher_metadata} WHERE 1=0 OR ';
                for($a = 0; $a < count($channels); $a++) {
                    for ($b = 0; $b < count($searchkeys); $b++) {
                        $SQL .= ' (`content` LIKE "%' . $searchkeys[$b] . '%" AND `active`=1)';
                        //$SQL .= ' (`field` LIKE "' . $channels[$a] . '#_%" ESCAPE("#") AND `content` LIKE "%' . $searchkeys[$b] . '%" AND `active`=1)';
                        if ($b < (count($searchkeys) -1)) {
                            $SQL .= ' ' . $combine;
                        }
                    }
                    if ($a < (count($channels) -1)) {
                        $SQL .= ' OR';
                    }
                }
                $SQL .= ' GROUP BY package ORDER BY cnt DESC';
                $relevance = $DB->get_records_sql($SQL, array());
                $reply['relevance'] = array();
                $reply['packages'] = array();
                require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
                foreach($relevance AS $relevant) {
                    if (!isset($reply['relevance'][$relevant->cnt])) {
                        $reply['relevance'][$relevant->cnt] = array();
                    }
                    $reply['relevance'][$relevant->cnt][] = $relevant->package;
                    $reply['packages'][$relevant->package] = block_edupublisher::get_package($relevant->package, true);
                }
                //$reply['sql'] = $SQL;
            }
            $reply['status'] = 'ok';
        break;
        case 'moolevels':
            $valid_moolevels = explode(',', get_config('block_eduvidual', 'moolevels'));
            $moolevels = optional_param_array('moolevels', NULL, PARAM_INT);
            if (count($valid_moolevels) > 0 && $valid_moolevels[0] != '') {
                // Check if all moolevels are valid
                for ($a = 0; $a < count($moolevels); $a++) {
                    if (!in_array($moolevels[$a], $valid_moolevels)) {
                        exit;
                    }
                }
                $context = context_system::instance();
                $roles = get_user_roles($context, $USER->id, true);
                $hasroles = array();
                foreach($roles AS $hasrole) {
                    $hasroles[] = $hasrole->roleid;
                }
                $reply['acts'] = array();
                $reply['acts'][] = $moolevels;
                foreach($valid_moolevels AS $vm) {
                    $reply['acts'][] = $vm;
                    if (in_array($vm, $hasroles) && !in_array($vm, $moolevels)) {
                        // Need to remove this role
                        $reply['acts'][] = 'Unassign ' . $vm;
                        role_unassign($vm, $USER->id, $context->id);
                    } elseif (!in_array($vm, $hasroles) && in_array($vm, $moolevels)) {
                        // Need to add this role
                        $reply['acts'][] = 'Assign ' . $vm;
                        role_assign($vm, $USER->id, $context->id);
                    }
                }
                $reply['status'] = 'ok';
            }
        break;
        case 'questioncategories':
            $questioncategories = optional_param_array('questioncategories', NULL, PARAM_INT);
            $reply['acts'] = array();
            $hascats_ = $DB->get_records('block_eduvidual_userqcats', array('userid' => $USER->id));
            $hascats = array();
            foreach($hascats_ AS $hascat) {
                if (!in_array($hascat->categoryid, $questioncategories)) {
                    $reply['acts'][] = 'Remove ' . $hascat->categoryid;
                    $DB->delete_records('block_eduvidual_userqcats', array('userid' => $USER->id, 'categoryid' => $hascat->categoryid));
                } else {
                    $hascats[] = $hascat->categoryid;
                }
            }

            $allowed_questioncategories = explode(",", get_config('block_eduvidual', 'questioncategories'));
            foreach($questioncategories AS $cat) {
                if (!in_array($cat, $allowed_questioncategories)) continue;
                if (!in_array($cat, $hascats)) {
                    $entry = new stdClass();
                    $entry->userid = $USER->id;
                    $entry->categoryid = $cat;
                    $reply['acts'][] = 'Insert ' . $cat;
                    $DB->insert_record('block_eduvidual_userqcats', $entry);
                }
            }
            $reply['status'] = 'ok';
        break;
        case 'user_search':
            if (!in_array(block_eduvidual::get('orgrole'), array('Manager', 'Teacher')) && !!in_array(block_eduvidual::get('role'), array('Administrator'))) {
                $reply['error'] = 'No_permission';
            } else {
                $courseid = optional_param('courseid', 0, PARAM_INT);
                $context = context_course::instance($courseid);
                $type = optional_param('type', '', PARAM_TEXT);
                $searchfor = '%' . optional_param('searchfor', '', PARAM_TEXT) . '%';
                $CONCAT = 'CONCAT(u.firstname," ",u.lastname," (",u.email,")")';
                $reply['users'] = array();
                if ($type == 'orgusers') {
        			$users = $DB->get_records_sql('SELECT u.id,u.email,' . $CONCAT . ' AS userfullname FROM {user} AS u,{block_eduvidual_orgid_userid} AS ou WHERE u.id=ou.userid AND ou.orgid=? AND ' . $CONCAT . ' LIKE ? ORDER BY u.lastname ASC,u.firstname ASC', array($org->orgid, $searchfor));
                } else {
                    $users = array();
                    $userids = array_keys(get_enrolled_users($context));
                    foreach($userids AS $userid) {
                        $entry = $DB->get_records_sql('SELECT u.id,u.email,' . $CONCAT . ' AS userfullname FROM {user} AS u,{block_eduvidual_orgid_userid} AS ou WHERE u.id=ou.userid AND ou.orgid=? AND ' . $CONCAT . ' LIKE ? AND ou.userid=? ORDER BY u.lastname ASC,u.firstname ASC', array($org->orgid, $searchfor, $userid));
                        foreach($entry AS $e) {
                            $users[$e->id] = $e;
                        }
                    }
                }
                if (count(array_keys($users)) > 200) {
                    $fakeuser = new stdClass();
                    $fakeuser->userid = 0;
                    $fakeuser->email = '';
                    $fakeuser->name = get_string('courses:enrol:searchtoomuch', 'block_eduvidual');
                    $reply['users'] = array($fakeuser);
                } else {
                    /*
                    $defaultroleparent = get_config('block_eduvidual', 'defaultroleparent');
                    $defaultrolestudent = get_config('block_eduvidual', 'defaultrolestudent');
                    $defaultroleteacher = get_config('block_eduvidual', 'defaultroleteacher');
                    */
                    foreach($users AS $user) {
        				$reply['users'][] = array(
        					"userid" => $user->id,
        					"name" => $user->userfullname,
        					"email" => $user->email
        				);
        			}
                }
    			$reply['status'] = 'ok';
            }
        break;
        case 'user_set':
            if (!in_array(block_eduvidual::get('orgrole'), array('Manager', 'Teacher')) && !!in_array(block_eduvidual::get('role'), array('Administrator'))) {
                $reply['error'] = get_string('access_denied', 'block_eduvidual');
            } else {
                $courseid = optional_param('courseid', 0, PARAM_INT);
                $course = $DB->get_record('course', array('id' => $courseid));
                if (isset($course->id) && $course->id > 0) {
                    $category = $DB->get_record('course_categories', array('id' => $course->category));
                    $path = explode('/', $category->path);
                    if ($path[1] == $org->categoryid) {
                        $context = context_course::instance($course->id);
                        $canedit = has_capability('moodle/course:update', $context) || block_eduvidual::get('role') == 'Administrator' || block_eduvidual::get('orgrole') == 'Manager';
                        if ($canedit) {
                            $roleid = 0;
                            switch(optional_param('role', '', PARAM_TEXT)) {
                                case 'Parent': $roleid = get_config('block_eduvidual', 'defaultroleparent'); break;
                                case 'Student': $roleid = get_config('block_eduvidual', 'defaultrolestudent'); break;
                                case 'Teacher': $roleid = get_config('block_eduvidual', 'defaultroleteacher'); break;
                                case 'remove': $roleid = -1; break;
                            }
                            if ($roleid > 0 || $roleid == -1) {
                                $userids = optional_param_array('userids', 0, PARAM_INT);
                                $userinorg = array();
                                foreach($userids AS $userid) {
                                    $chk = $DB->get_record('block_eduvidual_orgid_userid', array('userid' => $userid));
                                    if (isset($chk->userid) && $chk->userid == $userid) {
                                        $userinorg[] = $userid;
                                    }
                                }
                                require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                                $reply['failures'] = block_eduvidual_lib_enrol::course_manual_enrolments(array($courseid), $userinorg, $roleid);
                                $reply['updates'] = array(array($courseid), $userinorg, $roleid);
                                $reply['updateduserids'] = $userinorg;
        			            $reply['status'] = 'ok';
                            } else {
                                $reply['error'] = 'Invalid_role';
                            }
                        } else {
                            $reply['error'] = get_string('access_denied', 'block_eduvidual');
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
}

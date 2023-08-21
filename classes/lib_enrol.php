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
 * @copyright  2017-2020 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual;

defined('MOODLE_INTERNAL') || die;

class lib_enrol {
    private static $backgrounds = array(); // holds possible backgrounds

    /**
     * Adds a user to the cohort of an org.
     * @param userid (int) the userid.
     * @param org (object) org
     * @param cohorts (String) list comma separated.
     */
    public static function cohorts_add($userid, &$org, $cohorts) {
        global $DB;
        if (empty($cohorts))
            return;
        if (empty($org->categoryid))
            return;
        if (empty($org->categorycontext)) {
            $org->categorycontext = \context_coursecat::instance($org->categoryid);
        }
        if (empty($org->categorycontext->id))
            return;

        $cohorts = explode(',', $cohorts);

        foreach ($cohorts as $cohort) {
            if (empty($cohort))
                continue;
            $cohorto = $DB->get_record('cohort', array('contextid' => $org->categorycontext->id, 'name' => $cohort));
            if (empty($cohorto->id)) {
                $idnumber = $org->orgid . '_' . hash('crc32', $cohort);
                $cohorto = (object)array(
                    'contextid' => $org->categorycontext->id,
                    'description' => 'Automatically created cohort ' . $cohort,
                    'descriptionformat' => 1,
                    'idnumber' => $idnumber,
                    'name' => $cohort,
                    'timecreated' => time(),
                    'timemodified' => time(),
                    'visible' => 1,
                );
                $cohorto->id = $DB->insert_record('cohort', $cohorto);
            }
            $chk = $DB->get_record('cohort_members', array('cohortid' => $cohorto->id, 'userid' => $userid));
            if (empty($chk->id)) {
                $DB->insert_record('cohort_members', (object)array('cohortid' => $cohorto->id, 'userid' => $userid, 'timeadded' => time()));
            }
        }

        return true;
    }

    /**
     * Remove a user from a cohort of an org.
     * @param userid (int) the userid.
     * @param org (object) org
     * @param cohorts (String) list comma separated.
     */
    public static function cohorts_remove($userid, $org, $cohorts) {
        global $DB;
        if (empty($cohorts))
            return;
        if (empty($org->categoryid))
            return;
        $context = \context_coursecat::instance($org->categoryid);
        if (empty($context->id))
            return;

        $cohorts = explode(',', $cohorts);

        foreach ($cohorts as $cohort) {
            $cohort = $DB->get_record('cohort', array('contextid' => $context->id, 'name' => $cohort));
            if (!empty($cohort->id)) {
                $DB->delete_records('cohort_members', array('cohortid' => $cohort->id, 'userid' => $userid));
            }
        }

        return true;
    }

    /**
     * Set users role in a particular organization.
     * @param int userid
     * @param object org
     * @param String role either Manager, Teacher, Student, Parent or 'remove'.
     * @param boolean force (optional) the role to be assigned again, even if it is already set.
     */
    public static function role_set($userid, $org, $role, $force = false) {
        global $CFG, $DB, $PAGE, $USER;
        $enrol = false;
        $reply = array();

        if ($USER->id == $userid) {
            $PAGE->requires->js_call_amd("local_eduvidual/jsinjector", "clearSessionStorage", array());
        }

        // Remove could be written in various cases.
        if (strtolower($role) == 'remove')
            $role = 'remove';

        // Check if this user exists.
        self::user_exists($userid);

        // If org was given by orgid, load object from database.
        if (is_numeric($org)) {
            $org = $DB->get_record('local_eduvidual_org', array('orgid' => $org));
        }
        // We can only proceed if this is a valid org.
        if (!empty($org->orgid)) {
            // Get our current role.
            $current = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $org->orgid, 'userid' => $userid));
            if (!empty($current->role) && $current->role == 'Manager' && $role != 'Manager') {
                // We are currently Manager but should be degraded. Ensure, we are not the last manager of this org!
                $allmanagers = $DB->get_records('local_eduvidual_orgid_userid', array('orgid' => $org->orgid, 'role' => 'Manager'));
                if (count($allmanagers) == 1) {
                    $reply['error_last_manager'] = 1;
                    return $reply;
                }
            }

            // If we are removing the user, we have to remove a bunch and cohorts as well.
            if ($role == 'remove') {
                // Remove from orgcourse
                self::course_manual_enrolments(array($org->courseid, $org->supportcourseid), array($userid), -1);
                // Remove from supportcourse
                if (!empty($org->supportcourseid)) {
                    \local_eduvidual\lib_enrol::course_manual_enrolments(array($org->supportcourseid), array($userid), -1);
                }

                // Remove from orgcategory
                $orgcatcontext = \context_coursecat::instance($org->categoryid, IGNORE_MISSING);
                if (!empty($orgcatcontext->id)) {
                    // Remove alle roles that were given in coursecat.
                    $orgroles = array('manager', 'teacher', 'student', 'parent');
                    foreach ($orgroles as $orgrole) {
                        $catrole = get_config('local_eduvidual', 'defaultorgrole' . strtolower($orgrole));
                        if (!empty($catrole)) {
                            role_unassign($catrole, $userid, $orgcatcontext->id);
                        }
                    }
                    // Remove user from any cohorts in this orgcategory.
                    $cohorts = $DB->get_records('cohort', array('contextid' => $orgcatcontext->id));
                    foreach ($cohorts as $cohort) {
                        $DB->delete_records('cohort_members', array('cohortid' => $cohort->id, 'userid' => $userid));
                    }
                }

                // Now remove our eduvidual-membership.
                $DB->delete_records('local_eduvidual_orgid_userid', array('orgid' => $org->orgid, 'userid' => $userid));
            } else {
                // Set our roles in this org.
                if (!empty($current->orgid) && $current->role == $role && !$force) {
                    // Nothing to do
                    $reply['nothing_to_do'] = true;
                } else {
                    // The user orgrole was added, changed or we are forced to set it again.

                    // Add our eduvidual-membership
                    if (!empty($current->id)) {
                        $DB->set_field('local_eduvidual_orgid_userid', 'role', $role, array('orgid' => $org->orgid, 'userid' => $userid));
                    } else {
                        $data = array('orgid' => $org->orgid, 'userid' => $userid, 'role' => $role);
                        $DB->insert_record('local_eduvidual_orgid_userid', $data);
                    }

                    // Add user to orgcategory
                    $orgcatcontext = \context_coursecat::instance($org->categoryid, IGNORE_MISSING);
                    if (!empty($orgcatcontext->id)) {
                        $coursecatrolenew = get_config('local_eduvidual', 'defaultorgrole' . strtolower($role));
                        if (!empty($coursecatrolenew)) {
                            role_assign($coursecatrolenew, $userid, $orgcatcontext->id);
                        }
                        // If our old role differs, we should unassign it.
                        if (!empty($current->role)) {
                            // Check course category context
                            $coursecatroleold = get_config('local_eduvidual', 'defaultorgrole' . strtolower($current->role));

                            if ($coursecatroleold != $coursecatrolenew) {
                                role_unassign($coursecatroleold, $userid, $orgcatcontext->id);
                            }
                        }
                    }

                    // Add user to orgcourse
                    self::set_orgcourserole($org, $role, $userid);

                    // If we have a support course, add us there too.
                    if (!empty($org->supportcourseid)) {
                        $assignrole = ($role == 'Manager') ? get_config('local_eduvidual', 'defaultroleteacher') : get_config('local_eduvidual', 'defaultrolestudent');
                        \local_eduvidual\lib_enrol::course_manual_enrolments(array($org->supportcourseid), array($userid), $assignrole);
                        if ($role == 'Manager') {
                            $forums = $DB->get_records('local_edusupport', array('courseid' => $org->supportcourseid));
                            foreach ($forums as $forum) {
                                $chk = $DB->get_record('forum_subscriptions', array('userid' => $userid, 'forum' => $forum->id));
                                if (empty($chk->id)) {
                                    $DB->insert_record('forum_subscriptions', array('userid' => $userid, 'forum' => $forum->id));
                                }
                            }
                        }
                    }
                    $reply['status'] = 'ok';
                }
            }
            \local_eduvidual\educloud\user::action($userid);
        }

        // Check for global roles.
        $globalroles = array(
            'manager' => get_config('local_eduvidual', 'defaultglobalrolemanager'),
            'teacher' => get_config('local_eduvidual', 'defaultglobalroleteacher'),
            'student' => get_config('local_eduvidual', 'defaultglobalrolestudent'),
            'parent' => get_config('local_eduvidual', 'defaultglobalroleparent'),
        );
        $sql = "SELECT DISTINCT(role) AS role
                    FROM {local_eduvidual_orgid_userid}
                    WHERE userid=?";
        $hasroles = array_values($DB->get_records_sql($sql, array($userid)));
        $syscontext = \context_system::instance();
        foreach ($hasroles as $hasrole) {
            $roleid = $globalroles[strtolower($hasrole->role)];
            role_assign($roleid, $userid, $syscontext->id);
            // set globalrole to 0.
            $globalroles[strtolower($hasrole->role)] = 0;
        }
        // globalroles that were not set to 0 will be unassigned.
        foreach ($globalroles as $roleid) {
            if (!empty($roleid)) {
                role_unassign($roleid, $userid, $syscontext->id);
            }
        }

        return $reply;
    }

    /**
     * Get a random background from all available backgrounds
     * @param userid if given will set the random background for this user
     * @return url to random background
     **/
    public static function choose_background($userid = 0) {
        global $CFG;
        if (count(self::$backgrounds) == 0) {
            // We need to load possible backgrounds
            $context = \context_system::instance();
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'local_eduvidual', 'backgrounds_cards', 0);
            self::$backgrounds = array();
            foreach ($files as $file) {
                if (str_replace('.', '', $file->get_filename()) != "") {
                    self::$backgrounds[] = '' . \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                }
            }
        }
        $bgurl = '';
        if (count(self::$backgrounds) > 0) {
            $bgurl = str_replace($CFG->wwwroot, '', self::$backgrounds[array_rand(self::$backgrounds, 1)]);
        }

        if (!empty($userid) && !isguestuser($userid)) {
            set_user_preference('local_eduvidual_backgroundcard', $bgurl, $userid);
        }
        return $bgurl;
    }

    /**
     * Loads all basements for creation of new courses
     * @param type 'system' (for system-wide basements), 'user' for own courses (requires role trainer) or 'all'
     * @return array containing all courses that matches
     **/
    public static function get_course_basements($type = 'all') {
        global $DB, $USER;
        $courses = array();
        if ($type == 'system' || $type == 'all') {
            $coursebasements = explode(",", get_config('local_eduvidual', 'coursebasements'));
            foreach ($coursebasements as $cb) {
                if (!$cb) {
                    // hack für local-dev von daniel: das setting "coursebasements" existiert mehr?!?
                    $cb = 1;
                }
                $category = $DB->get_record('course_categories', array('id' => $cb));
                $courses[$category->name] = array();
                $cs = $DB->get_records_sql('SELECT * FROM {course} WHERE category=? AND visible=1 ORDER BY fullname ASC', array($cb));
                foreach ($cs as $c) {
                    $c->imageurl = self::get_course_image($c);
                    $courses[$category->name][] = $c;
                }
            }
        }
        if ($type == 'user' || $type == 'all') {
            $_courses = enrol_get_all_users_courses($USER->id, true);
            $selflbl = get_string('teacher:coursebasements:ofuser', 'local_eduvidual');
            $courses[$selflbl] = array();
            $coursesbyname = array();
            foreach ($_courses as $c) {
                $context = \context_course::instance($c->id);
                $canedit = has_capability('moodle/course:update', $context);
                if ($canedit) {
                    $c->imageurl = self::get_course_image($c);
                    $coursesbyname[$c->fullname] = $c;
                }
            }
            $names = array_keys($coursesbyname);
            asort($names);
            foreach ($names as $name) {
                $courses[$selflbl][] = $coursesbyname[$name];
            }
        }
        return $courses;
    }

    /**
     * Checks if a given basement is valid
     * @param type system, user or all
     * @param basement courseid to check
     * @return true or false
     **/
    public static function is_valid_course_basement($type, $basement) {
        $basements = self::get_course_basements($type);
        $keys = array_keys($basements);
        $found = false;
        foreach ($keys as $key) {
            foreach ($basements[$key] as $b) {
                if ($b->id == $basement) {
                    return true;
                }
            }
        }
    }

    /**
     * Enrols users to specific courses
     * @param courseids array containing courseid or a single courseid
     * @param userids array containing userids or a single userid
     * @param roleid roleid to assign, or -1 if wants to unenrol
     * @return true or false
     **/
    public static function course_manual_enrolments($courseids, $userids, $roleid) {
        global $CFG, $DB, $reply;
        if (!isset($reply))
            $reply = array();
        if (!is_array($courseids))
            $courseids = array($courseids);
        if (!is_array($userids))
            $userids = array($userids);

        // Check manual enrolment plugin instance is enabled/exist.
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            throw new \moodle_exception('manualpluginnotinstalled', 'enrol_manual');
        }
        $failures = 0;
        $instances = array();
        foreach ($courseids as $courseid) {
            // Check if course exists.
            $course = $DB->get_record('course', array('id' => $courseid), '*', IGNORE_MISSING);
            //$course = get_course($courseid);
            if (empty($course->id))
                continue;
            if (empty($instances[$courseid])) {
                $instances[$courseid] = self::get_enrol_instance($courseid);
            }

            foreach ($userids as $userid) {
                if (!self::user_exists($userid))
                    continue;
                if ($roleid == -1) {
                    $enrol->unenrol_user($instances[$courseid], $userid);
                } else {
                    $enrol->enrol_user($instances[$courseid], $userid, $roleid, time(), 0, ENROL_USER_ACTIVE);
                }

            }
        }
        return ($failures == 0);
    }

    public static function get_course_image($course) {
        global $CFG;
        $course = new \core_course_list_element($course);

        $imageurl = '';
        foreach ($course->get_course_overviewfiles() as $file) {
            if (@$file->is_valid_image()) {
                $imagepath = '/' . $file->get_contextid() .
                    '/' . $file->get_component() .
                    '/' . $file->get_filearea() .
                    $file->get_filepath() .
                    $file->get_filename();
                $imageurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $imagepath, false);
                // Use the first image found.
                break;
            }
        }
        return $imageurl;
    }

    /**
     * Get the enrol instance for manual enrolments of a course, or create one.
     * @param courseid
     * @return object enrolinstance
     */
    private static function get_enrol_instance($courseid) {
        // Check manual enrolment plugin instance is enabled/exist.
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            throw new \moodle_exception('manualpluginnotinstalled', 'enrol_manual');
        }
        $instance = null;
        $enrolinstances = enrol_get_instances($courseid, false);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                if ($courseenrolinstance->status == 1) {
                    // It is inactive - we have to activate it!
                    $data = (object)array('status' => 0);
                    $enrol->update_instance($courseenrolinstance, $data);
                    $courseenrolinstance->status = $data->status;
                }
                return $courseenrolinstance;
            }
        }
        if (empty($instance)) {
            $course = get_course($courseid);
            $enrol->add_default_instance($course);
            return self::get_enrol_instance($courseid);
        }
    }

    /**
     * Set the role in the orgcourse based on the orgrole.
     * This method only switches between roles, it does not unenrol!
     * @param object org
     * @param String orgrole
     */
    private static function set_orgcourserole($org, $orgrole, $userid) {
        $orgrole = strtolower($orgrole);
        $valid = array('manager', 'teacher', 'student', 'parent');
        if (!in_array($orgrole, $valid))
            return;
        // Enrol into organization course as student or teacher (if it is a manager!!)
        $roles = array(
            'manager' => get_config('local_eduvidual', 'defaultroleteacher'),
            'teacher' => get_config('local_eduvidual', 'defaultrolestudent'),
        );
        $roles['student'] = $roles['teacher'];
        $roles['parent'] = $roles['teacher'];
        $orgcourses = array(
            (object)array('courseid' => $org->courseid, 'context' => \context_course::instance($org->courseid, IGNORE_MISSING)),
            (object)array('courseid' => $org->supportcourseid, 'context' => \context_course::instance($org->supportcourseid, IGNORE_MISSING)),
        );
        foreach ($orgcourses as $orgcourse) {
            $courseid = $orgcourse->courseid;
            $context = $orgcourse->context;
            if (empty($context->id))
                continue;
            if (is_enrolled($context, $userid)) {
                // Only switch role
                $roletoassign = $roles[$orgrole];
                role_assign($roletoassign, $userid, $context->id);

                foreach ($roles as $role => $roleid) {
                    if ($roleid !== $roletoassign) {
                        role_unassign($roleid, $userid, $context->id);
                    }
                }
            } else {
                // Enrol user with the required role.
                self::course_manual_enrolments(array($courseid), array($userid), $roles[$orgrole]);
            }
        }
    }

    /**
     * Ensure that a user exists.
     * @param userid
     * @return boolean true or false
     */
    public static function user_exists($userid) {
        global $DB;
        $chk = $DB->get_record('user', array('id' => $userid), 'deleted');
        if (!isset($chk->deleted)) {
            return false;
        } elseif ($chk->deleted == 1) {
            // Remove this user from any eduvidual-lists
            $DB->delete_records('local_eduvidual_courseshow', array('userid' => $userid));
            $DB->delete_records('local_eduvidual_orgid_userid', array('userid' => $userid));
            $DB->delete_records('local_eduvidual_userqcats', array('userid' => $userid));
            return false;
        } else {
            return true;
        }
    }


}

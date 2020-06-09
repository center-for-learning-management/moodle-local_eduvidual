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

namespace local_eduvidual;

defined('MOODLE_INTERNAL') || die;

class lib_helper {
    /**
     * Duplicate a course.
     * @param courseid to duplicate
     * @param fullname
     * @param shortname
     * @param categoryid where the new course should be located.
     * @param visiblity whether or not the new course should be visible.
     **/
    public static function duplicate_course($courseid, $fullname, $shortname, $categoryid, $visibility = 1, $options = array()) {
        global $CFG, $DB, $USER;
        // is that needed??? require_once($CFG->dirroot . '/course/externallib.php');

        // Grant a role that allows course duplication in source and target category
        $basecourse = $DB->get_record('course', array('id' => $courseid));
        $sourcecontext = \context_course::instance($courseid);

        $roletoassign = 1; // Manager
        $revokesourcerole = true;

        $roles = get_user_roles($sourcecontext, $USER->id, false);
        foreach($roles AS $role) {
            if ($role->roleid == $roletoassign) {
                // User had this role before - we do not revoke!
                $revokesourcerole = false;
            }
        }
        role_assign($roletoassign, $USER->id, $sourcecontext->id);

        // Create new course.
        require_once($CFG->dirroot . '/course/lib.php');
        $coursedata = $basecourse;
        unset($coursedata->id);
        unset($coursedata->idnumber);
        unset($coursedata->sortorder);
        $coursedata->fullname = $fullname;
        $coursedata->shortname = $shortname;
        $coursedata->category = $categoryid;
        $coursedata->startdate = (date("m") < 6)?strtotime((date("Y")-1) . '0901000000'):strtotime(date("Y") . '0901000000');
        $coursedata->summary = "";
        foreach ($options AS $k => $option) {
            $coursedata->{$k} = $option;
        }
        $course = \create_course($coursedata);
        $targetcontext = \context_course::instance($course->id);

        // ATTENTION - Revoking the role is MANDATORY and is done AFTER the roles are set in the course!
        if (!empty($course->id)) {
            // Do the import from basement.
            require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

            // Make backup from basement.
            $course_to_backup = $basecourse->id; // id of the course to backup.
            $course_to_restore  = $course->id; // id of the target course.
            $user_performing = $USER->id; // id of the user performing the backup.
            //print_r($course);

            $bc = new \backup_controller(\backup::TYPE_1COURSE, $course_to_backup, \backup::FORMAT_MOODLE,
                                        \backup::INTERACTIVE_NO, \backup::MODE_IMPORT, $user_performing);
            //$bc->get_plan()->get_setting('users')->set_value(0);
            $bc->execute_plan();
            $bc->get_results();
            $bc->destroy();

            $tempdestination = make_backup_temp_directory($bc->get_backupid(), false);
            if (!file_exists($tempdestination) || !is_dir($tempdestination)) {
                print_error('unknownbackupexporterror'); // shouldn't happen ever
            }

            require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

            // Transaction.
            $transaction = $DB->start_delegated_transaction();

            // Restore backup into course.
            $rc = new \restore_controller($bc->get_backupid(), $course_to_restore,
                    \backup::INTERACTIVE_NO, \backup::MODE_IMPORT, $user_performing,
                    \backup::TARGET_EXISTING_DELETING);
            if ($rc->get_status() == \backup::STATUS_REQUIRE_CONV) {
                $rc->convert();
            }
            $rc->execute_precheck();
            $rc->execute_plan();

            // Commit.
            $transaction->allow_commit();
        }
        return $course;
    }
    /**
     * Makes a natural sort on an array of objects.
     * @param os The array.
     * @param indexname the property name of the objects that is used for sorting.
    **/
    public static function natsort($os, $indexname, $debugname = '') {
        global $reply;
        $reply['natsort_' . $debugname . '_os'] = $os;
        $unsortedos = array();
        foreach($os AS $o) {
            $unsortedos[$o->{$indexname}] = $o;
        }
        $reply['natsort_' . $debugname . '_unsortedos'] = $unsortedos;
        $indices = array_keys($unsortedos);
        natcasesort($indices);
        $reply['natsort_' . $debugname . '_indices'] = $indices;
        $sorted = array();
        foreach($indices AS $index) {
            $sorted[] = $unsortedos[$index];
        }
        $reply['natsort_' . $debugname . '_sorted'] = $sorted;
        return $sorted;
    }
    /**
     * Build tree for orgmenus.
     */
    public static function orgmenus() {
        global $CFG, $DB, $USER;
        $orgmenus = array();
        $fields = array("name", "url", "target", "roles");
        $memberships = $DB->get_records('local_eduvidual_orgid_userid', array('userid' => $USER->id));
        foreach($memberships as $membership){
            $org = $DB->get_record('local_eduvidual_org', array('orgid' => $membership->orgid));
            $entries = explode("\n", $org->orgmenu);
            if (count($entries) > 0) {
                $orgmenu = array(
                    'entries' => array(),
                    'name' => $org->name,
                    'orgid' => $org->orgid,
                    'url' => $CFG->wwwroot . '/course/index.php?categoryid=' . $org->categoryid,
                    'urlmanagement' => ($membership->role == 'Manager') ? $CFG->wwwroot . '/local/eduvidual/pages/manage.php?orgid=' . $org->orgid : '',
                );
                foreach ($entries AS $entry) {
                    $entry = explode("|", $entry);
                    if (empty($entry[0])) continue;
                    $o = array();
                    foreach ($fields AS $k => $field) {
                        $o[$field] = (!empty($entry[$k])) ? $entry[$k] : '';
                    }
                    if (empty($o['roles']) || strpos($membership->role, $o['roles']) > -1) {
                        $orgmenu['entries'][] = $o;
                    }
                }
                if (!empty($orgmenu['urlmanagement']) || count($orgmenu['entries']) > 0) {
                    $orgmenus[] = $orgmenu;
                }
            }
        }
        return $orgmenus;
    }
}

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
    public static function duplicate_course($courseid, $fullname, $shortname, $categoryid, $visible = 1, $options = array()) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        // Grant a role that allows course duplication in source and target category
        $basecourse = $DB->get_record('course', array('id' => $courseid));
        $sourcecontext = \context_course::instance($courseid);
        //$targetcontext = \context_coursecat::instance($categoryid);

        // Create new course
        require_once($CFG->dirroot . '/course/lib.php');
        $coursedata = (object) array(
            'category' => $categoryid,
            'fullname' => $fullname,
            'shortname' => $shortname,
            'visible' => $visible,
        );
        $targetcourse = \create_course($coursedata);

        // Enrol user as teacher
        $roleid = get_config('local_eduvidual', 'defaultroleteacher');
        \local_eduvidual\lib_enrol::course_manual_enrolments(array($targetcourse->id), array($USER->id), $roleid);

        // Import from old course

        try {
            $backupsettings = array(
                'activities' => 1,
                'blocks' => 1,
                'filters' => 1,
                'users' => 0,
                'enrolments' => 2,
                'role_assignments' => 0,
                'comments' => 0,
                'userscompletion' => 0,
                'logs' => 0,
                'grade_histories' => 0
            );
            foreach ($backupsettings AS $name => $value) {
                if (!empty($options[$name])) {
                    $backupsettings[$name] = $options[$name];
                }
            }

            // Backup the course.
            $bc = new \backup_controller(\backup::TYPE_1COURSE, $basecourse->id, \backup::FORMAT_MOODLE,
                                    \backup::INTERACTIVE_NO, \backup::MODE_SAMESITE, $USER->id);

            $settings = $bc->get_plan()->get_settings();
            foreach($settings AS $setting) {
                if (!empty($backupsettings[$setting->get_name()])) {
                    // Deactivated, caused permission error.
                    //$setting->set_value($backupsettings[$setting->get_name()]);
                }
            }

            $backupid       = $bc->get_backupid();
            $backupbasepath = $bc->get_plan()->get_basepath();

            $bc->execute_plan();
            $results = $bc->get_results();
            $file = $results['backup_destination'];

            $bc->destroy();

            // Restore the backup immediately.
            // Check if we need to unzip the file because the backup temp dir does not contains backup files.
            if (!file_exists($backupbasepath . "/moodle_backup.xml")) {
                $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backupbasepath);
            }

            $rc = new \restore_controller($backupid, $targetcourse->id,
                    \backup::INTERACTIVE_NO, \backup::MODE_SAMESITE, $USER->id, \backup::TARGET_NEW_COURSE);

            foreach ($backupsettings as $name => $value) {
                $setting = $rc->get_plan()->get_setting($name);
                if ($setting->get_status() == \backup_setting::NOT_LOCKED) {
                    // Deactivated, caused permission error.
                    //$setting->set_value($value);
                }
            }

            if (!$rc->execute_precheck()) {
                $precheckresults = $rc->get_precheck_results();
                if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                    if (empty($CFG->keeptempdirectoriesonbackup)) {
                        fulldelete($backupbasepath);
                    }

                    $errorinfo = '';

                    foreach ($precheckresults['errors'] as $error) {
                        $errorinfo .= $error;
                    }

                    if (array_key_exists('warnings', $precheckresults)) {
                        foreach ($precheckresults['warnings'] as $warning) {
                            $errorinfo .= $warning;
                        }
                    }

                    throw new moodle_exception('backupprecheckerrors', 'webservice', '', $errorinfo);
                }
            }

            $rc->execute_plan();
            $rc->destroy();

            $course = $DB->get_record('course', array('id' => $newcourseid), '*', MUST_EXIST);
            $course->fullname = $fullname;
            $course->shortname = $shortname;
            $course->visible = $visible;

            // Set shortname and fullname back.
            $DB->update_record('course', $course);

            if (empty($CFG->keeptempdirectoriesonbackup)) {
                fulldelete($backupbasepath);
            }

            // Delete the course backup file created by this WebService. Originally located in the course backups area.
            $file->delete();

            return $course;
        } catch(Exception $e) {
            echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
                'content' => $e->getMessage(),
                'type' => 'danger',
            ));
        }
    }
    /**
     * If a course belongs to "mycourses", moodle will not show the coursecategories in the navbar.
     * We want that, so we modify the navbar, if the "mycourses"-node is found.
     */
    public static function fix_navbar() {
        global $DB, $PAGE;

        // Add navbar items.
        if (!empty($PAGE->context->contextlevel) && $PAGE->context->contextlevel >= CONTEXT_COURSE) {

            $navbaritems = $PAGE->navbar->get_items();
            $PAGE->navbar->ignore_active();

            $foundmycourses = false;
            $nodes = array();
            $nodesafter = array();

            foreach ($navbaritems AS &$navbaritem) {
                if ($navbaritem->key == 'myhome') {
                    $nodes[] = array(
                        'has_action' => true,
                        'action' => $navbaritem->action,
                        'get_title' => $navbaritem->text,
                        'get_content' => $navbaritem->text,
                        'is_hidden' => false,
                    );
                    continue;
                }
                if ($navbaritem->key == 'mycourses') {
                    $foundmycourses = true;
                    continue;
                }
                if ($navbaritem->type != \navigation_node::TYPE_COURSE) {
                    $nodesafter[] = array(
                        'has_action' => true,
                        'action' => $navbaritem->action,
                        'get_title' => $navbaritem->text,
                        'get_content' => $navbaritem->text,
                        'is_hidden' => false,
                    );
                }
            }

            if (!$foundmycourses) {
                // we did not find the my courses node, so we will not modify this navbar!
                return;
            }

            $path = explode('/', $PAGE->context->path);
            for ($a = 2; $a < count($path); $a++) {
                $ctx = $DB->get_record('context', array('id' => $path[$a]));
                switch ($ctx->contextlevel) {
                    case CONTEXT_COURSECAT:
                        $o = $DB->get_record('course_categories', array('id' => $ctx->instanceid));
                        if (!empty($o->id)) {
                            $url = new \moodle_url('/course/index.php', array('categoryid' => $o->id));
                            $nodes[] = array(
                                'has_action' => true,
                                'action' => $url->__toString(),
                                'get_title' => $o->name,
                                'get_content' => $o->name,
                                'is_hidden' => false,
                            );
                        }
                    break;
                    case CONTEXT_COURSE:
                        $o = $DB->get_record('course', array('id' => $ctx->instanceid));
                        if (!empty($o->id)) {
                            $url = new \moodle_url('/course/view.php', array('id' => $o->id));
                            $nodes[] = array(
                                'has_action' => true,
                                'action' => $url->__toString(),
                                'get_title' => $o->fullname,
                                'get_content' => $o->fullname,
                                'is_hidden' => false,
                            );
                        }
                    break;
                }
            }

            $nodes = array_merge($nodes, $nodesafter);
            \local_eduvidual\lib_wshelper::$navbar_nodes = $nodes;
            ob_start();
            register_shutdown_function('\local_eduvidual\lib_wshelper::buffer_navbar');
        }
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
        // Trigger if management-links shall be shawn.
        $display_managerlink = false;
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
                    'urlmanagement' => ($display_managerlink && $membership->role == 'Manager') ?
                                            $CFG->wwwroot . '/local/eduvidual/pages/manage.php?orgid=' . $org->orgid : '',
                );
                foreach ($entries AS $entry) {
                    $entry = explode("|", $entry);
                    if (empty($entry[0])) continue;
                    $o = array();
                    foreach ($fields AS $k => $field) {
                        $o[$field] = (!empty($entry[$k])) ? $entry[$k] : '';
                    }
                    if (empty($o['roles']) || strpos($o['roles'], $membership->role) > -1) {
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
    /**
     * Override course settings as specified by org-managers.
     * @param courseid.
     */
    public static function override_coursesettings($courseid) {
        global $DB;
        $ctx = \context_course::instance($courseid);
        if (empty($ctx->id)) {
            return;
        }
        $org = \local_eduvidual\locallib::get_org_by_courseid($courseid);
        if (empty($org->orgid)) {
            return;
        }
        $overrides = $DB->get_records('local_eduvidual_overrides', array('orgid' => $org->orgid));
        foreach ($overrides as $override) {
            $field = explode('_', $override->field);
            switch($field[0]) {
                case 'courserole':
                    if (count($field) == 3 && $field[2] == 'name') {
                        $roleid = $field[1];
                        $rec = $DB->get_record('role_names', array('contextid' => $ctx->id, 'roleid' => $roleid));
                        if (!empty($rec->id)) {
                            $DB->set_field('role_names', 'name', $override->value, array('contextid' => $ctx->id, 'roleid' => $roleid));
                        } else {
                            $rec = (object) array(
                                'contextid' => $ctx->id,
                                'name' => $override->value,
                                'roleid' => $roleid,
                            );
                            $DB->insert_record('role_names', $rec);
                        }
                    }
                break;
            }

        }
    }
}

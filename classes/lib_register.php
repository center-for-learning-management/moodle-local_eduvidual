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
 * @copyright  2021 Center for Learning Management
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual;

defined('MOODLE_INTERNAL') || die;

class lib_register {
    /**
     * Create a category for the org if none exists or it was deleted.
     * @param org as object.
     */
    public static function create_orgcategory(&$org) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/externallib.php');

        if (!empty($org->categoryid)) {
            $ctx = \context_coursecat::instance($org->categoryid, IGNORE_MISSING);
            if (empty($ctx->id)) {
                // It was removed!
                $org->categoryid = 0;
            }
        }

        if (empty($org->categoryid)) {
            // Create a course category for this org
            $data = (object) [
                'name' => $org->name,
                'description' => $org->name,
                'idnumber' => $org->orgid,
            ];
            $category = \core_course_category::create($data);
            $org->categoryid = $category->id;
            $DB->set_field('local_eduvidual_org', 'categoryid', $org->categoryid, [ 'orgid' => $org->orgid ]);
        }
    }
    /**
     * Create required courses for this org, if they don't exist.
     * @param org as object.
     */
    public static function create_orgcourses(&$org) {
        global $CFG, $DB;
        if (empty($org->categoryid)) {
            throw new \moodle_exception('category must be set', 'local_eduvidual');
        }
        if (!empty($org->courseid)) {
            $ctx = \context_course::instance($org->courseid, IGNORE_MISSING);
            if (empty($ctx->id)) {
                // Was removed.
                $org->courseid = 0;
            }
        }
        if (!empty($org->supportcourseid)) {
            $ctx = \context_course::instance($org->courseid, IGNORE_MISSING);
            if (empty($ctx->id)) {
                // Was removed.
                $org->supportcourseid = 0;
            }
        }
        if (empty($org->courseid)) {
            // Create an org-course for this org
            $orgcoursebasement = get_config('local_eduvidual', 'orgcoursebasement');
            $course = \local_eduvidual\lib_helper::duplicate_course($orgcoursebasement, 'Digitaler Schulhof (' . $org->orgid . ')', $org->orgid, $org->categoryid, 1);
            $org->courseid = $course->id;
            $DB->set_field('local_eduvidual_org', 'courseid', $org->courseid, array('orgid' => $org->orgid));
            $course->summary = '<p>Digitaler Schulhof der Schule ' . $org->name . '</p>';
            $DB->set_field('course', 'summary', $course->summary, array('id' => $course->id));
        }
        if (empty($org->supportcourseid) && file_exists($CFG->dirroot . '/local/edusupport/version.php')) {
            // Create a support course for this org.
            $template = get_config('local_eduvidual', 'supportcourse_template');
            if (!empty($template)) {
                // Duplicate our template.
                $supportcourse = \local_eduvidual\lib_helper::duplicate_course($template, 'Helpdesk (' . $org->name . ')', 'helpdesk_' . $org->orgid, $org->categoryid, 1);
                if (!empty($supportcourse->id)) {
                    $DB->set_field('local_eduvidual_org', 'supportcourseid', $supportcourse->id, array('orgid' => $org->orgid));
                    // Remove news forum in that course.
                    $sql = "SELECT * FROM {forum} WHERE type='news')";
                    $newsforums = $DB->get_records('forum', array('type' => 'news', 'course' => $supportcourse->id));
                    foreach ($newsforums as $newsforum) {
                        $cm = \get_coursemodule_from_instance('forum', $newsforum->id);
                        if (!empty($cm->id)) {
                            \course_delete_module($cm->id);
                        }
                    }
                    $sql = "SELECT * FROM {forum} WHERE course=? AND type='general'";
                    $forums = $DB->get_records_sql($sql, array($supportcourse->id));
                    foreach ($forums as $forum) {
                        \local_edusupport\lib::supportforum_enable($forum->id);
                        if ($org->orgid > 500000 && $org->orgid < 600000) {
                            // School from Salzburg
                            \local_edusupport\lib::supportforum_setdedicatedsupporter($forum->id, 2098);
                        }
                    }
                }
            }
        }
    }
    /**
     * Set the name or the org.
     * @param org object containing org data.
     * @param name the new name.
     */
    public static function set_orgname(&$org, $name) {
        global $DB;
        if ($org->name != $name) {
            $org->name = $name;
            $DB->set_field('local_eduvidual_org', 'name', $org->name, [ 'orgid' => $org->orgid ]);
        }
    }
}

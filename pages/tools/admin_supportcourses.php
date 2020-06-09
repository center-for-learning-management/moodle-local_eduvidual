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
 * @copyright  2020 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Ensure that every organiation has a support course.
 */

require_once('../../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/admin.php', array());
$PAGE->set_title(get_string('admin:supportcourses', 'local_eduvidual'));
$PAGE->set_heading(get_string('admin:supportcourses', 'local_eduvidual'));

require_login();

if (!is_siteadmin()) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => get_string('access_denied', 'local_eduvidual'),
        'type' => 'danger'
    ));
    echo $OUTPUT->footer();
    die();
}

$template = get_config('local_eduvidual', 'supportcourse_template');
if (empty($template)) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => get_string('admin:supportcourse:missingsetup', 'local_eduvidual'),
        'type' => 'danger'
    ));
    echo $OUTPUT->footer();
    die();
}

$course = $DB->get_record('course', array('id' => $template), '*', MUST_EXIST);

echo $OUTPUT->header();
echo "<ul>\n";
require_once($CFG->dirroot . '/course/externallib.php');
$orgs = $DB->get_records('local_eduvidual_org', array('authenticated' => 1));
foreach ($orgs AS $org) {
    $course = $DB->get_record('course', array('id' => $template), 'id', IGNORE_MISSING);
    if (!empty($org->supportcourseid) && !empty($course->id)) {
        echo "<li>$org->name has a supportcourse</li>\n";
    } else {
        echo "<li>$org->name needs a supportcourse</li>\n";
        $supportcourse = \local_eduvidual\lib_helper::duplicate_course($template, 'Helpdesk (' . $org->name . ')', 'helpdesk_' . $org->orgid, $org->categoryid, 1);
        if (empty($supportcourse->id)) {
            echo "<li class='alert alert-danger'><strong>Error creating supportcourse</strong></li>\n";
        } else {
            $DB->set_field('local_eduvidual_org', 'supportcourseid', $supportcourse->id, array('orgid' => $org->orgid));
            // Retrieve first forum from course and configure it as supportforum.
            $sql = "SELECT * FROM {forum} WHERE course=? LIMIT 0,1";
            $forum = $DB->get_record_sql($sql, array($supportcourse->id));
            if (empty($forum->id)) {
                echo "<li class='alert alert-danger'>Supportcourse created successfully, but there is no forum to set as support forum.</li>\n";
            } else {
                \block_edusupport\lib::supportforum_enable($forum->id);
                echo "<li>Supportcourse created successfully</li>\n";
                if ($org->orgid > 500000 && $org->orgid < 600000) {
                    // School from Salzburg
                    echo "<li>Added dedicated supporter #2098</li>\n";
                    \block_edusupport\lib::supportforum_setdedicatedsupporter($forum->id, 2098);
                }
            }

            // Now enrol all users of that organisation.
            $members = $DB->get_records('local_eduvidual_orgid_userid', array('orgid' => $org->orgid));
            $managers = array();
            $others = array();
            foreach ($members AS $member) {
                if ($member->role == 'Manager') $managers[] = $member->userid;
                else $others[] = $member->userid;
            }
            \local_eduvidual\lib_enrol::course_manual_enrolments(array($supportcourse->id), $managers, get_config('local_eduvidual', 'defaultroleteacher'));
            \local_eduvidual\lib_enrol::course_manual_enrolments(array($supportcourse->id), $others, get_config('local_eduvidual', 'defaultrolestudent'));
            echo "<li>Added " . count($managers) . " Managers with teacher role</li>\n";
            echo "<li>Added " . count($others) . " Users with student role</li>\n";
        }
    }
    break;
}
echo "</ul>\n";

echo $OUTPUT->footer();

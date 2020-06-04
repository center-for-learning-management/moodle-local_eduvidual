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
$PAGE->set_url('/blocks/eduvidual/pages/admin.php', array());
$PAGE->set_title(get_string('admin:supportcourses', 'block_eduvidual'));
$PAGE->set_heading(get_string('admin:supportcourses', 'block_eduvidual'));

require_login();



if (!is_siteadmin()) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('block_eduvidual/alert', array(
        'content' => get_string('access_denied', 'block_eduvidual'),
        'type' => 'danger'
    ));
    echo $OUTPUT->footer();
    die();
}

$template = get_config('block_eduvidual', 'supportcourse_template');
if (empty($template)) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('block_eduvidual/alert', array(
        'content' => get_string('admin:supportcourse:missingsetup', 'block_eduvidual'),
        'type' => 'danger'
    ));
    echo $OUTPUT->footer();
    die();
}

$course = $DB->get_record('course', array('id' => $template), '*', MUST_EXIST);


echo $OUTPUT->header();

$orgs = $DB->get_records('block_eduvidual_org', array('authenticated' => 1));
foreach ($orgs AS $org) {
    $course = $DB->get_record('course', array('id' => $template), 'id', IGNORE_MISSING);
    if (!empty($org->supportcourseid) && !empty($course->id)) {
        echo "<li>$org->name has a supportcourse</li>";
    } else {
        echo "<li>$org->name needs a supportcourse</li>";
    }
}
// load all orgs that are authenticated on this system.

// check for every org if the supportcourse is set and exists.

// If not, create a support course and enrol all users of that org.

echo $OUTPUT->footer();

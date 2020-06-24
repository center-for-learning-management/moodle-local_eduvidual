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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We allow managers to "force-enrol" as teacher into certain courses.

require_once('../../../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);

$PAGE->set_context(\context_course::instance($courseid));
$PAGE->set_url('/local/eduvidual/pages/redirects/forceenrol.php', array('courseid' => $courseid));
$PAGE->set_title(get_string('manage:enrolmeasteacher', 'local_eduvidual'));
$PAGE->set_heading(get_string('manage:enrolmeasteacher', 'local_eduvidual'));


$org = \local_eduvidual\locallib::get_org_by_courseid($courseid);


$is_manager = !empty($org->categoryid) && \local_eduvidual\locallib::is_manager($org->categoryid);

if (!empty($org->orgid) && ($is_manager || is_siteadmin())) {
    \local_eduvidual\lib_enrol::course_manual_enrolments(array($courseid), array($USER->id), get_config('local_eduvidual', 'defaultroleteacher'));
    redirect(new \moodle_url('/course/view.php', array('id' => $courseid)));
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => get_string('access_denied', 'local_eduvidual'),
        'type' => 'danger',
        'url' => new \moodle_url('/course/view.php', array('id' => $courseid),
    ));
    echo $OUTPUT->footer();
}

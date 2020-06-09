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

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/eduvidual/block_eduvidual.php');

$current_orgid = optional_param('orgid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$sectionid = optional_param('sectionid', -1, PARAM_INT);
$moduleid = optional_param('moduleid', -1, PARAM_INT);
$act = optional_param('act', 'createmodule', PARAM_TEXT);


$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/local/eduvidual/pages/teacher.php', array('act' => $act, 'orgid' => $current_orgid, 'courseid' => $courseid, 'sectionid' => $sectionid, 'moduleid' => $moduleid));
//$PAGE->set_cacheable(false);

// Only allow a certain user group access to this page
$allow = array("Manager", "Teacher");
if (!in_array(local_eduvidual::get('role'), $allow) && !is_siteadmin()) {
    $PAGE->set_context(context_system::instance());
    local_eduvidual::print_app_header();
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'type' => 'danger',
        'content' => get_string('access_denied', 'local_eduvidual'),
    ));
	local_eduvidual::print_app_footer();
	exit;
}

// Used to determine if we can teach in this org
$orgas = local_eduvidual::get_organisations('Teacher');

// We do not set context for createcourse!
if ($act != 'createcourse') {
    if ($courseid > 0) {
        $org = local_eduvidual::set_org_by_courseid($courseid);
        local_eduvidual::set_context_auto($courseid);
        $course = $DB->get_record('course', array('id' => $courseid));
        $title = $course->fullname;
        require_login($course);
    } else {
        $org = local_eduvidual::get_organisations_check($orgas, $current_orgid);
        if ($org && $courseid == 0) {
            local_eduvidual::set_org($org->orgid);
        } else {
            local_eduvidual::set_context_auto();
        }
    }
} else {
    $PAGE->set_context(context_system::instance());
}

switch($act) {
	case 'createmodule':
        $title = get_string('teacher:createmodule', 'local_eduvidual');
    break;
	case 'createcourse':
        $title = get_string('teacher:createcourse', 'local_eduvidual');
    break;
	default: $title = get_string('Teacher', 'local_eduvidual');
}

$PAGE->set_title($title);
$PAGE->set_heading($title);

$actions = local_eduvidual::get_actions('teacher');

$subpages = array_keys($actions);
$includefile = $CFG->dirroot . '/local/eduvidual/pages/sub/teacher_' . $act . '.php';
if (in_array($act, $subpages) && file_exists($includefile)) {
    include($includefile);
}

local_eduvidual::print_app_footer();

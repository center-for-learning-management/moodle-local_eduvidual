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


require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/eduvidual/block_eduvidual.php');

$manageruserid = optional_param('manageruserid', 0, PARAM_INT);
$orgids = optional_param('orgids', '', PARAM_TEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/admin.php', array());
$PAGE->set_title(get_string('Administration', 'local_eduvidual'));
$PAGE->set_heading(get_string('Administration', 'local_eduvidual'));

require_login();

if (!is_siteadmin()) die;

$msgs = array();

if (!empty($orgids) && !empty($manageruserid)) {
    $orgs = explode("\n", $orgids);
    foreach ($orgs AS $orgid) {
        $orgid = trim($orgid);
        $org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));

        if (!empty($org->orgid)) {
            $msgs[] = "Registering $org->orgid with name $org->name<br />";

            require_once($CFG->dirroot . '/lib/coursecatlib.php');
            require_once($CFG->dirroot . '/course/externallib.php');

            if (empty($org->categoryid)) {
                // Create a course category for this org
                $data = new \stdClass();
                $data->name = $org->name;
                $data->description = $org->name;
                $data->idnumber = $org->orgid;
                $category = coursecat::create($data);
                $org->categoryid = $category->id;
                $msgs[] = "=> Created category $org->categoryid<br />";
                $DB->set_field('local_eduvidual_org', 'categoryid', $org->categoryid, array('orgid' => $org->orgid));
            }

            if (empty($org->courseid)) {
                // Create an org-course for this org
                $orgcoursebasement = get_config('local_eduvidual', 'orgcoursebasement');
                $basement = $DB->get_record('course', array('id' => $orgcoursebasement));

                if (!empty($basement->id)) {
                    // Duplicate course
                    $course = core_course_external::duplicate_course($basement->id, 'Digitaler Schulhof (' . $org->orgid . ')', $org->orgid, $org->categoryid, true);
                    $org->courseid = $course["id"];
                    $DB->set_field('local_eduvidual_org', 'courseid', $org->courseid, array('orgid' => $org->orgid));
                    $course['summary'] = '<p>Digitaler Schulhof der Schule ' . $org->name . '</p>';
                    $DB->update_record('course', $course);
                }
                $msgs[] = "=> Created course $org->courseid<br />";
            }

            if (!empty($org->courseid)) {
                $msgs[] = "=> Setting up roles<br />";
                \local_eduvidual\lib_enrol::role_set($manageruserid, $org, 'Manager');

                $org->authenticated = 1;
                $org->authtan = '';
                $DB->set_field('local_eduvidual_org', 'authenticated', 1, array('orgid' => $org->orgid));
                $DB->set_field('local_eduvidual_org', 'authtan', '', array('orgid' => $org->orgid));
            }
        }
    }
}


echo $OUTPUT->header();
if (count($msgs) > 0) {
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array('content' => implode('', $msgs)));
}
echo $OUTPUT->render_from_template('local_eduvidual/admin_bulkregister', array('manageruserid' => $manageruserid, 'orgids' => $orgids));
echo $OUTPUT->footer();

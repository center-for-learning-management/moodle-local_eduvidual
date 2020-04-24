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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../config.php');
require_login();

require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

if (!block_eduvidual::get('role') == "Administrator") die;

$manageruserid = optional_param('manageruserid', 0, PARAM_INT);
$orgids = optional_param('orgids', '', PARAM_TEXT);

if (!empty($orgids) && !empty($manageruserid)) {
    $orgs = explode(' ', $orgids);
    foreach ($orgs AS $orgid) {
        $org = $DB->get_record('block_eduvidual_org', array('orgid' => $orgid));

        if (!empty($org->orgid)) {
            $org->name = $name;
            $DB->set_field('block_eduvidual_org', 'name', $name, array('orgid' => $org->orgid));
            echo "Registering $org->orgid with name $name<br />";

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
                echo "=> Created category $org->categoryid<br />";
                $DB->set_field('block_eduvidual_org', 'categoryid', $org->categoryid, array('orgid' => $org->orgid));
            }

            if (empty($org->courseid)) {
                // Create an org-course for this org
                $orgcoursebasement = get_config('block_eduvidual', 'orgcoursebasement');
                $basement = $DB->get_record('course', array('id' => $orgcoursebasement));

                if (!empty($basement->id)) {
                    // Grant a role that allows course duplication in source and target category
                    $sourcecontext = context_coursecat::instance($basement->category);
                    $targetcontext = context_coursecat::instance($org->categoryid);
                    $roletoassign = 1; // Manager
                    $revokesourcerole = true;
                    $revoketargetrole = true;
                    $roles = get_user_roles($sourcecontext, $manageruserid, false);
                    foreach($roles AS $role) {
                        if ($role->roleid == $roletoassign) {
                            // User had this role before - we do not revoke!
                            $revokesourcerole = false;
                        }
                    }
                    $roles = get_user_roles($targetcontext, $manageruserid, false);
                    foreach($roles AS $role) {
                        if ($role->roleid == $roletoassign) {
                            // User had this role before - we do not revoke!
                            $revoketargetrole = false;
                        }
                    }
                    role_assign($roletoassign, $manageruserid, $sourcecontext->id);
                    role_assign($roletoassign, $manageruserid, $targetcontext->id);

                    // Duplicate course
                    $course = core_course_external::duplicate_course($basement->id, 'Digitaler Schulhof (' . $org->orgid . ')', $org->orgid, $org->categoryid, true);
                    $org->courseid = $course["id"];
                    $DB->set_field('block_eduvidual_org', 'courseid', $org->courseid, array('orgid' => $org->orgid));
                    $course['summary'] = '<p>Digitaler Schulhof der Schule ' . $org->name . '</p>';
                    $DB->update_record('course', $course);

                    // Revoke role that allows course duplication in source and target category
                    if ($revokesourcerole) {
                        role_unassign($roletoassign, $manageruserid, $sourcecontext->id);
                    }
                    if ($revoketargetrole) {
                        role_unassign($roletoassign, $manageruserid, $targetcontext->id);
                    }
                }
                echo "=> Created course $org->courseid<br />";
            }

            if (!empty($org->courseid)) {
                require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                echo "=> Setting up roles";
                print_r(block_eduvidual_lib_enrol::role_set($manageruserid, $org, 'Manager'));
                echo "<br />";

                $org->authenticated = 1;
                $org->authtan = '';
                $DB->set_field('block_eduvidual_org', 'authenticated', 1, array('orgid' => $org->orgid));
                $DB->set_field('block_eduvidual_org', 'authtan', '', array('orgid' => $org->orgid));
            }
        }
    }
}

echo $OUTPUT->render_from_template('block_eduvidual/admin_bulkregister', array('manageruserid' => $manageruserid, 'orgids' => $orgids));

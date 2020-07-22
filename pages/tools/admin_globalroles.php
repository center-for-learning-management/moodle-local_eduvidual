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
$PAGE->set_url('/local/eduvidual/pages/tools/admin_supportcourses.php', array());
$PAGE->set_title(get_string('admin:supportcourses', 'local_eduvidual'));
$PAGE->set_heading(get_string('admin:supportcourses', 'local_eduvidual'));

require_login();

echo $OUTPUT->header();

$roles = array(
    32 => 'manager',
    33 => 'teacher',
    34 => 'student',
    35 => 'parent',
);
echo "<ul>\n";
foreach ($roles AS $role => $type) {
    $previousrole = get_config('local_eduvidual', 'defaultorgrole' . $type);
    //$reply['previousrole'] = $previousrole;
    if (!empty($previousrole) && $previousrole != $role) {
        // We remove the previously set roles.
        //$reply['unassigning'] = array();
        $members = $DB->get_records('local_eduvidual_orgid_userid', array('role' => ucfirst($type)), 'orgid ASC', '*');
        $orgid = 0; $contextid = 0;
        foreach ($members AS $member) {
            if ($member->orgid != $orgid) {
                $org = $DB->get_record('local_eduvidual_org', array('orgid' => $member->orgid));
                if (empty($org->categoryid)) continue; // Skip this org - no category.
                $context = \context_coursecat::instance($org->categoryid, IGNORE_MISSING);
                $contextid = $context->id;
            }
            if (empty($contextid)) continue;
            echo "<li>UNASSIGN role " . $previousrole . " for " . $member->userid . " in " . $org->orgid . ", context " . $context->id . "</li>\n";
            role_unassign($previousrole, $member->userid, $context->id);
        }
    }
    if (!empty($role)) {
        set_config('defaultorgrole' . $type, $role, 'local_eduvidual');
        //$reply['assigning'] = array();
        $members = $DB->get_records('local_eduvidual_orgid_userid', array('role' => ucfirst($type)), 'orgid ASC', '*');
        $orgid = 0; $contextid = 0;
        foreach ($members AS $member) {
            if ($member->orgid != $orgid) {
                $org = $DB->get_record('local_eduvidual_org', array('orgid' => $member->orgid));
                if (empty($org->categoryid)) continue; // Skip this org - no category.
                $context = \context_coursecat::instance($org->categoryid, IGNORE_MISSING);
                $contextid = $context->id;
            }
            if (empty($contextid)) continue;
            // Check if this user still exists.
            $user = \core_user::get_user($member->userid, 'id,deleted', IGNORE_MISSING);
            if (empty($user->id) || $user->deleted) continue;
            echo "<li>ASSIGN role " . $role . " for " . $member->userid . " in " . $org->orgid . ", context " . $contextid . "</li>\n";
            role_assign($role, $member->userid, $contextid);
        }
    } else {
        // How to remove a plugin-config?
        //remove_config('defaultorgrole' . $type, 'local_eduvidual');
    }

}
echo "</ul>";

echo $OUTPUT->footer();

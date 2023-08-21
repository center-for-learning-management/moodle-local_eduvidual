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
 * Ensure that all global roles are set correctly!
 */

require_once('../../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/tools/admin_supportcourses.php', array());
$PAGE->set_title(get_string('admin:supportcourses', 'local_eduvidual'));
$PAGE->set_heading(get_string('admin:supportcourses', 'local_eduvidual'));

require_login();

echo $OUTPUT->header();

$types = array('manager', 'teacher', 'student', 'parent');
$roles = array(
    32 => 'manager',
    33 => 'teacher',
    34 => 'student',
    35 => 'parent',
);
echo "<ul>\n";
foreach ($roles as $role => $type) {
    // Set to 0 if you require at least one!
    if (in_array($type, $types)) {
        // We test if the new role is already used by another role.
        $roleinuse = false;
        foreach ($types as $testtype) {
            if ($testtype == $type)
                continue;
            if (get_config('local_eduvidual', 'defaultglobalrole' . $testtype) == $role) {
                $roleinuse = true;
            };
        }
        if (!$roleinuse) {
            $context = \context_system::instance();
            $previousrole = get_config('local_eduvidual', 'defaultglobalrole' . $type);
            //$reply['previousrole'] = $previousrole;
            if (!empty($previousrole) && $previousrole != $role) {
                // We remove the previously set roles.
                $assignments = $DB->get_records('role_assignments', array('roleid' => $previousrole, 'contextid' => $context->id));
                foreach ($assignments as $assignment) {
                    echo "<li>UNASSIGN role " . $previousrole . " for " . $assignment->userid . " in " . $context->id . "</li>\n";
                    role_unassign($previousrole, $assignment->userid, $context->id);
                }
            }
            if (!empty($role)) {
                set_config('defaultglobalrole' . $type, $role, 'local_eduvidual');
                //$reply['assigning'] = array();
                $members = $DB->get_records('local_eduvidual_orgid_userid', array('role' => ucfirst($type)));
                foreach ($members as $member) {
                    // Check if this user still exists.
                    $user = \core_user::get_user($member->userid, 'id,deleted', IGNORE_MISSING);
                    if (empty($user->id) || $user->deleted)
                        continue;
                    echo "<li>ASSIGN role " . $role . " for " . $member->userid . " in " . $context->id . "</li>\n";
                    role_assign($role, $member->userid, $context->id);
                }
            } else {
                // How to remove a plugin-config?
                //remove_config('defaultglobalrole' . $type, 'local_eduvidual');
            }
            $reply['status'] = 'ok';
        } else {
            $reply['error'] = get_string('defaultroles:global:inuse', 'local_eduvidual');
        }
    } else {
        $reply['error'] = 'config_not_set';
    }
}
echo "</ul>";

echo $OUTPUT->footer();

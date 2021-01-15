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
 * Unassign all manager roles.
 */

require_once('../../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/tools/admin_removemanagers.php', array());
$PAGE->set_title('Remove Managers');
$PAGE->set_heading('Remove Managers');

require_login();

echo $OUTPUT->header();

$assignments = $DB->get_records('role_assignments', array('roleid' => 1));

echo "<ul>\n";
foreach ($assignments as $assignment) {
    echo "<li>Unassigning $assignment->userid from $assignment->contextid</li>\n";
    role_unassign($assignment->roleid, $assignment->userid, $assignment->contextid);
}
echo "</ul>";

echo $OUTPUT->footer();

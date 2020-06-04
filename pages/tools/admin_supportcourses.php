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

if (!is_siteadmin()) die;

echo $OUTPUT->header();

// load all orgs that are authenticated on this system.

// check for every org if the supportcourse is set and exists.

// If not, create a support course and enrol all users of that org.


echo $OUTPUT->footer();

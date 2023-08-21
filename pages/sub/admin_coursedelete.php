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
 * @copyright  2022 Center of Learning Management (www.lernmanagement.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
if (!is_siteadmin())
    die;

$from = optional_param('from', 0, PARAM_INT);
$size = optional_param('size', 50, PARAM_INT);
$sizes = [];
$_sizes = [30, 50, 100, 500, 1000];
foreach ($_sizes as $_size) {
    $sizes[] = (object)[
        'size' => $_size,
        'selected' => ($_size == $size) ? 1 : 0,
    ];
}

$entries = array_values($DB->get_records('local_eduvidual_coursedelete', [], '', '*', $from, $size));
foreach ($entries as $entry) {
    $user = \core_user::get_user($entry->userid);
    $entry->userfullname = fullname($user);
}

echo $OUTPUT->render_from_template(
    'local_eduvidual/admin_coursedelete',
    (object)array(
        'entries' => $entries,
        'from' => $from,
        'nextfrom' => $from + $size,
        'size' => $size,
        'sizes' => $sizes,
        'wwwroot' => $CFG->wwwroot,
    )
);

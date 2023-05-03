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

if (!is_siteadmin()) {
    throw new \moodle_exception(get_string('access_denied', 'local_eduvidual'));
}

$act = optional_param('act', '', PARAM_TEXT);
if (empty($act)) {
    $act = 'backgrounds';
}

$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/admin.php', array('act' => $act));
$PAGE->set_title(get_string('Administration', 'local_eduvidual'));
$PAGE->set_heading(get_string('Administration', 'local_eduvidual'));
//$PAGE->set_cacheable(false);
$PAGE->requires->css('/local/eduvidual/style/leaflet.css');
$PAGE->requires->css('/local/eduvidual/style/admin.css');

$adminurl = new \moodle_url('/local/eduvidual/pages/admin.php');
$PAGE->navbar->add(get_string('Administration', 'local_eduvidual'), $adminurl);
if (!empty($act)) {
    $PAGE->navbar->add(get_string("admin:$act:title", 'local_eduvidual'), $PAGE->url);
    $PAGE->set_title(get_string("admin:$act:title", 'local_eduvidual'));
    $PAGE->set_heading(get_string("admin:$act:title", 'local_eduvidual'));
}

echo $OUTPUT->header();

echo "<div class=\"grid-eq-2\">\n";
$actions = \local_eduvidual\locallib::get_actions('admin');
\local_eduvidual\locallib::print_act_selector($actions);
echo "</div>\n";

$subpages = array_keys($actions);
$includefile = $CFG->dirroot . '/local/eduvidual/pages/sub/admin_' . $act . '.php';
if (in_array($act, $subpages) && file_exists($includefile)) {
    include($includefile);
}

echo $OUTPUT->footer();

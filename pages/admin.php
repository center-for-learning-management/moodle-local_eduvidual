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

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

$act = optional_param('act', '', PARAM_TEXT);
if (empty($act)) {
    $act = 'backgrounds';
}

block_eduvidual::set_context_auto();
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/blocks/eduvidual/pages/admin.php', array('act' => $act));
$PAGE->set_title(get_string('Administration', 'block_eduvidual'));
$PAGE->set_heading(get_string('Administration', 'block_eduvidual'));
//$PAGE->set_cacheable(false);
$PAGE->requires->css('/blocks/eduvidual/style/leaflet.css');
$PAGE->requires->css('/blocks/eduvidual/style/admin.css');

block_eduvidual::print_app_header();

if (!is_siteadmin()) {
	?>
		<p class="alert alert-danger"><?php echo get_string('access_denied', 'block_eduvidual'); ?></p>
	<?php
	block_eduvidual::print_app_footer();
	die();
}

echo "<div class=\"grid-eq-2\">\n";
$actions = block_eduvidual::get_actions('admin');
block_eduvidual::print_act_selector($actions);
echo "</div>\n";

$subpages = array_keys($actions);
$includefile = $CFG->dirroot . '/blocks/eduvidual/pages/sub/admin_' . $act . '.php';
if (in_array($act, $subpages) && file_exists($includefile)) {
    include($includefile);
}

block_eduvidual::print_app_footer();

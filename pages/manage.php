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

$act = optional_param('act', '', PARAM_TEXT);
$orgid = optional_param('orgid', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/local/eduvidual/pages/manage.php', array('act' => $act, 'orgid' => $orgid));
$PAGE->set_title(get_string('Management', 'local_eduvidual'));
$PAGE->set_heading(get_string('Management', 'local_eduvidual'));
//$PAGE->set_cacheable(false);
$PAGE->requires->css('/local/eduvidual/style/manage.css');

// Only allow a certain user group access to this script
$allow = array("Manager");
if (!in_array(local_eduvidual::get('role'), $allow) && !is_siteadmin()) {
	local_eduvidual::print_app_header();
	?>
		<p class="alert alert-danger"><?php get_string('access_denied', 'local_eduvidual'); ?></p>
	<?php
	local_eduvidual::print_app_footer();
	exit;
}

// Used to determine if we can manage this org
$current_orgid = optional_param('orgid', 0, PARAM_INT);
$orgas = local_eduvidual::get_organisations('Manager');
$org = local_eduvidual::get_organisations_check($orgas, $current_orgid);
if ($org) {
    local_eduvidual::set_org($org->orgid);
}

local_eduvidual::set_context_auto(0, $org->categoryid);
$PAGE->navbar->add(get_string('Management', 'local_eduvidual'), $PAGE->url);

$act = optional_param('act', '', PARAM_TEXT);
if (empty($act)) {
    $act = 'users';
}
switch($act) {
    case 'archive':
        $title = get_string('manage:archive', 'local_eduvidual');
        $PAGE->requires->js('/local/eduvidual/js/archive.js');
        $PAGE->requires->css('/local/eduvidual/style/archive.css');
    break;
	case 'categories':
        $title = get_string('manage:categories', 'local_eduvidual');
        $PAGE->requires->js('/local/eduvidual/js/archive.js');
        $PAGE->requires->css('/local/eduvidual/style/archive.css');
    break;
    case 'subcats': $title = get_string('manage:subcats:title', 'local_eduvidual'); break;
    case 'data': $title = get_string('manage:data', 'local_eduvidual'); break;
	case 'style': $title = get_string('manage:style', 'local_eduvidual'); break;
	case 'users': $title = get_string('manage:users', 'local_eduvidual'); break;
	default: $title = get_string('Management', 'local_eduvidual');
}

$PAGE->set_title($org->name . ': ' . $title);
$PAGE->set_heading($org->name . ': ' . $title);

local_eduvidual::print_app_header();

echo "<div class=\"grid-eq-2 ui-eduvidual\">\n";
if (count($orgas) > 1) {
    local_eduvidual::print_org_selector('Manager', $org->orgid);
}
$actions = local_eduvidual::get_actions('manage');
local_eduvidual::print_act_selector($actions, $act);
echo "</div>\n";

if ($org) {
    $subpages = array_keys($actions);
    $includefile = $CFG->dirroot . '/local/eduvidual/pages/sub/manage_' . $act . '.php';
    if (in_array($act, $subpages) && file_exists($includefile)) {
        include($includefile);
    }
}

local_eduvidual::print_app_footer();

// Below this line we only collect functions

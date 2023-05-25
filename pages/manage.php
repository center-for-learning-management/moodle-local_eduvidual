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

define('READ_ONLY_SESSION', true);

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');

$orgid = required_param('orgid', PARAM_INT);
$org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));
$context = \context_coursecat::instance($org->categoryid);

require_capability('local/eduvidual:canmanage', $context);

$act = optional_param('act', 'users', PARAM_TEXT);

$PAGE->set_context($context);
$PAGE->set_url('/local/eduvidual/pages/manage.php', array('act' => $act, 'orgid' => $orgid));
$PAGE->set_title(get_string('Management', 'local_eduvidual'));
$PAGE->set_heading(get_string('Management', 'local_eduvidual'));

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
	case 'login': $title = get_string('manage:login:action', 'local_eduvidual'); break;
	case 'stats': $title = get_string('manage:stats', 'local_eduvidual'); break;
	case 'style': $title = get_string('manage:style', 'local_eduvidual'); break;
	case 'subcats': $title = get_string('manage:subcats:title', 'local_eduvidual'); break;
	case 'users': $title = get_string('manage:users', 'local_eduvidual'); break;
    case 'webuntis': $title = get_string('manage:webuntis', 'local_eduvidual'); break;
	default: $title = get_string('Management', 'local_eduvidual');
}

$PAGE->set_title($org->name . ': ' . $title);
$PAGE->set_heading($org->name . ': ' . $title);

$orgurl = new \moodle_url('/course/index.php', array('categoryid' => $org->categoryid));
$PAGE->navbar->add($org->name, $orgurl);
$manageurl = new \moodle_url('/local/eduvidual/pages/manage.php', array('orgid' => $orgid));
$PAGE->navbar->add(get_string('Management', 'local_eduvidual'), $manageurl);

if (!empty($act)) {
	$PAGE->navbar->add($title, $PAGE->url);
}

echo $OUTPUT->header();

$actions = \local_eduvidual\locallib::get_actions('manage');
$subpages = array_keys($actions);
$includefile = $CFG->dirroot . '/local/eduvidual/pages/sub/manage_' . $act . '.php';
/*
\local_eduvidual\locallib::print_act_selector($actions, $act);
*/

$oactions = array();

foreach ($actions as $key => $action) {
	$oactions[] = array(
		'action' => $action,
		'key' => $key,
		'localized' => get_string($action, 'local_eduvidual'),
		'selected' => ($key == $act),
		'url' => new \moodle_url('/local/eduvidual/pages/manage.php', array('orgid' => $orgid, 'act' => $key)),
	);
}
echo $OUTPUT->render_from_template('local_eduvidual/manage_overview', array('actions' => $oactions));

if (!empty($act) && in_array($act, $subpages) && file_exists($includefile)) {
    include($includefile);
}
echo $OUTPUT->footer();

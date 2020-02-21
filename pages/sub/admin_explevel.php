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

defined('MOODLE_INTERNAL') || die;
if (!block_eduvidual::get('role') == "Administrator") die;

$params = (object) array(
    'moolevels' => array(),
    'questioncategories' => array()
);

$moolevels = explode(",", get_config('block_eduvidual', 'moolevels'));
$options = array(); //"<option value=\"\">" . get_string('none') . "</option>");
$result = $DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 10 ORDER BY name ASC', array());
foreach($result AS $role) {
	$params->moolevels[] = array(
        'roleid' => $role->id,
        'checked' => (in_array($role->id, $moolevels))?' checked':'',
        'name' => (($role->name != "")?$role->name:$role->shortname)
    );
}

$questioncategories = explode(",", get_config('block_eduvidual', 'questioncategories'));
$options = array(); //"<option value=\"\">" . get_string('none') . "</option>");
$top = $DB->get_record('question_categories', array('contextid' => 1, 'parent' => 0));
$result = $DB->get_records_sql('SELECT id,name FROM {question_categories} WHERE contextid=? AND parent=? ORDER BY name ASC', array(1, $top->id));

foreach($result AS $cat) {
	$params->questioncategories[] = array(
        'checked' => (in_array($cat->id, $questioncategories))?' checked':'',
        'catid' => $cat->id,
        'name' => $cat->name
    );
}

$params->formmodificators = json_decode(get_config('block_eduvidual', 'formmodificators'), JSON_NUMERIC_CHECK);
if (empty($params->formmodificators)) {
    // Set a default value.
    $params->formmodificators = array(
        array(
            'type' => 'quiz',
            'roleid' => 16,
            'ids_to_hide' => '',
            'ids_to_set' => '',
        ),
    );
}
for ($a = 0; $a < count($params->formmodificators); $a++) {
    $params->formmodificators[$a]['id'] = $a;
}

echo $OUTPUT->render_from_template(
    'block_eduvidual/admin_explevel',
    $params
);

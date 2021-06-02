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

defined('MOODLE_INTERNAL') || die;
if (!is_siteadmin()) die;

$params = (object) array(
    'questioncategories' => array()
);

$questioncategories = explode(",", get_config('local_eduvidual', 'questioncategories'));
$options = array(); //"<option value=\"\">" . get_string('none') . "</option>");
$top = $DB->get_record('question_categories', array('contextid' => 1, 'parent' => 0));
$result = $DB->get_records_sql('SELECT id,name FROM {question_categories} WHERE contextid=? AND parent=? ORDER BY name ASC', array(1, $top->id));

foreach($result AS $cat) {
    $supportcourse = get_config('local_eduvidual', 'questioncategory_' . $cat->id . '_supportcourse');
	$params->questioncategories[] = array(
        'checked' => (in_array($cat->id, $questioncategories))?' checked':'',
        'catid' => $cat->id,
        'name' => $cat->name,
        'supportcourse' => !empty($supportcourse) ? $supportcourse : '',
    );
}

echo $OUTPUT->render_from_template(
    'local_eduvidual/admin_questionbank',
    $params
);

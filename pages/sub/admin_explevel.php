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

echo $OUTPUT->render_from_template(
    'block_eduvidual/admin_explevel',
    $params
);
/*
?>
<h4><?php echo get_string('explevel:title', 'block_eduvidual'); ?></h4>
<p><?php echo get_string('explevel:description', 'block_eduvidual'); ?></p>
<p><?php echo get_string('explevel:select', 'block_eduvidual'); ?></p>
<?php
$moolevels = explode(",", get_config('block_eduvidual', 'moolevels'));
$options = array(); //"<option value=\"\">" . get_string('none') . "</option>");
$result = $DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 10 ORDER BY name ASC', array());
foreach($result AS $role) {
	$options[] = "\t<li><label><input type=\"checkbox\" name=\"moolevels[]\" value=\"" . $role->id . "\"" . ((in_array($role->id, $moolevels))?" checked":"") . " onclick=\"var inp = this; require(['block_eduvidual/admin'], function(ADMIN) { ADMIN.moolevels(inp); });\">" . (($role->name != "")?$role->name:$role->shortname) . "</label></li>";
}
?>
<ul>
<!--<select id="block_eduvidual_admin_moolevels" name="moolevels[]" multiple="multiple" size="<?php echo count($options); ?>" onclick="BLOCK_EDUVIDUAL_ADMIN.moolevels();"> -->
<?php echo implode("\n", $options); ?>
<!--</select>-->
</ul>

<h4><?php echo get_string('admin:questioncategories:title', 'block_eduvidual'); ?></h4>
<p><?php echo get_string('admin:questioncategories:description', 'block_eduvidual'); ?></p>
<?php
$questioncategories = explode(",", get_config('block_eduvidual', 'questioncategories'));
$options = array(); //"<option value=\"\">" . get_string('none') . "</option>");
$top = $DB->get_record('question_categories', array('contextid' => 1, 'parent' => 0));
$result = $DB->get_records_sql('SELECT id,name FROM {question_categories} WHERE contextid=? AND parent=? ORDER BY name ASC', array(1, $top->id));

foreach($result AS $cat) {
	$options[] = "\t<li><label><input type=\"checkbox\" name=\"questioncategories[]\" value=\"" . $cat->id . "\"" . ((in_array($cat->id, $questioncategories))?" checked":"") . " onclick=\"var inp = this; require(['block_eduvidual/admin'], function(ADMIN) { ADMIN.questioncategories(inp); });\">" . $cat->name . "</label></li>";
}
?>
<ul>
<?php echo implode("\n", $options); ?>
</ul>

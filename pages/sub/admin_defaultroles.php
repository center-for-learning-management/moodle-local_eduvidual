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

/*
if (optional_param('refreshroles', 0, PARAM_INT) == 1) {
    require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
    $memberships = $DB->get_records_sql('SELECT * FROM {block_eduvidual_orgid_userid} ORDER BY orgid ASC');
    foreach ($memberships AS $membership) {
        block_eduvidual_lib_enrol::role_set($membership->userid, $membership->orgid, $membership->role, true);
    }
    echo $OUTPUT->render_from_template('block_eduvidual/alert', array(
        'type' => 'success',
        'content' => get_string('ok'),
    ));
}
*/

$potentialroles_course = array_values($DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 50 ORDER BY r.name ASC', array()));
$potentialroles_org = array_values($DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 40  ORDER BY name ASC', array()));
$rolestoset_course = array(
    array('roleidentifier' => 'parent'),
    array('roleidentifier' => 'student'),
    array('roleidentifier' => 'teacher'),
);
$rolestoset_org = array(
    array('roleidentifier' => 'manager'),
    array('roleidentifier' => 'parent'),
    array('roleidentifier' => 'student'),
    array('roleidentifier' => 'teacher'),
);
foreach($rolestoset_course AS &$roletoset) {
    $defaultrole = get_config('block_eduvidual', 'defaultrole' . $roletoset['roleidentifier']);
    $roletoset['rolename'] = get_string('defaultroles:course:' . $roletoset['roleidentifier'], 'block_eduvidual');
    $roletoset['options'] = unserialize(serialize($potentialroles_course));
    foreach($roletoset['options'] AS &$option) {
        if ($defaultrole == $option->id) {
            $option->selected = ' selected="selected"';
        }
    }
}
foreach($rolestoset_org AS &$roletoset) {
    $defaultrole = get_config('block_eduvidual', 'defaultorgrole' . $roletoset['roleidentifier']);
    $roletoset['rolename'] = get_string('defaultroles:orgcategory:' . $roletoset['roleidentifier'], 'block_eduvidual');
    $roletoset['options'] = unserialize(serialize($potentialroles_org));
    foreach($roletoset['options'] AS &$option) {
        if ($defaultrole == $option->id) {
            $option->selected = ' selected="selected"';
        }
    }
}

echo $OUTPUT->render_from_template('block_eduvidual/admin_defaultroles', array(
    'rolestoset_course' => $rolestoset_course,
    'rolestoset_org' => $rolestoset_org,
));
/*
?>
<h4><?php echo get_string('defaultroles:course:title', 'block_eduvidual'); ?></h4>
<p><?php echo get_string('defaultroles:course:description', 'block_eduvidual'); ?></p>
<h5><?php echo get_string('defaultroles:course:teacher', 'block_eduvidual'); ?></h5>
<?php
$defaultroleteacher = get_config('block_eduvidual', 'defaultroleteacher');
if ($defaultroleteacher == '') {
	$defaultroleteacher = 3;
	set_config('defaultroleteacher', $defaultroleteacher, 'block_eduvidual');
}
$roles = $DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 50 ORDER BY r.name ASC', array());
$options = array();
foreach($roles AS $role) {
	$options[] = "\t<option value=\"" . $role->id . "\"" . (($role->id == $defaultroleteacher)?" selected":"") . ">" . (($role->name != "")?$role->name:$role->shortname) . "</option>";
}

?>
<select id="block_eduvidual_admin_defaultroleteacher" onchange="var e = this; require(['block_eduvidual/admin'], function(ADMIN) { ADMIN.defaultrole('teacher', e.value); }); return false;">
<?php echo implode("\n", $options); ?>
</select>

<h5><?php echo get_string('defaultroles:course:student', 'block_eduvidual'); ?></h5>
<?php
$defaultrolestudent = get_config('block_eduvidual', 'defaultrolestudent');
if ($defaultrolestudent == '') {
	$defaultrolestudent = 5;
	set_config('defaultrolestudent', $defaultrolestudent, 'block_eduvidual');
}
$options = array();
foreach($roles AS $role) {
	$options[] = "\t<option value=\"" . $role->id . "\"" . (($role->id == $defaultrolestudent)?" selected":"") . ">" . (($role->name != "")?$role->name:$role->shortname) . "</option>";
}

?>
<select id="block_eduvidual_admin_defaultrolestudent" onchange="var e = this; require(['block_eduvidual/admin'], function(ADMIN) { ADMIN.defaultrole('student', e.value); }); return false;">
<?php echo implode("\n", $options); ?>
</select>

<h5><?php echo get_string('defaultroles:course:parent', 'block_eduvidual'); ?></h5>
<?php
$defaultroleparent = get_config('block_eduvidual', 'defaultroleparent');
if ($defaultroleparent == '') {
	$defaultroleparent = 5;
	set_config('defaultroleparent', $defaultroleparent, 'block_eduvidual');
}
$options = array();
foreach($roles AS $role) {
	$options[] = "\t<option value=\"" . $role->id . "\"" . (($role->id == $defaultroleparent)?" selected":"") . ">" . (($role->name != "")?$role->name:$role->shortname) . "</option>";
}

?>
<select id="block_eduvidual_admin_defaultroleparent" onchange="var e = this; require(['block_eduvidual/admin'], function(ADMIN) { ADMIN.defaultrole('parent', e.value); }); return false;">
<?php echo implode("\n", $options); ?>
</select>

<h4><?php echo get_string('defaultroles:orgcategory:title', 'block_eduvidual'); ?></h4>
<p><?php echo get_string('defaultroles:orgcategory:description', 'block_eduvidual'); ?></p>
<h5><?php echo get_string('defaultroles:orgcategory:manager', 'block_eduvidual'); ?></h5>
<ul>
<?php
$managersroles = explode(",", get_config('block_eduvidual', 'managersroles'));
$options = array(); //"<option value=\"\">" . get_string('none') . "</option>");
$result = $DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 40  ORDER BY name ASC', array());
foreach($result AS $role) {
	$options[] = "\t<li><label><input type=\"checkbox\" name=\"managersroles[]\" value=\"" . $role->id . "\"" . ((in_array($role->id, $managersroles))?" checked":"") . " onchange=\"var e = this; require(['block_eduvidual/admin'], function(ADMIN) { ADMIN.orgroles(e, 'managers'); }); return false;\">" . (($role->name != "")?$role->name:$role->shortname) . "</label></li>";
}
echo implode("\n", $options);
?>
</ul>
<h5><?php echo get_string('defaultroles:orgcategory:teacher', 'block_eduvidual'); ?></h5>
<ul>
<?php
$teachersroles = explode(",", get_config('block_eduvidual', 'teachersroles'));
$options = array(); //"<option value=\"\">" . get_string('none') . "</option>");
$result = $DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 40  ORDER BY name ASC', array());
foreach($result AS $role) {
	$options[] = "\t<li><label><input type=\"checkbox\" name=\"teachersroles[]\" value=\"" . $role->id . "\"" . ((in_array($role->id, $teachersroles))?" checked":"") . " onchange=\"var e = this; require(['block_eduvidual/admin'], function(ADMIN) { ADMIN.orgroles(e, 'teachers'); }); return false;\">" . (($role->name != "")?$role->name:$role->shortname) . "</label></li>";
}
echo implode("\n", $options);
?>
</ul>
<h5><?php echo get_string('defaultroles:orgcategory:student', 'block_eduvidual'); ?></h5>
<ul>
<?php
$studentsroles = explode(",", get_config('block_eduvidual', 'studentsroles'));
$options = array(); //"<option value=\"\">" . get_string('none') . "</option>");
$result = $DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 40  ORDER BY name ASC', array());
foreach($result AS $role) {
	$options[] = "\t<li><label><input type=\"checkbox\" name=\"studentsroles[]\" value=\"" . $role->id . "\"" . ((in_array($role->id, $studentsroles))?" checked":"") . " onchange=\"var e = this; require(['block_eduvidual/admin'], function(ADMIN) { ADMIN.orgroles(e, 'students'); }); return false;\">" . (($role->name != "")?$role->name:$role->shortname) . "</label></li>";
}
echo implode("\n", $options);
?>
</ul>
<h5><?php echo get_string('defaultroles:orgcategory:parent', 'block_eduvidual'); ?></h5>
<ul>
<?php
$parentsroles = explode(",", get_config('block_eduvidual', 'parentssroles'));
$options = array(); //"<option value=\"\">" . get_string('none') . "</option>");
$result = $DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 40  ORDER BY name ASC', array());
foreach($result AS $role) {
	$options[] = "\t<li><label><input type=\"checkbox\" name=\"parentsroles[]\" value=\"" . $role->id . "\"" . ((in_array($role->id, $parentsroles))?" checked":"") . " onchange=\"var e = this; require(['block_eduvidual/admin'], function(ADMIN) { ADMIN.orgroles(e, 'parents'); }); return false;\">" . (($role->name != "")?$role->name:$role->shortname) . "</label></li>";
}
echo implode("\n", $options);
?>
</ul>

<a href="<?php echo $CFG->wwwroot; ?>/blocks/eduvidual/pages/admin.php?act=defaultroles&refreshroles=1" class="btn btn-primary">
    <?php echo get_string('defaultroles:refreshroles', 'block_eduvidual'); ?>
</a>

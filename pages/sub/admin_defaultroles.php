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
if (!is_siteadmin()) die;

$potentialroles_course = array_values($DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = ? ORDER BY r.name ASC', array(CONTEXT_COURSE)));
$potentialroles_org = array_values($DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = ?  ORDER BY name ASC', array(CONTEXT_COURSECAT)));
$potentialroles_global = array_values($DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = ?  ORDER BY name ASC', array(CONTEXT_SYSTEM)));

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
$rolestoset_global = array(
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
foreach($rolestoset_global AS &$roletoset) {
    $defaultrole = get_config('block_eduvidual', 'defaultglobalrole' . $roletoset['roleidentifier']);
    $roletoset['rolename'] = get_string('defaultroles:global:' . $roletoset['roleidentifier'], 'block_eduvidual');
    $roletoset['options'] = unserialize(serialize($potentialroles_global));
    foreach($roletoset['options'] AS &$option) {
        if ($defaultrole == $option->id) {
            $option->selected = ' selected="selected"';
        }
    }
}

echo $OUTPUT->render_from_template('block_eduvidual/admin_defaultroles', array(
    'rolestoset_course' => $rolestoset_course,
    'rolestoset_org' => $rolestoset_org,
    'rolestoset_global' => $rolestoset_global,
));

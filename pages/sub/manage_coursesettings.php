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

$sql = "SELECT r.*
            FROM {role} AS r, {role_context_levels} AS rcl
            WHERE r.id=rcl.roleid
                AND rcl.contextlevel = ?
            ORDER BY r.name ASC";
$roles = array_values($DB->get_records_sql($sql, array(CONTEXT_COURSE)));

foreach ($roles as &$role) {
    $override = $DB->get_record('local_eduvidual_overrides', array('orgid' => $org->orgid, 'field' => 'courserole_' . $role->id . '_name'));
    //$role->showname = (!empty($role->name) ? $role->name : $role->shortname);
    if (!empty($override->value)) {
        $role->override = $override->value;
    }
}

$bbb_serverurl = $DB->get_record('local_eduvidual_overrides', array('orgid' => $org->orgid, 'field' => 'bigbluebuttonbn_server_url'));
$bbb_sharedsecret = $DB->get_record('local_eduvidual_overrides', array('orgid' => $org->orgid, 'field' => 'bigbluebuttonbn_shared_secret'));

echo $OUTPUT->render_from_template(
    'local_eduvidual/manage_coursesettings',
    (object)array(
        'bbb_serverurl' => !empty($bbb_serverurl->value) ? $bbb_serverurl->value : '',
        'bbb_sharedsecret' => !empty($bbb_sharedsecret->value) ? $bbb_sharedsecret->value : '',
        'has_bbb_installed' => file_exists($CFG->dirroot . '/mod/bigbluebuttonbn/version.php'),
        'overrideroles' => $roles,
        'orgid' => $org->orgid,
        'wwwroot' => $CFG->wwwroot,
    )
);

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
 * @copyright  2018-2020 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/dataformatlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

$orgid = required_param('orgid', PARAM_INT);
$userids = explode(',', required_param('userids', PARAM_TEXT));
$dataformat = required_param('dataformat', PARAM_ALPHA);

block_eduvidual::set_org($orgid);

if (block_eduvidual::get('orgrole') != "Manager" && block_eduvidual::get('role') != 'Administrator') {
    $OUTPUT->header();
    $OUTPUT->render_from_template('block_eduvidual/alert', array(
        'type' => 'warning',
        'content' => get_string('js:missing_permission', 'block_eduvidual'),
    ));
    $OUTPUT->footer();
    exit;
}
$columns = array(
    'id' => 'id',
    'username' => 'username',
    'email' => 'email',
    'firstname' => 'firstname',
    'lastname' => 'lastname',
    'role' => 'role',
    'bunch' => 'bunch',
    'secret' => 'secret',
);

list($insql, $params) = $DB->get_in_or_equal($userids);
$sql = "SELECT u.id,u.username,u.email,u.firstname,u.lastname,ou.role,ub.bunch
            FROM {user} u, {block_eduvidual_orgid_userid} ou, {block_eduvidual_userbunches} ub
            WHERE u.id=ou.userid
                AND u.id=ub.userid
                AND ou.role IS NOT NULL
                AND ou.orgid = ?
                AND u.id $insql
            ORDER BY u.lastname ASC,u.firstname ASC";

// Add orgid at the beginning of params.
array_unshift($params, $orgid);
$rs = $DB->get_recordset_sql($sql, $params);

download_as_dataformat('users_' . date("Ymd-His"), $dataformat, $columns, $rs, function($record) {
    $r = (object) array('id' => $record->id);
    profile_load_data($r);
    $record->secret = $record->id . '#' . $r->profile_field_secret;
    return $record;
});
$rs->close();

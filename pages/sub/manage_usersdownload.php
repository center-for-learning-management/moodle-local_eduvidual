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
 * @copyright  2018-2020 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();


$orgid = required_param('orgid', PARAM_INT);
$userids = explode(',', required_param('userids', PARAM_TEXT));
$dataformat = required_param('dataformat', PARAM_ALPHA);

if (\local_eduvidual\locallib::get_orgrole($orgid) != "Manager" && !is_siteadmin()) {
    $OUTPUT->header();
    $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'type' => 'warning',
        'content' => get_string('js:missing_permission', 'local_eduvidual'),
    ));
    $OUTPUT->footer();
    exit;
} else {
    $columns = array(
        'id' => 'id',
        'auth' => 'auth',
        'username' => 'username',
        'email' => 'email',
        'firstname' => 'firstname',
        'lastname' => 'lastname',
        'role' => 'role',
        'lastlogin' => 'lastlogin',
        'cohorts_add' => 'cohorts_add',
        'cohorts_remove' => 'cohorts_remove',
        'secret' => 'secret',
        'password' => 'password',
        'forcechangepassword' => 'forcechangepassword',
    );

    list($insql, $params) = $DB->get_in_or_equal($userids);
    $sql = "SELECT u.id,u.auth,u.username,u.email,u.firstname,u.lastname,ou.role,u.lastlogin
                FROM {user} u, {local_eduvidual_orgid_userid} ou
                WHERE u.id=ou.userid
                    AND ou.role IS NOT NULL
                    AND ou.orgid = ?
                    AND u.id $insql
                ORDER BY u.lastname ASC,u.firstname ASC";

    // Add orgid at the beginning of params.
    array_unshift($params, $orgid);
    $rs = $DB->get_recordset_sql($sql, $params);

    \core\dataformat::download_data('users_' . date("Ymd-His"), $dataformat, $columns, $rs, function($record) {
        global $DB, $orgid;
        $r = (object)array('id' => $record->id);
        profile_load_data($r);
        $org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));
        $context = \context_coursecat::instance($org->categoryid);
        if (!empty($context->id)) {
            $sql = "SELECT c.id,c.name
                FROM {cohort} c, {cohort_members} cm
                WHERE c.id=cm.cohortid
                    AND cm.userid=?
                    AND c.contextid=?";
            $cohorts = $DB->get_records_sql($sql, array($record->id, $context->id));
            $cohorts_ = array();
            foreach ($cohorts as $cohort) {
                $cohorts_[] = $cohort->name;
            }
            $record->cohorts_add = implode(',', $cohorts_);
        } else {
            $record->cohorts_add = '';
        }
        $record->cohorts_remove = '';

        $record->lastlogin = userdate($record->lastlogin, get_string('strftimedatetimeshort', 'langconfig'));
        $record->secret = $record->id . '#' . $r->profile_field_secret;
        $record->password = '';
        $record->forcechangepassword = 0;
        return $record;
    });
    $rs->close();

}

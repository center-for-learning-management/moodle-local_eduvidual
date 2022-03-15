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

$action = optional_param('action', 'usermap', PARAM_TEXT); // tab the will be shown initially.

$orgmaps = array_values($DB->get_records('local_webuntis_orgmap', [ 'connected' => 1, 'orgid' => $orgid ]));

if (count($orgmaps) > 1) {
    foreach ($orgmaps as $orgmap) {
        if ($orgmap->orgid == $orgid) {
            $ORGMAP = $orgmap;
        }
    }
} else if (count($orgmaps) > 0) {
    $ORGMAP = $orgmaps[0];
} else {
    $ORGMAP = (object) [];
}

if (empty($ORGMAP->orgid) || $ORGMAP->orgid != $orgid) {
    throw new \moodle_exception('missing_permission', 'local_webuntis');
}

\local_webuntis\tenant::load($ORGMAP->tenant_id, false);
$USERMAP->sync();

$params = (object)[
    'sitename' => $CFG->shortname,
    'usermaps' => [],
    'uses_eduvidual' => \local_webuntis\locallib::uses_eduvidual(),
    'wwwroot' => $CFG->wwwroot,
];

$dbparams = array('tenant_id' => $TENANT->get_tenant_id());
$params->usermaps = array_values($DB->get_records('local_webuntis_usermap', $dbparams, 'lastname ASC,firstname ASC'));
foreach ($params->usermaps as $usermap) {
    if (!empty($usermap->userid)) {
        $user = \core_user::get_user($usermap->userid);
        $user->fullname = fullname($user);
        $usermap->moodleuser = $user;
    }
}
$actions = [
    (object) [
            'active' => ($action == 'usermap'),
            'label' => get_string('admin:usermaps:pagetitle', 'local_webuntis'),
            'relativepath' => "/local/eduvidual/pages/manage.php?act=webuntis&orgid=$orgid&action=usermap",
        ],
    (object) [
            'active' => ($action == 'create'),
            'label' => get_string('admin:usersync:usercreate', 'local_webuntis'),
            'relativepath' => "/local/eduvidual/pages/manage.php?act=webuntis&orgid=$orgid&action=create",
        ],
    (object) [
            'active' => ($action == 'purge'),
            'label' => get_string('admin:usersync:userpurge', 'local_webuntis'),
            'relativepath' => "/local/eduvidual/pages/manage.php?act=webuntis&orgid=$orgid&action=purge",
        ],
    (object) [
            'active' => ($action == 'roles'),
            'label' => get_string('admin:usersync:userroles', 'local_webuntis'),
            'relativepath' => "/local/eduvidual/pages/manage.php?act=webuntis&orgid=$orgid&action=roles",
        ]
];
echo $OUTPUT->render_from_template('local_webuntis/navbar', [ 'actions' => $actions ]);

switch ($action) {
    case 'create':
        $sql = "SELECT *
                    FROM {local_webuntis_usermap}
                    WHERE tenant_id = ?
                        AND (userid = 0 OR userid IS NULL)
                    ORDER BY lastname ASC, firstname ASC";
        $params->notmappedusers = array_values($DB->get_records_sql($sql, [ $TENANT->get_tenant_id() ]));
        foreach ($params->notmappedusers as $nmu) {
            $nmu->missingdata = (empty($nmu->email) || empty($nmu->firstname) || empty($nmu->lastname));
            if (!empty($nmu->missingdata)) {
                $chkusers = array_values($DB->get_records('user', [ 'email' => strtolower($nmu->email)]));
                $nmu->exists = (count($chkusers) > 0);
            }
        }
        echo $OUTPUT->render_from_template('local_webuntis/usersync_create', $params);
    break;
    case 'purge':
        $params->orgid = $orgid;
        $sql = "SELECT *
                    FROM {user}
                    WHERE id IN (
                        SELECT userid
                            FROM {local_eduvidual_orgid_userid}
                            WHERE orgid = ?
                    ) AND id IN (
                        SELECT userid
                            FROM {local_webuntis_usermap}
                            WHERE tenant_id = ?
                                AND userid > 0
                                AND userid IS NOT NULL
                    )
                    AND id <> ?
                    AND id > 1
                    AND id NOT IN ($CFG->siteadmins)
                    AND deleted = 0
                    ORDER BY lastname ASC, firstname ASC";
        $params->purgecandidates = array_values($DB->get_records_sql($sql, [ $params->orgid, $TENANT->get_tenant_id(), $USER->id ]));
        $roles = $DB->get_records_sql('SELECT userid,role FROM {local_eduvidual_orgid_userid} WHERE orgid=?', [ 'orgid' => $params->orgid ]);

        foreach ($params->purgecandidates as $pc) {
            $u = \core_user::get_user($pc->id);
            $pc->profileimage = $OUTPUT->user_picture($u, array('size' => 30));
            $pc->role = $roles[$pc->id]->role;
        }
        echo $OUTPUT->render_from_template('local_webuntis/usersync_purge', $params);
    break;
    case 'roles':
        $params->orgid = $orgid;

        $params->mappedusers = [];
        $fields = implode(',', [
            "webuntis.remoteuserid ruid",
            "webuntis.email w_email",
            "webuntis.firstname w_firstname",
            "webuntis.lastname w_lastname",
            "webuntis.remoteuserrole w_role",
            "moodle.id m_id",
            "moodle.email m_email",
            "moodle.firstname m_firstname",
            "moodle.lastname m_lastname",
            "moodle.username m_username",
        ]);
        $sql = "SELECT $fields
                    FROM {local_webuntis_usermap} webuntis, {user} moodle
                    WHERE webuntis.tenant_id = ?
                        AND webuntis.userid > 0
                        AND webuntis.userid = moodle.id
                    ORDER BY webuntis.lastname ASC, webuntis.firstname ASC";
        $mappedusers = array_values($DB->get_records_sql($sql, [ $TENANT->get_tenant_id() ]));

        foreach ($mappedusers as $mu) {
            $mu->w_role = ucfirst($mu->w_role);
            $role = [];
            if (!empty($mu->m_id)) {
                $u = \core_user::get_user($mu->m_id);
                $mu->m_profileimage = $OUTPUT->user_picture($u, array('size' => 30));
                $mu->m_role = \local_eduvidual\locallib::get_orgrole($params->orgid, $mu->m_id);
                $mu->role_differ = $mu->w_role != 'Administrator' && ($mu->w_role != $mu->m_role);
            }
            $params->mappedusers[] = $mu;
        }

        echo $OUTPUT->render_from_template('local_webuntis/usersync_roles', $params);
    break;
    default:
        echo $OUTPUT->render_from_template('local_webuntis/landingusermaps', $params);
}

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

$mform = new \local_eduvidual\licence_form($PAGE->url->__toString());

if ($data = $mform->get_data()) {
    $orgids = explode("\n", $data->orgids);
    $added = array(); $failed = array();
    for ($a = 0; $a < count($orgids); $a++) {
        $lic = \local_eduvidual\lib_licence::add_licence($orgids[$a], $data->comment, $data->expiry);
        if (!empty($lic->id)) $added[] = $orgids[$a];
        else $failed[] = $orgids[$a];
    }
    if (count($added) > 0) {
        echo $OUTPUT->render_from_template('local_eduvidual/alert', [
            'type' => 'success',
            'content' => get_string('admin:licence:added', 'local_eduvidual', [ 'added' => count($added)]),
        ]);
    }
    if (count($failed) > 0) {
        echo $OUTPUT->render_from_template('local_eduvidual/alert', [
            'type' => 'danger',
            'content' => get_string('admin:licence:failed', 'local_eduvidual', [ 'failed' => count($failed)]) . '<br />' . implode(', ', $failed),
        ]);
    }
}

$revoke = optional_param('revoke', 0, PARAM_INT);
if (!empty($revoke)) {
    $DB->set_field('local_eduvidual_org_lic', 'revokedby', $USER->id, [ 'id' => $revoke ]);
    $DB->set_field('local_eduvidual_org_lic', 'timerevoked', time(), [ 'id' => $revoke ]);
    echo $OUTPUT->render_from_template('local_eduvidual/alert', [
        'type' => 'success',
        'content' => get_string('admin:licence:revoked', 'local_eduvidual'),
    ]);
}

$dbparams = [];
$sql = "SELECT *
            FROM {local_eduvidual_org_lic} ol";
$filterorgid = optional_param('filterorgid', 0, PARAM_INT);
if (!empty($filterorgid)) {
    $dbparams['orgid'] = $filterorgid;
    $sql .= " WHERE orgid = :orgid";
}
$sql .= " ORDER BY id DESC LIMIT 0, 50";
$licences = $DB->get_records_sql($sql, $dbparams);
$userby = array();
foreach ($licences as &$licence) {
    $licence->revokeurl = $PAGE->url;
    $licence->revokeurl->param('revoke', $licence->id);
    $licence->revokeurl = $licence->revokeurl->__toString();
    if (empty($userby[$licence->createdby])) {
        $byuser = \core_user::get_user($licence->createdby);
        $fullname = \fullname($byuser);
        $userby[$licence->createdby] = "<a href=\"$CFG->wwwroot/profile/view.php?id=$licence->createdby\">$fullname</a>";
    }
    if (empty($userby[$licence->revokedby])) {
        $byuser = \core_user::get_user($licence->revokedby);
        $fullname = \fullname($byuser);
        $userby[$licence->revokedby] = "<a href=\"$CFG->wwwroot/profile/view.php?id=$licence->revokedby\">$fullname</a>";
    }
    $licence->createdby = $userby[$licence->createdby];
    $licence->revokedby = $userby[$licence->revokedby];
}

$enable = optional_param('licencesystem_enable', 0, PARAM_INT);
if ($enable == 1) {
    \local_eduvidual\lib_licence::system_status($enable);
    echo $OUTPUT->render_from_template('local_eduvidual/alert', [
        'type' => 'success',
        'content' => get_string('admin:licencesystem:enabled', 'local_eduvidual'),
    ]);
}
if ($enable == -1) {
    \local_eduvidual\lib_licence::system_status($enable);
    echo $OUTPUT->render_from_template('local_eduvidual/alert', [
        'type' => 'warning',
        'content' => get_string('admin:licencesystem:disabled', 'local_eduvidual'),
    ]);
}

$enableurl = $PAGE->url;
$enableurl->param('licencesystem_enable', 1);
$disableurl = $PAGE->url;
$disableurl->param('licencesystem_enable', -1);
$params = [
    'disableurl' => $disableurl->__toString(),
    'enableurl' => $enableurl->__toString(),
    'filterorgid' => $filterorgid,
    'form' => $mform->render(),
    'licences' => array_values($licences),
    'licencesystem_enabled' => \local_eduvidual\lib_licence::system_status() ? 1 : 0,
    'pageurl' => $PAGE->url->__toString(),
];

echo $OUTPUT->render_from_template('local_eduvidual/admin_licence', $params);

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

$userid = $USER->id;

$permiturl = $PAGE->url;
$permiturl->param('toggle', '1');

$accept = optional_param('accept', '', PARAM_ALPHA);
$showmsg = optional_param('showmsg', 0, PARAM_INT);
$toggle = optional_param('toggle', 0, PARAM_INT);

$accept = ($accept == 'on') ? 1 : 0;

$record = $DB->get_record('local_eduvidual_educloud', ['orgid' => $org->orgid]);

switch ($showmsg) {
    case -1:
        echo $OUTPUT->render_from_template('local_eduvidual/alert', [
            'type' => 'danger',
            'content' => get_string('educloud:toggle:failed', 'local_eduvidual'),
        ]);
        break;
    case 1:
        echo $OUTPUT->render_from_template('local_eduvidual/alert', [
            'type' => 'success',
            'content' => get_string('educloud:toggle:success', 'local_eduvidual'),
        ]);
        break;
}

if (!empty($accept)) {
    $record = \local_eduvidual\educloud\school::accept($org->orgid);
}

if (!empty($toggle)) {
    if (!is_siteadmin()) {
        throw new \moodle_exception('educloud:exception:onlyadmins', 'local_eduvidual');
    }
    if (empty($record->enabled)) {
        $record = \local_eduvidual\educloud\school::enable($org->orgid);
        $permiturl = $PAGE->url;
        $permiturl->remove_params(['toggle']);
        $permiturl->param('showmsg', !empty($record->enabled) ? 1 : -1);
        redirect($url);
    } else {
        $record = \local_eduvidual\educloud\school::disable($org->orgid);
        $permiturl->remove_params(['toggle']);
        $permiturl->param('showmsg', empty($record->enabled) ? 1 : -1);
        redirect($url);
    }
}

$params = [
    'acceptedby' => $record->acceptedby,
    'canpermit' => is_siteadmin() ? 1 : 0,
    'isaccepted' => $record->accepted,
    'ispermitted' => !empty($record->permitted) ? 1 : 0,
    'orgid' => $org->orgid,
    'orgname' => $org->name,
    'permiturl' => $permiturl->__toString(),
    'wwwroot' => $CFG->wwwroot,
];

if (!empty($record->acceptedby)) {
    $acceptedbyuser = \core_user::get_user($record->acceptedby);
    $params['acceptedbylink'] = "$CFG->wwwroot/user/profile.php?id=$record->acceptedby";
    $params['acceptedbytime'] = userdate($record->accepted, get_string('strftimedate', 'core_langconfig'));
    $params['acceptedbyuser'] = \fullname($acceptedbyuser);
}

echo $OUTPUT->render_from_template('local_eduvidual/manage_educloud', $params);

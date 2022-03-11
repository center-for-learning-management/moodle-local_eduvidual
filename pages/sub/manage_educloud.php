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

//print_r(\local_eduvidual\lib_educloud::api_get_user('schrenk', 'lastname'));
\local_eduvidual\lib_educloud::api_create_user($USER->id);

$url = $PAGE->url;
$url->param('toggle', '1');

$toggle = optional_param('toggle', 0, PARAM_INT);
$showmsg = optional_param('showmsg', 0, PARAM_INT);

$record = $DB->get_record('local_eduvidual_educloud', [ 'orgid' => $org->orgid]);

switch($showmsg) {
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

if (!empty($toggle)) {
    if (!is_siteadmin()) {
        throw new \moodle_exception('educloud:exception:onlyadmins', 'local_eduvidual');
    }
    if (empty($record->id)) {
        $record = \local_eduvidual\lib_educloud::org_enable($org->orgid);
        $url = $PAGE->url;
        $url->remove_params(['toggle']);
        $url->param('showmsg', !empty($record->id) ? 1 : -1);
        redirect($url);
    } else {
        $record = \local_eduvidual\lib_educloud::org_disable($org->orgid);
        $url->remove_params(['toggle']);
        $url->param('showmsg', empty($record->id) ? 1 : -1);
        redirect($url);
    }
}

$params = [
    'canactivate' => is_siteadmin() ? 1 : 0,
    'isactive' => !empty($record->id) ? 1 : 0,
    'toggleurl' => $url->__toString(),
];

echo $OUTPUT->render_from_template('local_eduvidual/manage_educloud', $params);

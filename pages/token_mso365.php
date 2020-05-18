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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Show a list of Moodle Office 365 Webservices-Tokens of the current user.

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');


$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/blocks/eduvidual/pages/token_mso365.php', array());
$PAGE->set_title('Moodle Office 365 Webservices');
$PAGE->set_heading('Moodle Office 365 Webservices');

block_eduvidual::print_app_header();

$service = $DB->get_record('external_services', array('name' => 'Moodle Office 365 Webservices'));
if (empty($service->id)) {
    echo $OUTPUT->render_from_template('block_eduvidual/alert', array(
        'content' => 'No such webservice.',
        'type' => 'danger',
        'url' => $CFG->wwwroot . '/my',
    ));
} else {
    $user = $USER;
    if (block_eduvidual::get('role') == 'Administrator' && optional_param('userid', 0, PARAM_INT) > 0) {
        $user = \core_user::get_user(optional_param('userid', 0, PARAM_INT));
    }
    $tokens = array_values($DB->get_records('external_tokens', array('externalserviceid' => $service->id, 'userid' => $user->id)));
    if (count($tokens) == 0) {
        $svc = $DB->get_record('external_services', array('component' => 'local_o365'));
        if (!empty($svc->id)) {
            $validuntil = 0;
            external_generate_token(EXTERNAL_TOKEN_PERMANENT, $svc->id,
                            $user->id, context_system::instance(), $validuntil);
        }
        $tokens = array_values($DB->get_records('external_tokens', array('externalserviceid' => $service->id, 'userid' => $user->id)));
    }
    echo $OUTPUT->render_from_template('block_eduvidual/token_mso365', array(
        'user' => $user,
        'tokens' => $tokens,
    ));
}



block_eduvidual::print_app_footer();

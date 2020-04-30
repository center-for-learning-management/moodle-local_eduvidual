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

// We require a login, check if we belong to an org and launch edutube afterwards.

require_once('../../../config.php');
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('popup');
$PAGE->set_url('/blocks/eduvidual/pages/edutube.php', array());
$PAGE->set_title(get_string('edutube:title', 'block_eduvidual'));
$PAGE->set_heading(get_string('edutube:title', 'block_eduvidual'));

$mediaspaceurl = get_config('block_eduvidual', 'kalturamediaspaceurl');
$mediaspacesecret = get_config('block_eduvidual', 'kalturamediaspacesecret');

echo $OUTPUT->header();
if (empty($mediaspaceurl) || empty($mediaspacesecret)) {
    echo $OUTPUT->render_from_template('block_eduvidual/alert', array(
        'content' => get_string('edutube:missing_configuration', 'block_eduvidual'),
        'type' => 'danger',
        'url' => '/my',
    ));
} else {
    $sql = "SELECT orgid
                FROM {block_eduvidual_orgid_userid}
                WHERE userid=?
                    AND orgid LIKE '______'
                    AND role IN ('Student', 'Teacher', 'Manager')";
    $memberships = $DB->get_records_sql($sql, array($USER->id));
    if (count($memberships) == 0) {
        echo $OUTPUT->render_from_template('block_eduvidual/alert', array(
            'content' => get_string('edutube:no_org', 'block_eduvidual', array('wwwroot' => $CFG->wwwroot)),
            'type' => 'warning',
            'url' => '/blocks/eduvidual/pages/register.php',
        ));
    } else {
        require_once($CFG->dirroot . '/blocks/eduvidual/classes/KMSSessionKey.class.php');
        // generate SSO login hash
        $hash = KMSSessionKey::createSessionKey($USER->id, 'viewerOnly', $mediaspacesecret, $expiry = 5, $extraUserInfo = array());
        // build mediaSpace SSO login URL
        $url = $mediaspaceurl . '/user/authenticate/sessionKey/' . $hash;
        // redirect
        header("Location: $url");
        exit();
    }
}
echo $OUTPUT->footer();

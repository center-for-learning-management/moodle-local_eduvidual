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
 * @copyright  2021 Center for Learningmanagement (http://www.lernmanagement.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../../config.php');
require_login();

$context = \context_user::instance($USER->id);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/eduvidual/pages/redirects/dataprivacyorgerror.php', array());
$PAGE->set_title(get_string('dataprivacyorgerror:pagetitle', 'local_eduvidual'));
$PAGE->set_heading(get_string('dataprivacyorgerror:pagetitle', 'local_eduvidual'));


$PAGE->navbar->add(get_string('profile'), new \moodle_url('/user/profile.php', array('id' => $USER->id)));
$PAGE->navbar->add(get_string('createnewdatarequest', 'tool_dataprivacy'), new \moodle_url('/admin/tool/dataprivacy/createdatarequest.php', array('type' => 2)));
$PAGE->navbar->add(get_string('dataprivacyorgerror:pagetitle', 'local_eduvidual'), $PAGE->url);


echo $OUTPUT->header();
$orgs = array_values(\local_eduvidual\locallib::get_organisations('*', false));

$sql = "SELECT u.*
            FROM {user} u, {local_eduvidual_orgid_userid} ou
            WHERE ou.userid = u.id
                AND ou.role = 'Manager'
                AND ou.orgid = :orgid";

foreach ($orgs as &$org) {
    $org->managers = array_values($DB->get_records_sql($sql, ['orgid' => $org->orgid]));
    foreach ($org->managers as &$manager) {
        $manager->userfullname = fullname($manager);
        $manager->userpicture = $OUTPUT->user_picture($manager, array('size' => 30));
    }
}

echo $OUTPUT->render_from_template('local_eduvidual/dataprivacyorgerror', array('orgs' => $orgs));

echo $OUTPUT->footer();

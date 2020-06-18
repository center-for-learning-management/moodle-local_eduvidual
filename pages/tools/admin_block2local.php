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
 * @copyright  2020 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Convert some things from block to local plugin.
 */

require_once('../../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/tools/admin_block2local.php', array());
$PAGE->set_title(get_string('admin:supportcourses', 'local_eduvidual'));
$PAGE->set_heading(get_string('admin:supportcourses', 'local_eduvidual'));

require_login();

if (!is_siteadmin()) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => get_string('access_denied', 'local_eduvidual'),
        'type' => 'danger'
    ));
    echo $OUTPUT->footer();
    die();
}


echo $OUTPUT->header();

try {
    echo "<h3>Transmit userextra to set_user_preference</h3>\n";
    echo "<ul>\n";
    $sql = "SELECT userid,background,backgroundcard
                FROM {local_eduvidual_userextra}";
    $extras = $DB->get_records_sql($sql, array());
    foreach ($extras AS $extra) {
        if (!empty($extra->background)) {
            echo "    <li>Setting background $extra->background for User #$extra->userid</li>\n";
            set_user_preference('local_eduvidual_background', $extra->background, $extra->userid);
        }
        if (!empty($extra->backgroundcard)) {
            echo "    <li>Setting background-card $extra->backgroundcard for User #$extra->userid</li>\n";
            set_user_preference('local_eduvidual_backgroundcard', $extra->backgroundcard, $extra->userid);
        }
    }

} catch(Exception $e) {
    echo "<li class=\"alert alert-danger\">" . $e->getMessage() . "</li>\n";
} finally {
    echo "</ul>\n";
}


try {
    echo "<h3>Ensure that all userbunches are synced to cohorts</h3>\n";
    echo "<ul>\n";
    $bunches = $DB->get_records_sql("SELECT * FROM {local_eduvidual_userbunches} ORDER BY orgid ASC", array());
    $org = "";
    foreach ($bunches as $bunch) {
        if (empty($org) || $org->orgid != $bunch->orgid) {
            $org = $DB->get_record('local_eduvidual_org', array('orgid' => $bunch->orgid));
        }
        if (!empty($org->categoryid)) {
            echo "<li>Add $bunch->userid to cohort $bunch->bunch of $org->orgid</li>\n";
            \local_eduvidual\lib_enrol::cohorts_add($bunch->userid, $org, $bunch->bunch);
        }
    }
} catch(Exception $e) {
    echo "<li class=\"alert alert-danger\">" . $e->getMessage() . "</li>\n";
} finally {
    echo "</ul>\n";
}

echo $OUTPUT->footer();

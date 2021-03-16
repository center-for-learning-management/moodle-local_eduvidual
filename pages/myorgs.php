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
 *             2020 onwards Zentrum für Lernmanagement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

$PAGE->set_url('/local/eduvidual/pages/myorgs.php', array());
$PAGE->set_context(\context_system::instance());
$PAGE->set_heading(get_string('categories:coursecategories', 'local_eduvidual'));
$PAGE->set_title(get_string('categories:coursecategories', 'local_eduvidual'));

echo $OUTPUT->header();

// Show list of my orgs.
if (is_siteadmin() && optional_param('showall', 0, PARAM_INT) == 1) {
    $sql = "SELECT orgid,'Administrator' AS role,name,categoryid,orgsize
            FROM {local_eduvidual_org}
            WHERE authenticated>0
            ORDER BY orgid ASC, name ASC";
    $params = array();
} else {
    $sql = "SELECT ou.orgid,ou.role,o.name,o.categoryid,o.orgsize
            FROM {local_eduvidual_org} o,
                 {local_eduvidual_orgid_userid} ou
            WHERE o.orgid=ou.orgid
                AND ou.userid=?
            ORDER BY o.orgid ASC, o.name ASC";
    $params = array($USER->id);
}

if (!empty($favorite = optional_param('favorite', 0, PARAM_INT))) {
    if ($favorite < 0) {
        \unset_user_preference('local_eduvidual_favorgid');
    } else {
        \set_user_preference('local_eduvidual_favorgid', $favorite);
    }
}
$favorgid = \local_eduvidual\locallib::get_favorgid();

$memberships = array_values($DB->get_records_sql($sql, $params));

foreach ($memberships AS &$membership) {
    $membership->isfavorite = ($membership->orgid == $favorgid);
    $membership->canmanage = is_siteadmin() || in_array($membership->role, array('Manager'));
    if ($membership->canmanage) {
        if (empty($managersactions)) {
            $_actions = \local_eduvidual\locallib::get_actions('manage', true);
            $actions = array_keys($_actions);
            $managersactions = array();
            foreach ($actions AS $action) {
                $managersactions[] = array(
                    'name' => $_actions[$action],
                    'url' => $CFG->wwwroot . '/local/eduvidual/pages/manage.php?act=' . $action,
                );
            }
        }
        $membership->actions = $managersactions;
        $membership->hasactions = true;
        $membership->filesize = \local_eduvidual\lib_manage::readable_filesize($membership->orgsize);
    }
    $membership->role = get_string('role:' . $membership->role, 'local_eduvidual');
}
echo $OUTPUT->render_from_template('local_eduvidual/user_orgs', array(
    'hasmultiple' => count($memberships) > 1,
    'isadmin' => is_siteadmin(),
    'memberships' => $memberships,
    'wwwroot' => $CFG->wwwroot
));

echo $OUTPUT->footer();

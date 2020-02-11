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

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

$current_orgid = optional_param('orgid', 0, PARAM_INT);
$orgas = block_eduvidual::get_organisations('*');
$checkedorg = block_eduvidual::get_organisations_check($orgas, $current_orgid);

$PAGE->set_url(new moodle_url('/blocks/eduvidual/pages/categories.php', array('orgid' => $current_orgid)));
//$PAGE->set_cacheable(false);

$PAGE->navbar->add(get_string('Browse_org', 'block_eduvidual'), new moodle_url('/blocks/eduvidual/pages/categories.php', array()));
if (!empty($current_orgid) && !empty($checkedorg->name)) {
    $org = $checkedorg;
    block_eduvidual::set_org($checkedorg->orgid);
    $PAGE->navbar->add($checkedorg->name, $PAGE->url);
    $PAGE->set_context(context_coursecat::instance($checkedorg->categoryid));
    $PAGE->set_pagelayout('coursecategory');
} else {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('standard');
}
$PAGE->set_heading(get_string('categories:coursecategories', 'block_eduvidual'));
$PAGE->set_title(get_string('categories:coursecategories', 'block_eduvidual'));
$PAGE->requires->css('/blocks/eduvidual/style/main.css');

block_eduvidual::print_app_header();

/*
if (empty($org)) {
    block_eduvidual::print_app_footer();
    exit;
}
*/
/* Old select-box.
echo "<div class=\"grid-eq-2\">\n";
if (count($orgas) > 1) {
    block_eduvidual::print_org_selector('*', $org->orgid);
}
echo "</div>\n";
*/


if (empty($current_orgid)) {
    // Show list of my orgs.
    if (is_siteadmin() && optional_param('showall', 0, PARAM_INT) == 1) {
        $sql = "SELECT orgid,'Administrator' AS role,name,categoryid,orgsize
                FROM {block_eduvidual_org}
                WHERE authenticated=1
                ORDER BY orgid ASC, name ASC";
        $params = array();
    } else {
        $sql = "SELECT ou.orgid,ou.role,o.name,o.categoryid,o.orgsize
                FROM {block_eduvidual_org} o,
                     {block_eduvidual_orgid_userid} ou
                WHERE o.orgid=ou.orgid
                    AND ou.userid=?
                ORDER BY o.orgid ASC, o.name ASC";
        $params = array($USER->id);
    }

    //$manageractions = block_eduvidual::get_actions('manage');
    $memberships = array_values($DB->get_records_sql($sql, $params));
    foreach ($memberships AS &$membership) {
        $membership->canmanage = is_siteadmin() || in_array($membership->role, array('Administrator', 'Manager'));
        if ($membership->canmanage) {
            if (empty($managersactions)) {
                $_actions = block_eduvidual::get_actions('manage', true);
                $actions = array_keys($_actions);
                $managersactions = array();
                foreach ($actions AS $action) {
                    $managersactions[] = array(
                        'name' => $_actions[$action],
                        'url' => $CFG->wwwroot . '/blocks/eduvidual/pages/manage.php?act=' . $action,
                    );
                }
            }
            $membership->actions = $managersactions;
            $membership->hasactions = true;
            require_once($CFG->dirroot . '/blocks/eduvidual/lib_manage.php');
            $membership->filesize = \block_eduvidual\lib_manage::readable_filesize($membership->orgsize);
        }
        $membership->role = get_string('role:' . $membership->role, 'block_eduvidual');
    }
    echo $OUTPUT->render_from_template('block_eduvidual/user_orgs', array(
        'isadmin' => (block_eduvidual::get('role') == 'Administrator'),
        'memberships' => $memberships,
        'wwwroot' => $CFG->wwwroot
    ));
} else {
    ?>
    <h3 class="block_eduvidual_courses_title"><?php echo get_string('categories:coursecategories', 'block_eduvidual'); ?></h3>
    <div class="ul-eduvidual-courses" data-orgid="<?php echo $org->orgid; ?>">
    <?php
    block_eduvidual::add_script_on_load('require(["block_eduvidual/user"], function(USER) { USER.loadCategory(' . $org->categoryid . '); });');
    echo get_string('loading');
    ?></div>
    <?php
}

block_eduvidual::print_app_footer();

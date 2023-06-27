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

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');

$orgid = optional_param('orgid', 0, PARAM_INT);
$cohort = optional_param('cohort', '', PARAM_TEXT);
$format = optional_param('format', 'list', PARAM_ALPHANUM);
$orderby = optional_param('orderby', 'lastname', PARAM_ALPHANUM);
$orderasc = optional_param('orderasc', 'asc', PARAM_ALPHANUM);

$org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));
$context = \context_coursecat::instance($org->categoryid);
$PAGE->set_context($context);

$PAGE->set_pagelayout('standard');
$PAGE->set_url(new \moodle_url('/local/eduvidual/pages/manage_userlists.php', array('orgid' => $orgid, 'cohort' => $cohort, 'format' => $format, 'orderby' => $orderby)));
$PAGE->set_title(!empty($cohorto->name) ? $cohorto->name : get_string('manage:userlist', 'local_eduvidual', $org));
$PAGE->set_heading(!empty($cohorto->name) ? $cohorto->name : get_string('manage:userlist', 'local_eduvidual', $org));
//$PAGE->set_cacheable(false);
$PAGE->requires->css('/local/eduvidual/style/manage_bunch.css');

// Only allow a certain user group access to this script
$allow = array("Manager");
if (!in_array(\local_eduvidual\locallib::get_orgrole($orgid), $allow) && !is_siteadmin()) {
    echo $OUTPUT->header();
    ?>
    <p class="alert alert-danger"><?php get_string('access_denied', 'local_eduvidual'); ?></p>
    <?php
    echo $OUTPUT->footer();
    exit;
}

$PAGE->navbar->add(get_string('Management', 'local_eduvidual'), new moodle_url('/local/eduvidual/pages/manage.php', array('orgid' => $orgid)));
$PAGE->navbar->add(get_string('manage:userlist', 'local_eduvidual', $org), $PAGE->url);
echo $OUTPUT->header();

$cohorts = array_values($DB->get_records_sql("SELECT id,name FROM {cohort} WHERE contextid=? ORDER BY name ASC", array($context->id)));
if (empty($cohort)) {
    foreach ($cohorts as $_cohort) {
        $cohort = $_cohort->id;
        break;
    }

}
if (!empty($cohort)) {
    $cohorto = $DB->get_record('cohort', array('id' => $cohort, 'contextid' => $context->id));
}

$cohorts[] = (object)array('id' => '___all', 'name' => get_string('manage:bunch:all', 'local_eduvidual'));
$cohorts[] = (object)array('id' => '___allparents', 'name' => get_string('manage:bunch:allparents', 'local_eduvidual'));
$cohorts[] = (object)array('id' => '___allstudents', 'name' => get_string('manage:bunch:allstudents', 'local_eduvidual'));
$cohorts[] = (object)array('id' => '___allteachers', 'name' => get_string('manage:bunch:allteachers', 'local_eduvidual'));
$cohorts[] = (object)array('id' => '___allmanagers', 'name' => get_string('manage:bunch:allmanagers', 'local_eduvidual'));

foreach ($cohorts as &$c) {
    $c->selected = ($cohort == $c->id);
}

require_once($CFG->dirroot . '/user/profile/lib.php');

$orderasc = ($orderasc == 'asc') ? 'ASC' : 'DESC';

switch ($orderby) {
    case 'firstname':
        $orderbysql = "u.firstname $orderasc, u.lastname $orderasc";
        break;
    case 'email':
        $orderbysql = "u.email $orderasc";
        break;
    case 'role':
        $orderbysql = "ou.role $orderasc";
        break;
    case 'authtype':
        $orderbysql = "u.auth $orderasc";
        break;
    case 'secret':
        $orderbysql = "u.id $orderasc";
        break;
    case 'lastname':
    default:
        $orderby = 'lastname';
        $orderbysql = "u.lastname $orderasc, u.firstname $orderasc";
}

switch ($cohort) {
    case '___all':
        $userids = array_keys($DB->get_records('local_eduvidual_orgid_userid', ['orgid' => $orgid], '', 'userid'));
        break;
    case '___allparents':
        $userids = array_keys($DB->get_records('local_eduvidual_orgid_userid', ['orgid' => $orgid, 'role' => 'Parent'], '', 'userid'));
        break;
    case '___allstudents':
        $userids = array_keys($DB->get_records('local_eduvidual_orgid_userid', ['orgid' => $orgid, 'role' => 'Student'], '', 'userid'));
        break;
    case '___allteachers':
        $userids = array_keys($DB->get_records('local_eduvidual_orgid_userid', ['orgid' => $orgid, 'role' => 'Teacher'], '', 'userid'));
        break;
    case '___allmanagers':
        $userids = array_keys($DB->get_records('local_eduvidual_orgid_userid', ['orgid' => $orgid, 'role' => 'Manager'], '', 'userid'));
        break;
    default:
        $userids = array_keys($DB->get_records('cohort_members', ['cohortid' => $cohort], '', 'userid'));
}

list($insql, $inparams) = $DB->get_in_or_equal($userids);
$sql = "SELECT *
            FROM {user} u
            WHERE id $insql
                AND deleted=0
            ORDER BY $orderbysql";
$entries = $DB->get_records_sql($sql, $inparams);
$dummydomain = \local_eduvidual\locallib::get_dummydomain();

$formats = array(
    (object)array('format' => 'cards', 'name' => get_string('manage:user_bunches:format:cards', 'local_eduvidual')),
    (object)array('format' => 'list', 'name' => get_string('manage:user_bunches:format:list', 'local_eduvidual')),
);
foreach ($formats as &$f) {
    $f->selected = ($format == $f->format);
}

$users = array();
$userids = array();
$cnt = 0;
foreach ($entries as $user) {
    $role = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $user->id));
    if (empty($role->role))
        continue;
    profile_load_data($user);
    $user->backgroundcard = get_user_preferences('local_eduvidual_backgroundcard', '', $user->id);
    if (empty($user->backgroundcard)) {
        $user->backgroundcard = \local_eduvidual\lib_enrol::choose_background($user->id);
    }
    $user->role = $role->role;
    $user->userpicture = $OUTPUT->user_picture($user, array('size' => 200));
    $user->userpicturesmall = $OUTPUT->user_picture($user, array('size' => 50));
    $user->secret_encoded = rawurlencode($user->id . '#' . $user->profile_field_secret);
    $user->displayusername = $user->username;

    $cnt++;
    if ($cnt == 18) {
        $cnt = 0;
        $user->pagebreakafter = true;
    }
    $users[] = $user;
    $userids[] = $user->id;
}

echo $OUTPUT->render_from_template('local_eduvidual/manage_userlists', array(
    'cohorts' => $cohorts,
    'exportuserids' => implode(',', $userids),
    'format_cards' => ($format == 'cards'),
    'format_list' => ($format == 'list'),
    'formats' => $formats,
    'orderby_' . $orderby => 1,
    'orderasc' => ($orderasc == 'ASC') ? 1 : 0,
    'orgid' => $orgid,
    'pageurl' => $PAGE->url->__toString(),
    'switchasc' => ($orderasc == 'ASC') ? 'desc' : 'asc',
    'users' => $users,
    'wwwroot' => $CFG->wwwroot,
));

echo $OUTPUT->footer();

// Below this line we only collect functions

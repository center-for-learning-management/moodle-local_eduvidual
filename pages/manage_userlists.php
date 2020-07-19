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
require_once($CFG->dirroot. '/course/lib.php');

$orgid = optional_param('orgid', 0, PARAM_INT);
$cohort = optional_param('cohort', '', PARAM_TEXT);
$format = optional_param('format', 'list', PARAM_TEXT);

$org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));
$context = \context_coursecat::instance($org->categoryid);
$PAGE->set_context($context);

$cohorts = $DB->get_records_sql("SELECT id,name FROM {cohort} WHERE contextid=? ORDER BY name ASC", array($context->id));
if (empty($cohort)) {
	foreach ($cohorts AS $_cohort) {
		$cohort = $_cohort->id;
		break;
	}

}
if (!empty($cohort)) {
	$cohorto = $DB->get_record('cohort', array('id' => $cohort, 'contextid' => $context->id));
}

$PAGE->set_pagelayout('standard');
$PAGE->set_url(new \moodle_url('/local/eduvidual/pages/manage_userlists.php', array('orgid' => $orgid, 'cohort' => $cohort, 'format' => $format)));
$PAGE->set_title(!empty($cohorto->name) ? $cohorto->name : get_string('Accesscards', 'local_eduvidual'));
$PAGE->set_heading(!empty($cohorto->name) ? $cohorto->name : get_string('Accesscards', 'local_eduvidual'));
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
$PAGE->navbar->add(get_string('Accesscards', 'local_eduvidual'), $PAGE->url);
echo $OUTPUT->header();

?>
<form action="" method="get">
    <input type="hidden" name="orgid" value="<?php echo $org->orgid; ?>" />
    <div class="hide-on-print ui-eduvidual grid-eq-2">
		<input type="hidden" name="orgid" value="<?php echo $org->orgid; ?>" />
        <div>
            <select name="cohort" onchange="this.form.submit();" style="width: 100%;">
            <?php

            $urltobunch = $CFG->wwwroot . '/local/eduvidual/pages/manage_userlists.php?orgid=' . $org->orgid . '&cohort=';
            $cohorts['___all'] = (object) array('name' => get_string('manage:bunch:all', 'local_eduvidual'));
            $cohorts['___allparents'] = (object) array('name' => get_string('manage:bunch:allparents', 'local_eduvidual'));
            $cohorts['___allstudents'] = (object) array('name' => get_string('manage:bunch:allstudents', 'local_eduvidual'));
            $cohorts['___allteachers'] = (object) array('name' => get_string('manage:bunch:allteachers', 'local_eduvidual'));
            $cohorts['___allmanagers'] = (object) array('name' => get_string('manage:bunch:allmanagers', 'local_eduvidual'));
			foreach ($cohorts as $k => $c) {
				?>
                <option value="<?php echo $k; ?>"<?php if($cohort == $k) { echo " selected"; } ?>>
                    <?php echo $c->name; ?>
                </option>
                <?php
			}
            ?>
            </select>
        </div>
        <div>
            <select name="format" onchange="this.form.submit();" style="width: 100%;">
                <option value="cards"<?php if($format == 'cards') echo " selected"; ?>><?php echo get_string('manage:user_bunches:format:cards', 'local_eduvidual'); ?></option>
                <option value="list"<?php if($format == 'list') echo " selected"; ?>><?php echo get_string('manage:user_bunches:format:list', 'local_eduvidual'); ?></option>
            </select>
        </div>
    </div>
</form>
<?php
require_once($CFG->dirroot . '/user/profile/lib.php');
switch($cohort) {
    case '___all':
        $entries = $DB->get_records_sql('SELECT u.* FROM {local_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=u.id AND ou.orgid=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid));
    break;
    case '___allparents':
        $entries = $DB->get_records_sql('SELECT u.* FROM {local_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=u.id AND ou.orgid=? AND ou.role=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid, 'Parent'));
    break;
    case '___allstudents':
        $entries = $DB->get_records_sql('SELECT u.* FROM {local_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=u.id AND ou.orgid=? AND ou.role=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid, 'Student'));
    break;
    case '___allteachers':
        $entries = $DB->get_records_sql('SELECT u.* FROM {local_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=u.id AND ou.orgid=? AND ou.role=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid, 'Teacher'));
    break;
    case '___allmanagers':
        $entries = $DB->get_records_sql('SELECT u.* FROM {local_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=u.id AND ou.orgid=? AND ou.role=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid, 'Manager'));
    break;
    default:
        $entries = $DB->get_records_sql('SELECT u.* FROM {cohort_members} AS cm, {user} AS u WHERE u.deleted=0 AND cm.userid=u.id AND cm.cohortid=? ORDER BY u.lastname ASC, u.firstname ASC', array($cohort));
}

$users = array();
$userids = array();
$cnt = 0;
foreach($entries AS $user) {
    profile_load_data($user);
    $role = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $user->id));
    $user->backgroundcard = get_user_preferences('local_eduvidual_backgroundcard', $user->id);
    $user->role = $role->role;
    $user->userpicture = $OUTPUT->user_picture($user, array('size' => 200));
    $user->secret_encoded = rawurlencode($user->id . '#' . $user->profile_field_secret);
    $cnt++;
    if ($cnt == 18) {
        $cnt = 0;
        $user->pagebreakafter = true;
    }
    $users[] = $user;
    $userids[] = $user->id;
}

echo $OUTPUT->render_from_template('local_eduvidual/manage_bunch', array(
    //'dataformatselector' => $OUTPUT->download_dataformat_selector(get_string('userbulkdownload', 'admin'), $CFG->wwwroot . '/local/eduvidual/pages/sub/manage_usersdownload.php', 'dataformat', array('orgid' => $orgid, 'userids' => implode(',', $userids))),
    'exportuserids' => implode(',', $userids),
    'format_cards' => ($format == 'cards'),
    'format_list' => ($format == 'list'),
    'orgid' => $orgid,
    'users' => $users,
    'wwwroot' => $CFG->wwwroot,
));

echo $OUTPUT->footer();

// Below this line we only collect functions

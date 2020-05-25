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
require_once($CFG->dirroot. '/course/lib.php');

$orgid = optional_param('orgid', 0, PARAM_INT);
$bunch = optional_param('bunch', '', PARAM_TEXT);
$format = optional_param('format', 'cards', PARAM_TEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_url(new moodle_url('/blocks/eduvidual/pages/manage_bunch.php', array('orgid' => $orgid, 'bunch' => $bunch, 'format' => $format)));
$PAGE->set_title(!empty($bunch) ? $bunch : get_string('Accesscards', 'block_eduvidual'));
$PAGE->set_heading(!empty($bunch) ? $bunch : get_string('Accesscards', 'block_eduvidual'));
//$PAGE->set_cacheable(false);
$PAGE->requires->css('/blocks/eduvidual/style/manage_bunch.css');

// Only allow a certain user group access to this script
$allow = array("Administrator", "Manager");
if (!in_array(block_eduvidual::get('role'), $allow)) {
	block_eduvidual::print_app_header();
	?>
		<p class="alert alert-danger"><?php get_string('access_denied', 'block_eduvidual'); ?></p>
	<?php
	block_eduvidual::print_app_footer();
	exit;
}

// Used to determine if we can manage this org
$current_orgid = optional_param('orgid', 0, PARAM_INT);
$orgas = block_eduvidual::get_organisations('Manager');
$org = block_eduvidual::get_organisations_check($orgas, $current_orgid);
if ($org) {
    block_eduvidual::set_org($org->orgid);
}

block_eduvidual::set_context_auto(0, $org->categoryid);
$PAGE->navbar->add(get_string('Management', 'block_eduvidual'), new moodle_url('/blocks/eduvidual/pages/manage.php', array('orgid' => $orgid)));
$PAGE->navbar->add(get_string('Accesscards', 'block_eduvidual'), $PAGE->url);
block_eduvidual::print_app_header();

$grid = 2;
if (count($orgas) > 1) $grid = 3;
?>
<form action="<?php echo $PAGE->url; ?>" method="get">
    <input type="hidden" name="orgid" value="<?php echo $org->orgid; ?>" />
    <div class="hide-on-print ui-eduvidual grid-eq-<?php echo $grid; ?>">
        <?php
        if (count($orgas) > 1) {
            ?><div><select name="orgid" onchange="this.form.submit();" style="width: 100%;"><?php
            foreach($orgas AS $orga) {
                ?><option value="<?php echo $orga->orgid; ?>"<?php if($orga->orgid == $org->orgid) echo " selected"; ?>>
                    <?php echo $orga->orgid . ' | ' . $orga->name; ?>
                </option><?php
            }
            ?></select></div><?php
        }
        ?>
        <div>
            <select name="bunch" onchange="this.form.submit();" style="width: 100%;">
            <?php

            $urltobunch = $CFG->wwwroot . '/blocks/eduvidual/pages/manage_bunch.php?orgid=' . $org->orgid . '&bunch=';
            $bunches = $DB->get_records_sql('SELECT DISTINCT(eu.bunch) FROM {block_eduvidual_userbunches} eu,{user} u WHERE u.id=eu.userid AND u.deleted=0 AND orgid=? ORDER BY eu.bunch ASC', array($org->orgid));
            $bunches['___all'] = (object) array('bunch' => get_string('manage:bunch:all', 'block_eduvidual'));
            $bunches['___allwithout'] = (object) array('bunch' => get_string('manage:bunch:allwithoutbunch', 'block_eduvidual'));
            $bunches['___allparents'] = (object) array('bunch' => get_string('manage:bunch:allparents', 'block_eduvidual'));
            $bunches['___allstudents'] = (object) array('bunch' => get_string('manage:bunch:allstudents', 'block_eduvidual'));
            $bunches['___allteachers'] = (object) array('bunch' => get_string('manage:bunch:allteachers', 'block_eduvidual'));
            $bunches['___allmanagers'] = (object) array('bunch' => get_string('manage:bunch:allmanagers', 'block_eduvidual'));
            if (count($bunches) > 0) {
                $ks = array_keys($bunches);
                asort($ks);
                for ($a = 0; $a < count($ks); $a++) {
                    $_bunch = $bunches[$ks[$a]];
                    // If we did not get a bunch we take the first one
                    if (empty($bunch)) {
                        $bunch = $ks[$a];
                    }
                    ?>
                    <option value="<?php echo $ks[$a]; ?>"<?php if($bunch == $ks[$a]) { echo " selected"; } ?>>
                        <?php echo $_bunch->bunch; ?>
                    </option>
                    <?php
                }
            } else {
                ?>
                <option value=""><?php echo get_string('none'); ?></option>
                <?php
            }
            ?>
            </select>
        </div>
        <div>
            <select name="format" onchange="this.form.submit();" style="width: 100%;">
                <option value="cards"<?php if($format == 'cards') echo " selected"; ?>><?php echo get_string('manage:user_bunches:format:cards', 'block_eduvidual'); ?></option>
                <option value="list"<?php if($format == 'list') echo " selected"; ?>><?php echo get_string('manage:user_bunches:format:list', 'block_eduvidual'); ?></option>
            </select>
        </div>
    </div>
</form>
<?php
require_once($CFG->dirroot . '/user/profile/lib.php');
switch($bunch) {
    case '___all':
        $entries = $DB->get_records_sql('SELECT u.* FROM {block_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=u.id AND ou.orgid=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid));
    break;
    case '___allwithout':
        $entries = $DB->get_records_sql('SELECT u.* FROM {block_eduvidual_userbunches} AS ub, {block_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=ub.userid AND ou.userid=u.id AND ou.orgid=? AND ub.bunch IS NULL ORDER BY u.lastname ASC, u.firstname ASC', array($orgid));
    break;
    case '___allparents':
        $entries = $DB->get_records_sql('SELECT u.* FROM {block_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=u.id AND ou.orgid=? AND ou.role=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid, 'Parent'));
    break;
    case '___allstudents':
        $entries = $DB->get_records_sql('SELECT u.* FROM {block_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=u.id AND ou.orgid=? AND ou.role=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid, 'Student'));
    break;
    case '___allteachers':
        $entries = $DB->get_records_sql('SELECT u.* FROM {block_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=u.id AND ou.orgid=? AND ou.role=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid, 'Teacher'));
    break;
    case '___allmanagers':
        $entries = $DB->get_records_sql('SELECT u.* FROM {block_eduvidual_orgid_userid} AS ou, {user} AS u WHERE u.deleted=0 AND ou.userid=u.id AND ou.orgid=? AND ou.role=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid, 'Manager'));
    break;
    default:
        $entries = $DB->get_records_sql('SELECT u.* FROM {block_eduvidual_userbunches} AS ub, {user} AS u WHERE u.deleted=0 AND ub.userid=u.id AND ub.orgid=? AND ub.bunch=? ORDER BY u.lastname ASC, u.firstname ASC', array($orgid, $bunch));
}

$users = array();
$userids = array();
$cnt = 0;
foreach($entries AS $user) {
    profile_load_data($user);
    $role = $DB->get_record('block_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $user->id));
    $user->backgroundcard = get_user_preferences('block_eduvidual_backgroundcard', $user->id);
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

echo $OUTPUT->render_from_template('block_eduvidual/manage_bunch', array(
    //'dataformatselector' => $OUTPUT->download_dataformat_selector(get_string('userbulkdownload', 'admin'), $CFG->wwwroot . '/blocks/eduvidual/pages/sub/manage_usersdownload.php', 'dataformat', array('orgid' => $orgid, 'userids' => implode(',', $userids))),
    'exportuserids' => implode(',', $userids),
    'format_cards' => ($format == 'cards'),
    'format_list' => ($format == 'list'),
    'orgid' => $orgid,
    'users' => $users,
    'wwwroot' => $CFG->wwwroot,
));

block_eduvidual::print_app_footer();

// Below this line we only collect functions

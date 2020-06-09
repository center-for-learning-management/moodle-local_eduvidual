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


$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/local/eduvidual/pages/accesscard.php', array());
$PAGE->set_title(get_string('Accesscard', 'local_eduvidual'));
$PAGE->set_heading(get_string('Accesscard', 'local_eduvidual'));
//$PAGE->set_cacheable(false);

$PAGE->requires->css('/local/eduvidual/style/manage_bunch.css');
echo $OUTPUT->header();
//print_r($USER);

if ($USER->id > 1 && !isguestuser($USER)) {
    $backgroundcard = get_user_preferences('local_eduvidual_backgroundcard', $USER->id);

    ?>
    <div class="card" style="margin-bottom: 20px;">
        <p><?php echo get_string('accesscard:description', 'local_eduvidual'); ?></p>
        <div class="grid-eq-2">
            <div style="text-align: center;">
                <h4><?php echo get_string('accesscard:card_access', 'local_eduvidual'); ?></h4>
                <div class="item" style="background-image: url(<?php echo $backgroundcard; ?>)">
                    <div class="name">
                        <div class="firstname"><?php echo $USER->firstname; ?></div>
                        <div class="lastname"><?php echo $USER->lastname; ?></div>
                    </div>
                    <div class="username"><?php echo $USER->username; ?></div>
                    <div class="contact"><?php echo $USER->email; ?></div>
                    <div class="header"><?php echo get_string('Accesscard', 'local_eduvidual'); ?></div>
                    <div class="avatar"><?php echo $OUTPUT->user_picture($USER, array('size' => 200)); ?></div>
                    <!-- <div class="qr"><img src="<?php echo $CFG->wwwroot . '/local/eduvidual/pix/qr.php?txt=' . rawurlencode($USER->id . '#' . \local_eduvidual\locallib::get('field_secret')); ?>" alt="QR" /></div> -->
                    <div class="secret" style="display: flex; align-items: flex-end; justify-content: flex-end;">
                        <span class="uid"><?php echo $USER->id; ?></span>
                        <span class="hash">#</span>
                        <span class="tan"><?php echo \local_eduvidual\locallib::get('field_secret'); ?></span>
                    </div>
                    <div class="roles"><?php echo \local_eduvidual\locallib::get_orgrole($org->orgid); ?></div>
                </div>
            </div>
            <div>
                <h4 style="text-align: center;"><?php echo get_string('accesscard:orgcode_access', 'local_eduvidual'); ?></h4>
                <label for="local_eduvidual_user_accesscode_orgid"><?php echo get_string('accesscard:orgid', 'local_eduvidual'); ?></label>
                <input type="text" id="local_eduvidual_user_accesscode_orgid" autocomplete="off"  readonly onfocus="this.removeAttribute('readonly');"/>
                <label for="local_eduvidual_user_accesscode_code"><?php echo get_string('accesscard:orgcode', 'local_eduvidual'); ?></label>
                <input type="password" id="local_eduvidual_user_accesscode_code" autocomplete="off"  readonly onfocus="this.removeAttribute('readonly');" />
                <a class="ui-btn btn btn-primary_" href="#" id="local_eduvidual_user_accesscode_btn"
                    onclick="require(['local_eduvidual/user'], function(USER) { USER.accesscode(); }); return false;">
                    <?php echo get_string('accesscard:enrol', 'local_eduvidual'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
} else {
    echo $OUTPUT->render_from_template(
        'local_eduvidual/alert',
        (object) array('type' => 'warning', 'content' => get_string('accesscard:not_for_guest', 'local_eduvidual'), 'url' => $CFG->wwwroot . '/my')
    );
}


echo $OUTPUT->footer();

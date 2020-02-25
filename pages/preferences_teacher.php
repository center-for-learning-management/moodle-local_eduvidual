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

if(!defined('MOODLE_INTERNAL')) {
    // This script was called directly
    require_once('../../../config.php');
    require_login();
    require_once($CFG->libdir . '/adminlib.php');
    require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');
}

if (!defined('MOODLE_INTERNAL') || $act == 'moolevelinit') {
    echo $OUTPUT->render_from_template('block_eduvidual/alert', array(
        "content" => get_string('preferences:request:moolevel', 'block_eduvidual'),
        "type" => 'info',
        "url" => $CFG->wwwroot . '/my',
    ));
}

if (in_array(block_eduvidual::get('role'), array('Administrator', 'Manager', 'Teacher'))) {
    if ($embed || $act == 'moolevel' || $act == 'moolevelinit') {
        $moolevels = explode(',', get_config('block_eduvidual', 'moolevels'));
        if (count($moolevels) > 0 && $moolevels[0] != '') {
        ?>
            <div class="card">
                <h3><?php echo get_string('preferences:explevel', 'block_eduvidual'); ?></h3>
                <p><?php echo get_string('preferences:explevel:description', 'block_eduvidual'); ?></p>

                <div class="grid-eq-3">
                <?php
                if (!isset($userextra->moolevels)) {
                    $extra->moolevels = array();
                }
                $context = context_system::instance();
                $roles = get_user_roles($context, $USER->id, true);

                $hasroles = array();
                foreach($roles AS $hasrole) {
                    $hasroles[] = $hasrole->roleid;
                }

                foreach($moolevels AS $moolevel) {
                    $role = $DB->get_record('role', array('id' => $moolevel));
                    ?>
                    <div>
                        <label>
                            <input type="radio" name="preferences_moolevels[]"
                                   value="<?php echo $role->id; ?>" onclick="var inp = this; require(['block_eduvidual/teacher'], function(TEACHER) { TEACHER.moolevels(inp); });"
                                   <?php if(in_array($role->id, $hasroles)){ echo ' checked="checked"'; } ?> />
                            <strong><?php echo (($role->name != "")?$role->name:$role->shortname); ?></strong><br />
                        </label>
                        <p style="margin-left: 25px;">
                            <?php
                            if ($role->description != '') {
                                echo get_string($role->description, 'block_eduvidual');
                            }
                            ?>
                        </p>
                    </div>
                    <?php
                }
            ?></div></div><?php
        } // has moolevels
    }
    if ($embed || $act == 'qcats' || $act == 'moolevelinit') {
        $questioncategories = explode(",", get_config('block_eduvidual', 'questioncategories'));
        if (count($questioncategories) > 0) {
            ?>
            <div class="card">
                <h3><?php echo get_string('preferences:questioncategories', 'block_eduvidual'); ?></h3>
                <p><?php echo get_string('preferences:questioncategories:description', 'block_eduvidual'); ?></p>
                <?php

                for ($a = 0; $a < count($questioncategories); $a++) {
                    $cat = $DB->get_record('question_categories', array('id' => $questioncategories[$a]));
                    if (isset($cat->id) && $cat->id > 0) {
                        $active = $DB->get_record('block_eduvidual_userqcats', array('userid' => $USER->id, 'categoryid' => $questioncategories[$a]));
                        ?>
                        <label>
                            <input type="checkbox" name="questioncategories[]" value="<?php echo $cat->id; ?>"<?php if ($active && $active->categoryid == $cat->id) { echo " checked"; } ?>
                                onclick="var inp = this; require(['block_eduvidual/teacher'], function(TEACHER) { TEACHER.questioncategories(inp); });" />
                            <?php echo $cat->name; ?>
                        </label>
                        <?php
                    }
                }
                ?>
            </div><?php
        } // endif count questioncategories > 0
    }
} // if is teacher

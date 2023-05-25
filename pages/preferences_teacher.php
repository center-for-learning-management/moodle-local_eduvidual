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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if(!defined('MOODLE_INTERNAL')) {
    // This script was called directly
    require_once('../../../config.php');
    require_login();
    require_once($CFG->libdir . '/adminlib.php');

}

$context = \context_system::instance();
if (has_capability('moodle/question:viewall', $context)) {
    $questioncategories = explode(",", get_config('local_eduvidual', 'questioncategories'));
    if (count($questioncategories) > 0) {
        ?>
        <div class="card">
            <h3><?php echo get_string('preferences:questioncategories', 'local_eduvidual'); ?></h3>
            <p><?php echo get_string('preferences:questioncategories:description', 'local_eduvidual'); ?></p>
            <?php

            for ($a = 0; $a < count($questioncategories); $a++) {
                $cat = $DB->get_record('question_categories', array('id' => $questioncategories[$a]));
                if (isset($cat->id) && $cat->id > 0) {
                    $active = $DB->get_record('local_eduvidual_userqcats', array('userid' => $USER->id, 'categoryid' => $questioncategories[$a]));
                    ?>
                    <label>
                        <input type="checkbox" name="questioncategories[]" value="<?php echo $cat->id; ?>"<?php if ($active && $active->categoryid == $cat->id) { echo " checked"; } ?>
                            onclick="var inp = this; require(['local_eduvidual/teacher'], function(TEACHER) { TEACHER.questioncategories(inp); });" />
                        <?php echo $cat->name; ?>
                    </label>
                    <?php
                }
            }
            ?>
        </div><?php
    } // endif count questioncategories > 0
}

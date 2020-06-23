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

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');


$embed = optional_param('embed', 0, PARAM_INT);
$act = optional_param('act', 'backgrounds', PARAM_TEXT);

$context = \context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout(($embed) ? 'popup' : 'standard');
$PAGE->set_pagetype('user-preferences');
$PAGE->set_url('/local/eduvidual/pages/preferences.php', array('act' => $act));
$PAGE->set_title(get_string('Preferences', 'local_eduvidual'));
$PAGE->set_heading(get_string('Preferences', 'local_eduvidual'));
//$PAGE->set_cacheable(false);
$PAGE->requires->css('/local/eduvidual/style/preferences.css');

echo $OUTPUT->header();

if ($embed || $act == 'backgrounds') {
    $background = get_user_preferences('local_eduvidual_background');

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_eduvidual', 'backgrounds', 0);
    $bgdivs = array();

    $bgdivs[] = "<span" . (empty($background)?" class=\"active\"":""). "><a style=\"background-image: none;background-color: gray;\" onclick=\"var a = this; require(['local_eduvidual/preferences'], function(PREFERENCES) { PREFERENCES.setBackground(a); });\" href=\"#\">&nbsp;</a></span>";
    foreach ($files as $file) {
        if (str_replace('.', '', $file->get_filename()) != ""){
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
            $bgdivs[] = "<span" . ((!empty($background) && $url == $background)?" class=\"active\"":""). "><a style=\"background-image: url(" . $url . ");\" onclick=\"var a = this; require(['local_eduvidual/preferences'], function(PREFERENCES) { PREFERENCES.setBackground(a); });\" href=\"#\">&nbsp;</a></span>";
        }
    }

    if (count($bgdivs) > 0) {
        ?>
        <div class="card">
            <h3><?php echo get_string('preferences:selectbg:title', 'local_eduvidual'); ?></h3>
            <div id="local_eduvidual_preferences_background">
            <?php
                echo implode("\n", $bgdivs);
            ?>
            </div>
        </div>
        <?php
    } // count bgdivs > 0
}

if (has_capability('moodle/question:viewall', $context)) {
    require_once($CFG->dirroot . '/local/eduvidual/pages/preferences_teacher.php');
} // if is teacher
echo $OUTPUT->footer();

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

// ATTENTION, THIS FILE IS ONLY TO BE USED AS ENTRY POINT FROM WITHIN THE CORDOVA APP

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/blocks/eduvidual/pages/launch.php',array());
$PAGE->set_title(get_string('Login', 'block_eduvidual'));
$PAGE->set_heading(get_string('Login', 'block_eduvidual'));
//$PAGE->set_cacheable(false);
$isapp = optional_param('isapp', 0, PARAM_INT);
block_eduvidual::set_context_auto();

// Remove tokens older than 6 months or not used for 3 weeks
$week = 60*60*24*7;
$DB->delete_records_select('block_eduvidual_usertoken', 'created < ? OR used < ?', array(time() - 26*$week, time() - 3*$week));

if ($USER->id > 0) {
    // Create a token for user and store to localstorage
    $entry = new stdClass();
    $entry->token = md5(date('Y-m-d H:i:s') . $USER->id . rand(pow(9,3), pow(9, 5)));
    $entry->userid = $USER->id;
    $entry->created = time();
    $entry->used = time();
    $DB->insert_record('block_eduvidual_usertoken', $entry);
    $launchurl = "eduvidual://block_eduvidual_token=" . $entry->token . "&userid=" . $USER->id;
}
header('location: ' . $launchurl);

block_eduvidual::print_app_header();

if (!empty($launchurl)) {
?>
    <p><a href="<?php echo $launchurl; ?>" onclick="">Launch in App</a></p>


<?php
} else {
    ?>
    Login failed.
    <?php
}
block_eduvidual::print_app_footer();

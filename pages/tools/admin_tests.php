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
 * @copyright  2020 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Test certain errors.
 */

require_once('../../../../config.php');

// The parent node we append to.
$test = optional_param('test', '', PARAM_ALPHANUM);


$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/tools/admin_tests.php', array('test' => $test));
$PAGE->set_title('Tests');
$PAGE->set_heading('Tests');

require_login();

if (!is_siteadmin()) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => get_string('access_denied', 'local_eduvidual'),
        'type' => 'danger'
    ));
    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->header();

switch ($test) {
    case 'memory':
        echo "<h3>Testing Memory</h3>\n";
        flush();
        $dummy = str_repeat("0", 1024*1024*1024*1024*1024);
    break;
    case 'timeout':
        echo "<h3>Testing Timeout</h3>\n";
        flush();
        // Set the time limit to 1 second.
        set_time_limit (1);
        // If it does not work, use sleep to wait extremely long.
        sleep(60*60*60*60*60);
    break;
    default:
}

?>
<ul>
    <li>
        <a href="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pages/tools/admin_tests.php?test=memory">
            Memory exhausted
        </a>
    </li>
    <li>
        <a href="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pages/tools/admin_tests.php?test=timeout">
            Timeout
        </a>
    </li>
</ul>

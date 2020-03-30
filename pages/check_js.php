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

// Just redirect to the google login provider

require_once('../../../config.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');
$forwardto = urldecode(optional_param('forwardto', '/my', PARAM_TEXT));

$PAGE->set_heading(get_string('check_js:title', 'block_eduvidual'));
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('check_js:title', 'block_eduvidual'));
$PAGE->set_url('/blocks/eduvidual/pages/check_js.php', array('forwardto' => $forwardto));

block_eduvidual::print_app_header();
echo "<p>" . get_string('check_js:description', 'block_eduvidual');
?><script type="text/javascript">
top.location.href = '<?php echo $CFG->wwwroot . $forwardto; ?>';
</script>
<?php
block_eduvidual::print_app_footer();

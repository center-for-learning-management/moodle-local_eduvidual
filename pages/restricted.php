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


$camefrom = optional_param('camefrom', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/local/eduvidual/pages/restricted.php', array('camefrom' => $camefrom));
$PAGE->set_title(get_string('restricted:title', 'local_eduvidual'));
$PAGE->set_heading(get_string('restricted:title', 'local_eduvidual'));
//$PAGE->set_cacheable(false);

echo $OUTPUT->header();
?>

<h3><?php echo get_string('restricted:title', 'local_eduvidual') ?></h3>
<?php echo get_string('restricted:description', 'local_eduvidual') ?>

<?php
echo $OUTPUT->footer();

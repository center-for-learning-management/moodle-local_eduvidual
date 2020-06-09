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


require_once('../../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');


if (!is_siteadmin()) {
    ?>
    <p class="alert alert-warning"><?php echo get_string('js:missing_permission', 'local_eduvidual'); ?></p>
    <?php
}

require_once($CFG->dirroot . '/local/eduvidual/classes/lib_import.php');
$helper = new local_eduvidual_lib_import();
$helper->load_post();
$helper->download('modules_' . date("Ymd-His"));

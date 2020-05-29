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

defined('MOODLE_INTERNAL') || die;
if (!is_siteadmin()) die;

$modules = $DB->get_records_sql('SELECT * FROM {block_eduvidual_modules} WHERE categoryid=? ORDER BY name ASC', array($categoryid));
?>
<h4><?php echo get_string('admin:modules:title', 'block_eduvidual'); ?></h4>
<ul>
    <li>
        <a href="<?php echo $PAGE->url . '?act=' . $act . '&categoryid=' . $categoryid . '&moduleid=-1'; ?>"><?php echo get_string('create'); ?></a>
    </li>
<?php
foreach($modules AS $module) {
    ?>
    <li>
        <a href="<?php echo $PAGE->url . '?act=' . $act . '&categoryid=' . $categoryid . '&moduleid=' . $module->id; ?>" class="<?php echo (($module->active)?'active':'inactive'); ?>"><?php echo $module->name; ?></a>
    </li>
    <?php
}
?>
</ul>
<?php

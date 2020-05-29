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

// This file is included from admin_modulecats.php and we have a $categoryid

?>
<h4><?php echo get_string('admin:modulecat:edit', 'block_eduvidual'); ?></h4>
<?php

$draftitemid = file_get_submitted_draft_itemid('modulecat');
file_prepare_draft_area($draftitemid, $context->id, 'block_eduvidual', 'modulecat', $categoryid,
    array('subdirs' => $categoryform->subdirs, 'maxbytes' => $categoryform->maxbytes, 'maxfiles' => $categoryform->maxfiles));

$entry = $DB->get_record('block_eduvidual_modulescat', array('id' => $categoryid));
$entry->act = $act;
if (isset($parentid) && $parentid > 0)  {
    $entry->parentid = $parentid;
}
$entry->categoryid = $categoryid;
$entry->modulecat = $draftitemid;
$entry->id = $categoryid;
$categoryform->set_data($entry);
$categoryform->display();

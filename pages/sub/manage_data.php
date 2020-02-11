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
$isadmin = in_array(block_eduvidual::get('role') == 'Administrator';
$ismanager = in_array(block_eduvidual::get('role') == 'Manager';
if (!$isadmin && !$ismanager) die;

?>
<h5><?php echo get_string('manage:data', 'block_eduvidual'); ?></h5>
<?php

if ($org->orgid > 0) {
    require_once($CFG->dirroot . "/blocks/eduvidual/classes/manage_data_form.php");

    $form = new block_eduvidual_manage_data_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
    $context = context_system::instance();
    if ($data = $form->get_data()) {
        // Store all fields from $data to $org
        //$org->mnetid = $data->mnetid;
        $DB->update_record('block_eduvidual_org', $org);
        file_save_draft_area_files(
            $data->mnetlogo, $context->id, 'block_eduvidual', 'mnetlogo', $org->orgid,
            array('subdirs' => $form->subdirs, 'maxbytes' => $form->maxbytes, 'maxfiles' => $form->maxfiles)
        );
        echo "<p class=\"alert alert-success\">" . get_string('store:success', 'block_eduvidual') . "</p>";
    }

    $draftitemid = file_get_submitted_draft_itemid('mnetlogo');
    file_prepare_draft_area($draftitemid, $context->id, 'block_eduvidual', 'mnetlogo', $org->orgid,
        array('subdirs' => $form->subdirs, 'maxbytes' => $form->maxbytes, 'maxfiles' => $form->maxfiles));
    $org->mnetlogo = $draftitemid;
    $form->set_data($org);
    $form->display();
} else {
    ?>
    <p class="alert alert-error"><?php echo get_string('manage:mnet:selectorg', 'block_eduvidual'); ?></p>
    <?php
}

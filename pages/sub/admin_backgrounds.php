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

defined('MOODLE_INTERNAL') || die;
if (!is_siteadmin()) die;

?>
<h4><?php echo get_string('admin:backgrounds:title', 'local_eduvidual'); ?></h4>
<p><?php echo get_string('admin:backgrounds:description', 'local_eduvidual'); ?></p>
<?php
require_once($CFG->dirroot . "/local/eduvidual/classes/admin_backgrounds_form.php");
$form = new local_eduvidual_admin_backgrounds_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
$context = context_system::instance();
if ($data = $form->get_data()) {
    file_save_draft_area_files(
        $data->backgrounds, $context->id, 'local_eduvidual', 'backgrounds', 0,
        array('subdirs' => $form->subdirs, 'maxbytes' => $form->maxbytes, 'maxfiles' => $form->maxfiles)
    );
    echo "<p class=\"alert alert-success\">" . get_string('store:success', 'local_eduvidual') . "</p>";
}
$draftitemid = file_get_submitted_draft_itemid('backgrounds');
file_prepare_draft_area($draftitemid, $context->id, 'local_eduvidual', 'backgrounds', 0,
                        array('subdirs' => $form->subdirs, 'maxbytes' => $form->maxbytes, 'maxfiles' => $form->maxfiles));
$entry = new \stdClass;
$entry->backgrounds = $draftitemid;
$form->set_data($entry);
$form->display();
?>
<h4><?php echo get_string('admin:backgrounds_cards:title', 'local_eduvidual'); ?></h4>
<p><?php echo get_string('admin:backgrounds_cards:description', 'local_eduvidual'); ?></p>
<?php
require_once($CFG->dirroot . "/local/eduvidual/classes/admin_backgrounds_cards_form.php");
$form_cards = new local_eduvidual_admin_backgrounds_cards_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
$context = context_system::instance();
if ($data = $form_cards->get_data()) {
    file_save_draft_area_files(
        $data->backgrounds_cards, $context->id, 'local_eduvidual', 'backgrounds_cards', 0,
        array('subdirs' => $form_cards->subdirs, 'maxbytes' => $form_cards->maxbytes, 'maxfiles' => $form_cards->maxfiles)
    );
    echo "<p class=\"alert alert-success\">" . get_string('store:success', 'local_eduvidual') . "</p>";
}
$draftitemid = file_get_submitted_draft_itemid('backgrounds_cards');
file_prepare_draft_area($draftitemid, $context->id, 'local_eduvidual', 'backgrounds_cards', 0,
                        array('subdirs' => $form_cards->subdirs, 'maxbytes' => $form_cards->maxbytes, 'maxfiles' => $form_cards->maxfiles));
$entry = new \stdClass;
$entry->backgrounds_cards = $draftitemid;
$form_cards->set_data($entry);
$form_cards->display();
?>
<h4><?php echo get_string('admin:globalfiles:title', 'local_eduvidual'); ?></h4>
<?php
/* <p><?php echo get_string('admin:globalfiles:description', 'local_eduvidual'); ?></p> */
require_once($CFG->dirroot . "/local/eduvidual/classes/admin_globalfiles_form.php");
$form_globalfiles = new local_eduvidual_admin_globalfiles_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
$context = context_system::instance();
if ($data = $form_globalfiles->get_data()) {
    file_save_draft_area_files(
        $data->globalfiles, $context->id, 'local_eduvidual', 'globalfiles', 0,
        array('subdirs' => $form_globalfiles->subdirs, 'maxbytes' => $form_globalfiles->maxbytes, 'maxfiles' => $form_globalfiles->maxfiles)
    );
    echo "<p class=\"alert alert-success\">" . get_string('store:success', 'local_eduvidual') . "</p>";
}
$draftitemid = file_get_submitted_draft_itemid('globalfiles');
file_prepare_draft_area($draftitemid, $context->id, 'local_eduvidual', 'globalfiles', 0,
                        array('subdirs' => $form_globalfiles->subdirs, 'maxbytes' => $form_globalfiles->maxbytes, 'maxfiles' => $form_globalfiles->maxfiles));
$entry = new \stdClass;
$entry->globalfiles = $draftitemid;
$form_globalfiles->set_data($entry);
$form_globalfiles->display();

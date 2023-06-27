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

require_once($CFG->dirroot . "/local/eduvidual/classes/manage_files_form.php");
// This variable is used within the form-object for the label text of orgfiles
$_url = '/pluginfile.php/1/local_eduvidual/orgfiles/' . $org->orgid . '/<i>filename</i>';

$form = new \local_eduvidual_manage_files_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
$context = \context_system::instance();
if ($data = $form->get_data()) {
    file_save_draft_area_files(
        $data->orgfiles, $context->id, 'local_eduvidual', 'orgfiles', $org->orgid,
        array('subdirs' => $form->subdirs, 'maxbytes' => $form->maxbytes, 'maxfiles' => $form->maxfiles)
    );
    file_save_draft_area_files(
        $data->orglogo, $context->id, 'local_eduvidual', 'orglogo', $org->orgid,
        array('subdirs' => $form->subdirs, 'maxbytes' => $form->maxbytes, 'maxfiles' => 1)
    );
    file_save_draft_area_files(
        $data->orgbanner, $context->id, 'local_eduvidual', 'orgbanner', $org->orgid,
        array('subdirs' => $form->subdirs, 'maxbytes' => $form->maxbytes, 'maxfiles' => 1)
    );
    $files = \local_eduvidual\locallib::list_area_files('orgbanner', $org->orgid, $context);
    if (count($files) > 0) {
        $org->banner = str_replace($CFG->wwwroot, '', $files[0]->url);
    } else {
        $org->banner = '';
    }
    $DB->update_record('local_eduvidual_org', $org);
    echo "<p class=\"alert alert-success\">" . get_string('store:success', 'local_eduvidual') . "</p>";
}


$entry = new \stdClass;
$entry->orgid = $org->orgid;

$draftitemid = file_get_submitted_draft_itemid('orgfiles');
file_prepare_draft_area($draftitemid, $context->id, 'local_eduvidual', 'orgfiles', $org->orgid,
    array('subdirs' => $form->subdirs, 'maxbytes' => $form->maxbytes, 'maxfiles' => $form->maxfiles));
$entry->orgfiles = $draftitemid;

$draftitemid = file_get_submitted_draft_itemid('orglogo');
file_prepare_draft_area($draftitemid, $context->id, 'local_eduvidual', 'orglogo', $org->orgid,
    array('subdirs' => $form->subdirs, 'maxbytes' => $form->maxbytes, 'maxfiles' => 1));
$entry->orglogo = $draftitemid;

$draftitemid = file_get_submitted_draft_itemid('orgbanner');
file_prepare_draft_area($draftitemid, $context->id, 'local_eduvidual', 'orgbanner', $org->orgid,
    array('subdirs' => $form->subdirs, 'maxbytes' => $form->maxbytes, 'maxfiles' => 1));
$entry->orgbanner = $draftitemid;

$form->set_data($entry);
$form->display();

?>

<h5>Custom CSS</h5>
<?php
if (optional_param('customcssstore', 0, PARAM_INT)) {
    $org->customcss = optional_param('customcss', '', PARAM_TEXT);

    if ($DB->set_field('local_eduvidual_org', 'customcss', $org->customcss, array('orgid' => $org->orgid))) {
        echo "<p class=\"alert alert-success\">" . get_string('store:success', 'local_eduvidual') . "</p>";
    } else {
        echo "<p class=\"alert alert-warning\">" . get_string('store:error', 'local_eduvidual') . "</p>";
    }
}
?>
<textarea id="local_eduvidual_manage_customcss" data-orgid="<?php echo $org->orgid; ?>" style="width: 100%; min-height: 600px;"
          onkeyup="require(['local_eduvidual/manager'], function(MANAGER) { MANAGER.customcss(); });"><?php echo $org->customcss; ?></textarea>

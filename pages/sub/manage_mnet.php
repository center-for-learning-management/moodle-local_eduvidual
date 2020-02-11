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

if ($org->mnetid > 0) {
    $mnet = $DB->get_record('mnet_host', array('id' => $org->mnetid));
    $org->mnetname = $mnet->name;
    $org->mnetwwwroot = $mnet->wwwroot;
} else {
    $org->mnetname = '';
}

$context = context_system::instance();
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'block_eduvidual', 'mnetlogo', $org->orgid);
foreach ($files as $file) {
    if (str_replace('.', '', $file->get_filename()) != ""){
        $org->mnetlogo = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
    }
}

if (empty($org->mnetlogo)) {
    $org->mnetlogo = $CFG->wwwroot . '/blocks/eduvidual/pix/icon_missing.png';
}

echo $OUTPUT->render_from_template(
    'block_eduvidual/manage_mnet' . ((block_eduvidual::get('role') == 'Administrator') ? '_isadmin': ''),
    (object) array(
        'maildomain' => $org->maildomain,
        'maildomainteacher' => $org->maildomainteacher,
        'mnetlogo' => $org->mnetlogo,
        'mnetname' => $org->mnetname,
        'mnetwwwroot' => $org->mnetwwwroot,
        'orgid' => $org->orgid,
        'wwwroot' => $CFG->wwwroot,
    )
);

if (block_eduvidual::get('role') == 'Administrator') {
    require_once($CFG->dirroot . "/blocks/eduvidual/classes/manage_mnet_form.php");

    $form = new block_eduvidual_manage_mnet_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
    $context = context_system::instance();
    if ($data = $form->get_data()) {
        $org->mnetid = $data->mnetid;
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
}

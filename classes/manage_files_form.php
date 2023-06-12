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
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/formslib.php");

class local_eduvidual_manage_files_form extends moodleform {
    var $maxbytes = 1024*1024;
    var $areamaxbytes = 10485760;
    var $maxfiles = 10;
    var $subdirs = 0;
    function definition() {
        global $_url, $CFG;
        $mform = $this->_form;
        $mform->addElement('hidden', 'orgid', 0);
        $mform->setType('orgid', PARAM_INT);
        $mform->addElement('hidden', 'act', 'style');
        $mform->setType('act', PARAM_TEXT);

        $mform->addElement('header', 'orglogolbl', get_string('manage:style:orglogo:header', 'local_eduvidual'));
        $mform->addElement('filemanager', 'orglogo', get_string('manage:style:orglogo:filearealabel', 'local_eduvidual'), null,
            array(
                'subdirs' => $this->subdirs, 'maxbytes' => $this->maxbytes, 'areamaxbytes' => $this->areamaxbytes,
                'maxfiles' => 1, 'accepted_types' => array('image')
            )
        );

        $mform->addElement('header', 'orgbannerlbl', get_string('manage:style:orgbanner:header', 'local_eduvidual'));
        $mform->addElement('filemanager', 'orgbanner', get_string('manage:style:orgbanner:filearealabel', 'local_eduvidual'), null,
            array(
                'subdirs' => $this->subdirs, 'maxbytes' => $this->maxbytes, 'areamaxbytes' => $this->areamaxbytes,
                'maxfiles' => 1, 'accepted_types' => array('image')
            )
        );

        $mform->addElement('header', 'orgfileslbl', get_string('manage:style:orgfiles:header', 'local_eduvidual'));
        $mform->addElement('filemanager', 'orgfiles', get_string('manage:style:orgfiles:filearealabel', 'local_eduvidual', array('url' => $_url)), null,
            array(
                'subdirs' => $this->subdirs, 'maxbytes' => $this->maxbytes, 'areamaxbytes' => $this->areamaxbytes,
                'maxfiles' => $this->maxfiles, 'accepted_types' => array('image') //, 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL
            )
        );

        $this->add_action_buttons(true, get_string('manage:style:files:send', 'local_eduvidual'));
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

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
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//namespace block_eduvidual;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/formslib.php");

class block_eduvidual_admin_backgrounds_form extends moodleform {
    var $maxbytes = 1024*1024;
    var $areamaxbytes = 10485760*10;
    var $maxfiles = 500;
    var $subdirs = 0;
    function definition() {
        global $CFG;
        $mform = $this->_form;
        $mform->addElement('filemanager', 'backgrounds', get_string('admin:backgrounds:filearealabel', 'block_eduvidual'), null,
                    array(
                        'subdirs' => $this->subdirs, 'maxbytes' => $this->maxbytes, 'areamaxbytes' => $this->areamaxbytes,
                        'maxfiles' => $this->maxfiles, 'accepted_types' => array('image') //, 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL
                    )
                );
        $mform->addElement('submit', null, get_string('admin:backgrounds:files:send', 'block_eduvidual'));
        //$this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

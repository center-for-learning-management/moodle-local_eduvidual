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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//namespace block_eduvidual;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/formslib.php");

class block_eduvidual_admin_module_form extends moodleform {
    var $maxbytes = 1024*1024;
    var $areamaxbytes = 10485760;
    var $maxfiles = 1;
    var $subdirs = 0;
    function definition() {
        global $CFG;
        $mform = $this->_form;

        // Hidden data
        $mform->addElement('hidden', 'act', 'modulecats');
        $mform->setType('act', PARAM_TEXT);
        $mform->addElement('hidden', 'categoryid', '');
        $mform->setType('categoryid', PARAM_INT);
        $mform->addElement('hidden', 'moduleid', '');
        $mform->setType('moduleid', PARAM_INT);
        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);

        // General Data
        $mform->addElement('header', 'generaldata', get_string('admin:module:generaldata', 'block_eduvidual'));
        //$mform->setExpanded('generaldata');
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('text', 'type', get_string('admin:module:type', 'block_eduvidual'));
        $mform->setType('type', PARAM_TEXT);
        $mform->addElement('textarea', 'description', get_string('description'));
        $mform->setType('description', PARAM_TEXT);
        $mform->addElement('html', get_string('admin:module:payload:jsoneditor', 'block_eduvidual'));
        $mform->addElement('textarea', 'payload', get_string('admin:module:payload', 'block_eduvidual'), 'wrap="virtual" rows="20" cols="50"');
        $mform->setType('payload', PARAM_RAW);
        $mform->addElement('selectyesno', 'active', get_string('visible'));
        $mform->setType('active', PARAM_INT);
        $mform->closeHeaderBefore('buttonar');

        // Image
        $mform->addElement('filemanager', 'module', get_string('admin:module:filearealabel', 'block_eduvidual'), null,
                    array(
                        'subdirs' => $this->subdirs, 'maxbytes' => $this->maxbytes, 'areamaxbytes' => $this->areamaxbytes,
                        'maxfiles' => $this->maxfiles, 'accepted_types' => array('image') //, 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL
                    )
                );

        // LTI Data
        $mform->addElement('header', 'ltidata', get_string('admin:module:ltidata', 'block_eduvidual'));
        //$mform->setExpanded('ltidata');
        $mform->addElement('text', 'lticartridge', get_string('admin:module:lticartridge', 'block_eduvidual'));
        $mform->setType('lticartridge', PARAM_TEXT);
        $mform->addElement('text', 'ltilaunch', get_string('admin:module:ltilaunch', 'block_eduvidual'));
        $mform->setType('ltilaunch', PARAM_TEXT);
        $mform->addElement('text', 'ltisecret', get_string('admin:module:ltisecret', 'block_eduvidual'));
        $mform->setType('ltisecret', PARAM_TEXT);
        $mform->addElement('text', 'ltiresourcekey', get_string('admin:module:ltiresourcekey', 'block_eduvidual'));
        $mform->setType('ltiresourcekey', PARAM_TEXT);
        $mform->setDefault('ltiresourcekey', get_config('block_eduvidual', 'ltiresourcekey'));
        $mform->closeHeaderBefore('buttonar');

        $mform->addElement('submit', null, get_string('admin:module:files:send', 'block_eduvidual'));
        //$this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}

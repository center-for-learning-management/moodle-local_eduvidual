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

class local_eduvidual_admin_termsofuse_form extends moodleform {
    function definition() {
        global $DB;

        $editoroptions = array('subdirs'=>0, 'maxbytes'=>0, 'maxfiles'=>0,
                               'changeformat'=>0, 'context'=>null, 'noclean'=>0,
                               'trusttext'=>0, 'enable_filemanagement' => false);


        $mform = $this->_form;
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'locked', 0);
        $mform->setType('locked', PARAM_INT);

        $mform->addElement('html', get_string('admin:termsofuse:editingalwaysderivative', 'local_eduvidual'));

        $mform->addElement('checkbox', 'active', get_string('active'));
        $mform->setType('active', PARAM_BOOL);

        $mform->addElement('textarea', 'orgid_pattern', get_string('admin:termsofuse:orgidpattern', 'local_eduvidual'));
        $mform->setType('orgid_pattern', PARAM_TEXT);
        $mform->addHelpButton('orgid_pattern', 'admin:termsofuse:orgidpattern', 'local_eduvidual');

        $mform->addElement('textarea', 'role_pattern', get_string('admin:termsofuse:rolepattern', 'local_eduvidual'));
        $mform->setType('role_pattern', PARAM_TEXT);
        $mform->addHelpButton('role_pattern', 'admin:termsofuse:rolepattern', 'local_eduvidual');

        $mform->addElement('editor', 'termsofuse', get_string('admin:termsofuse', 'local_eduvidual'), $editoroptions);
        $mform->setType('termsofuse', PARAM_RAW);

        $this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

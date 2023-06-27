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

class local_eduvidual_manage_user_createuseranonymous_form extends moodleform {
    function definition() {
        global $DB;
        $mform = $this->_form;
        $mform->addElement('hidden', 'orgid', 0);
        $mform->setType('orgid', PARAM_INT);
        $mform->addElement('hidden', 'act', 'users');
        $mform->setType('act', PARAM_TEXT);
        $options = array(
            'Parent' => get_string('role:Parent', 'local_eduvidual'),
            'Student' => get_string('role:Student', 'local_eduvidual'),
            'Teacher' => get_string('role:Teacher', 'local_eduvidual'),
            'Manager' => get_string('role:Manager', 'local_eduvidual'),
        );
        $mform->addElement('select', 'role', get_string('manage:createuseranonymous:role', 'local_eduvidual'), $options);
        $mform->setType('role', PARAM_TEXT);
        $mform->addElement('text', 'bunch', get_string('manage:createuseranonymous:bunch', 'local_eduvidual'));
        $mform->setType('bunch', PARAM_TEXT);
        $mform->addElement('text', 'amount', get_string('manage:createuseranonymous:amount', 'local_eduvidual'), array('type' => 'number'));
        $mform->setType('amount', PARAM_INT);
        $mform->setDefault('amount', '10');

        $mform->addElement('submit', null, get_string('manage:createuseranonymous:send', 'local_eduvidual'));
        //$this->add_action_buttons();
    }

    //Custom validation should be added here
    function validation($data, $files) {
        $errors = array();
        if ($data['amount'] > 50) {
            $errors['amount'] = 'Maximum 50';
        }
        return $errors;
    }
}

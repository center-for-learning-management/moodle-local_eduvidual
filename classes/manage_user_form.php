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
 * @copyright  2019 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/formslib.php");

class local_eduvidual_manage_user_form extends moodleform {
    function definition() {
        global $DB;
        $mform = $this->_form;
        $mform->addElement('hidden', 'orgid', 0);
        $mform->setType('orgid', PARAM_INT);
        $mform->addElement('hidden', 'userid', 0);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('text', 'firstname', get_string('firstname'));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addElement('text', 'lastname', get_string('lastname'));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addElement('text', 'email', get_string('email'));
        $mform->setType('email', PARAM_TEXT);
        //$this->add_action_buttons();
    }

    //Custom validation should be added here
    function validation($data, $files) {
        $errors = array();
        if (strlen($data['firstname']) < 2) {
            $errors['firstname'] = get_string('manage:profile:tooshort', 'local_eduvidual', array('fieldname' => get_string('firstname'), 'minchars' => '2'));
        }
        if (strlen($data['lastname']) < 2) {
            $errors['lastname'] = get_string('manage:profile:tooshort', 'local_eduvidual', array('fieldname' => get_string('lastname'), 'minchars' => '2'));
        }
        if (!validate_email($data['email'])) {
            $errors['email'] = get_string('manage:profile:invalidmail', 'local_eduvidual');
        }
        return $errors;
    }
}

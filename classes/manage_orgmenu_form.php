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
 * @copyright  2020 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/formslib.php");

class local_eduvidual_manage_orgmenu_form extends moodleform {
    function definition() {
        global $DB;
        $mform = $this->_form;
        $mform->addElement('hidden', 'orgid', 0);
        $mform->setType('orgid', PARAM_INT);
        $mform->addElement('hidden', 'act', 'orgmenu');
        $mform->setType('act', PARAM_TEXT);

        $mform->addElement('textarea', 'orgmenu', get_string('manage:orgmenu:title', 'local_eduvidual'), array('style' => 'width: 100%'));
        $mform->setType('orgmenu', PARAM_TEXT);

        $this->add_action_buttons();
    }
}

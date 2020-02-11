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
 * @copyright  2019 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/formslib.php");

class block_eduvidual_manage_subcats_form extends moodleform {
    var $maxbytes = 1024*1024;
    var $areamaxbytes = 10485760;
    var $maxfiles = 1;
    var $subdirs = 0;
    function definition() {
        global $DB;
        $mform = $this->_form;
        $mform->addElement('hidden', 'orgid', 0);
        $mform->setType('orgid', PARAM_INT);
        $mform->addElement('hidden', 'act', 'subcats');
        $mform->setType('act', PARAM_TEXT);
        $mform->addElement('html', '<h4>' . get_string('manage:subcats:title', 'block_eduvidual') . '</h4>');
        $mform->addElement('html', '<p>' . get_string('manage:subcats:description', 'block_eduvidual') . '</p>');

        $mform->addElement('html', '<h5>' . get_string('manage:subcats:forcategories', 'block_eduvidual') . '</h5>');

        $mform->addElement('text', 'subcats1lbl', get_string('manage:subcats:subcat1', 'block_eduvidual'), array('style' => 'width: 100%;'));
        $mform->setType('subcats1lbl', PARAM_TEXT);
        $mform->setDefault('subcats1lbl', block_eduvidual::get('subcats1lbl'));

        $mform->addElement('textarea', 'subcats1', block_eduvidual::get('subcats1lbl'), 'wrap="virtual" rows="5" cols="30" style="width: 100%;"');
        $mform->setType('subcats1', PARAM_TEXT);
        $mform->setDefault('subcats1', $org->subcats1);

        $mform->addElement('text', 'subcats2lbl', get_string('manage:subcats:subcat2', 'block_eduvidual'), array('style' => 'width: 100%;'));
        $mform->setType('subcats2lbl', PARAM_TEXT);
        $mform->setDefault('subcats2lbl', block_eduvidual::get('subcats2lbl'));

        $mform->addElement('textarea', 'subcats2', block_eduvidual::get('subcats2lbl'), 'wrap="virtual" rows="5" cols="30" style="width: 100%;"');
        $mform->setType('subcats2', PARAM_TEXT);
        $mform->setDefault('subcats2', $org->subcats2);

        $mform->addElement('html', '<h5>' . get_string('manage:subcats:forcoursename', 'block_eduvidual') . '</h5>');

        $mform->addElement('text', 'subcats3lbl', get_string('manage:subcats:subcat3', 'block_eduvidual'), array('style' => 'width: 100%;'));
        $mform->setType('subcats3lbl', PARAM_TEXT);
        $mform->setDefault('subcats3lbl', block_eduvidual::get('subcats3lbl'));

        $mform->addElement('textarea', 'subcats3', block_eduvidual::get('subcats3lbl'), 'wrap="virtual" rows="5" cols="30" style="width: 100%;"');
        $mform->setType('subcats3', PARAM_TEXT);
        $mform->setDefault('subcats3', $org->subcats3);

        $mform->addElement('text', 'subcats4lbl', get_string('manage:subcats:subcat4', 'block_eduvidual'), array('style' => 'width: 100%;'));
        $mform->setType('subcats4lbl', PARAM_TEXT);
        $mform->setDefault('subcats4lbl', block_eduvidual::get('subcats4lbl'));

        $mform->addElement('submit', null, get_string('save'));
        //$this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

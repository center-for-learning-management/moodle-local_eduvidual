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

class local_eduvidual_manage_data_form extends moodleform {
    var $restrict_admin_edit = array('name', 'categoryid', 'courseid', 'mnetid'); // Fields that can only be modified by an admin
    var $restrict_admin_view = array('cap_type', 'cap_users', 'cap_course', 'cap_space'); // Fields that can only be modified by an admin

    // Filearea-Parameters in case we can upload a logo for mnet
    var $maxbytes = 1024*1024;
    var $areamaxbytes = 10485760;
    var $maxfiles = 1;
    var $subdirs = 0;

    function definition() {
        global $DB;
        $mform = $this->_form;
        $mform->addElement('hidden', 'orgid', 0);
        $mform->setType('orgid', PARAM_INT);

        $mform->addElement('text', 'name', '');
        $mform->addElement('name', PARAM_ALPHANUM);




        $mform->addElement('hidden', 'act', 'mnet');
        $mform->setType('act', PARAM_TEXT);
        $mnets = $DB->get_records_sql('SELECT id,name,wwwroot FROM {mnet_host} WHERE deleted=0 ORDER BY name ASC, wwwroot ASC', array());
        $options = array();
        $options[0] = get_string('manage:mnet:selectnone', 'local_eduvidual');
        foreach($mnets AS $mnet) {
            $options[$mnet->id] = $mnet->name . ' (' . $mnet->wwwroot . ')';
        }
        $mform->addElement('select', 'mnetid', get_string('manage:mnet', 'local_eduvidual'), $options);
        $mform->addElement('filemanager', 'mnetlogo', get_string('manage:mnet:filearealabel', 'local_eduvidual'), null,
                    array(
                        'subdirs' => $this->subdirds, 'maxbytes' => $this->maxbytes, 'areamaxbytes' => $this->areamaxbytes,
                        'maxfiles' => $this->maxfiles, 'accepted_types' => array('image') //, 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL
                    )
                );

                /*
                orgid	bigint(20)			Nein	kein(e)		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	3	name	varchar(250)	utf8mb4_unicode_ci		Nein			Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	4	mail	varchar(250)	utf8mb4_unicode_ci		Ja	NULL		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	5	phone	varchar(50)	utf8mb4_unicode_ci		Ja	NULL		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	6	street	varchar(250)	utf8mb4_unicode_ci		Ja	NULL		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	7	zip	bigint(10)			Ja	NULL		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	8	city	varchar(100)	utf8mb4_unicode_ci		Ja	NULL		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	9	district	varchar(100)	utf8mb4_unicode_ci		Ja	NULL		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	10	country	varchar(100)	utf8mb4_unicode_ci		Ja	NULL		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	11	authenticated	tinyint(10)			Ja	0		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	12	authtan	varchar(10)	utf8mb4_unicode_ci		Ja	NULL		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	13	cap_type	varchar(20)	utf8mb4_unicode_ci		Ja	NULL		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	14	cap_users	bigint(20)			Nein	-1		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	15	cap_courses	bigint(20)			Nein	-1		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	16	cap_space	bigint(20)			Nein	-1		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	17	categoryid	bigint(20)			Nein	0		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	18	courseid	bigint(20)			Nein	0		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	19	customcss	longtext	utf8mb4_unicode_ci		Ja	NULL		Bearbeiten Bearbeiten	Löschen Löschen
                Mehr
                	20	mnetid
                */

        $mform->addElement('submit', null, get_string('manage:mnet:send', 'local_eduvidual'));
        //$this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

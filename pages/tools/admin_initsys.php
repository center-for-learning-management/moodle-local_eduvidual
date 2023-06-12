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

/**
 * Start with a Moodle site from scratch and fill with dummy data for test environment.
 */

require_once('../../../../config.php');

$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/tools/admin_initsys.php', array('confirm' => $confirm));
$PAGE->set_title('Tests');
$PAGE->set_heading('Tests');

require_login();

echo $OUTPUT->header();

if (!is_siteadmin()) {
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => get_string('access_denied', 'local_eduvidual'),
        'type' => 'danger'
    ));
} elseif (empty($confirm)) {
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => 'Start setting up test environment',
        'type' => 'danger',
        'url' => new \moodle_url($PAGE->url, array('confirm' => 1)),
    ));
} else {
    $sql = "SELECT COUNT(id) AS amount FROM {user}";
    $chk = $DB->get_record_sql($sql);
    if ($chk->amount > 2) {
        echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => 'Set up cancelled - this site has >5 users',
            'type' => 'danger'
        ));
    } else {
        echo "<h3>Setting up test environment</h3>";
        $randomkey = md5($CFG->wwwroot . date("Y-m-d H:i.s"));
        // 1 Required configuration for local_eduvidual
        // 1a create required roles and set config
        set_config('defaultrolestudent', 5, 'local_eduvidual');
        set_config('defaultroleteacher', 3, 'local_eduvidual');
        $systemcontext = \context_system::instance();
        $rolenames = array(
            'defaultroleparent', 'defaultorgrolemanager', 'defaultorgroleparent',
            'defaultorgrolestudent', 'defaultorroleteacher',
            'defaultglobalrolemanager', 'defaultglobalroleparent',
            'defaultglobalrolestudent', 'defaultglobalroleteacher'
        );

        foreach ($rolenames as $rolename) {
            $filepath = $CFG->dirroot . '/local/eduvidual/pages/tools/admin_initsys/' . $rolename . '.xml';
            if (file_exists($filepath)) {
                $xml = file_get_contents($filepath);
                $definitiontable = new \core_role_define_role_table_advanced($systemcontext, 0);
                $definitiontable->force_preset($xml, $options);
                if (!empty($definitiontable->get_role_id())) {
                    set_config($rolename, $definitiontable->get_role_id(), 'local_eduvidual');
                }
            }
        }
        //
        // 1b create basement courses and set config
        /*
        set_config('coursebasementempty', $courseempty, 'local_eduvidual');
        set_config('coursebasementrestore', $courserestore, 'local_eduvidual');
        set_config('coursebasementtemplate', $coursetemplate, 'local_eduvidual');
        set_config('orgcoursebasement', $basement, 'local_eduvidual');
        set_config('supportcourseurl', $supportcourseurl, 'local_eduvidual')
        */

        // Create some orgs
        // Shared orgs
        // Protect shared orgs.
        //set_config('protectedorgs', $protectedorgs, 'local_eduvidual');
        // Create school orgs.


        // Set randomized lti resource
        set_config('ltiresourcekey', $randomkey, 'local_eduvidual');

        set_config('blockfooter', '', 'local_eduvidual');
        set_config('registrationcc', "support+$randomkey@lernmanagement.at", 'local_eduvidual');
        set_config('registrationsupport', "support+$randomkey@lernmanagement.at", 'local_eduvidual');


        // Create question categories on system level.
        //set_config('questioncategories', implode(",", $questioncategories), 'local_eduvidual');

    }

}


echo $OUTPUT->footer();

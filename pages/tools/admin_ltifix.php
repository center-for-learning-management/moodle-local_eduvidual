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
 * Fix missing lti enrolments for edupublisher_packages.
 */

require_once('../../../../config.php');

require_once($CFG->dirroot . '/enrol/lti/lib.php');
require_once($CFG->dirroot . '/enrol/lti/classes/helper.php');
$elp = new enrol_lti_plugin();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/tools/admin_ltifix.php');
$PAGE->set_title('LTI Fix');
$PAGE->set_heading('LTI Fix');

require_login();

if (!is_siteadmin()) {
    throw new \moodle_exception(get_string('access_denied', 'local_eduvidual'));
    die();
}

echo $OUTPUT->header();
$sql = "SELECT * FROM moodle.oer_block_edupublisher_packages p WHERE course NOT IN (
		SELECT instanceid FROM moodle.oer_context c WHERE c.contextlevel = 50 AND c.id IN (
			SELECT l.contextid FROM moodle.oer_enrol_lti_tools l
		)
	);";

$packages = $DB->get_records_sql($sql);
$channels = [ 'etapas', 'eduthek' ];
foreach ($packages as $dbpackage) {
    $package = \block_edupublisher::get_package($dbpackage->id, true);
    foreach ($channels as $channel) {
        $publish = !empty($package->{$channel . '_publishas'});
        if (!empty($publish)) {
            echo "Want to publish $package->course $package->title<br />";
            $course = $DB->get_record('course', [ 'id' => $package->course ]);
            if (empty($course->id)) {
                 echo "=> THIS COURSE WAS REMOVED<br />";
                 $DB->delete_records('block_edupublisher_uses', array('package' => $id));
                 $DB->delete_records('block_edupublisher_rating', array('package' => $id));
                 $DB->delete_records('block_edupublisher_metadata', array('package' => $id));

                 $DB->delete_records('block_edupublisher_extitem', array('packageid' => $package->id));
                 $DB->delete_records('block_edupublisher_extsect', array('packageid' => $package->id));
                 $DB->delete_records('block_edupublisher_extpack', array('packageid' => $package->id));

                 $DB->delete_records('block_edupublisher_packages', array('id' => $package->id));
                 continue;
            }

            $context = \context_course::instance($package->course);
            $course = get_course($package->course, IGNORE_MISSING);

            if (empty($package->{$channel . '_ltisecret'})) {
                $package->{$channel . '_ltisecret'} = substr(md5(date("Y-m-d H:i:s") . rand(0,1000)),0,30);
            }
            $lti = array(
                'contextid' => $context->id,
                'gradesync' => 1,
                'gradesynccompletion' => 0,
                'membersync' => 1,
                'membersyncmode' => 1,
                'name' => $package->title . ' [' . $channel . ']',
                'roleinstructor' => get_config('block_edupublisher', 'defaultrolestudent'),
                'rolelearner' => get_config('block_edupublisher', 'defaultrolestudent'),
                'secret' => $package->{$channel . '_ltisecret'},
            );
            $elpinstanceid = $elp->add_instance($course, $lti);
            echo "=> ELPInstanceID $elpinstanceid<br />";
            if ($elpinstanceid) {
                $elpinstance = $DB->get_record('enrol_lti_tools', array('enrolid' => $elpinstanceid), 'id', MUST_EXIST);
                $tool = enrol_lti\helper::get_lti_tool($elpinstance->id);
                $package->{$channel . '_ltiurl'} = '' . enrol_lti\helper::get_launch_url($elpinstance->id);
                $package->{$channel . '_lticartridge'} = '' . enrol_lti\helper::get_cartridge_url($tool);
                echo "=> Lti-Data " . $package->{$channel . '_ltiurl'} . " and " . $package->{$channel . '_lticartridge'} . "<br />";
                \block_edupublisher::store_metadata($package, $channel, $channel . '_ltiurl');
                \block_edupublisher::store_metadata($package, $channel, $channel . '_lticartridge');
                \block_edupublisher::store_metadata($package, $channel, $channel . '_ltisecret');
                echo "=> Update package metadata<br />";
            }
        }
    }
}
echo $OUTPUT->footer();

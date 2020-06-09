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
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
if (!is_siteadmin()) die;

$id = optional_param('id', 0, PARAM_INT);

if ($id != 0) {
    if ($id > 0) {
        $termsofuse = $DB->get_record('local_eduvidual_terms', array('id' => $id), '*', MUST_EXIST);
    } else {
        $termsofuse = (object) array(
            'active' => 0,
            'locked' => 0,
            'orgid_pattern' => '',
            'role_pattern' => '',
            'terms' => '',
        );
    }
    require_once($CFG->dirroot . '/local/eduvidual/classes/local_eduvidual_admin_termsofuse_form.php');
    $form = new local_eduvidual_admin_termsofuse_form();
    $context = context_system::instance();
    if ($data = $form->get_data()) {
        if ($termsofuse->locked > 0) {
            // These terms had already been active and should hence be stored as a derivative
            $data->id = 0;
            $data->id = $DB->insert_record('local_eduvidual_terms', $data, true);
            $termsofuse = $data;
        }
        if ($data->id > 0) {
            // Update terms of use
            $DB->update_record('local_eduvidual_terms', $data);
        }
        echo "<p class=\"alert alert-success\">" . get_string('store:success', 'local_eduvidual') . "</p>";
    }

    $form->set_data($termsofuse);
    $form->display();
} else {
    $terms = $DB->get_records('local_eduvidual_terms', array());
    echo $OUTPUT->render_from_template(
        'local_eduvidual/admin_termsofuse_list',
        (object) array('terms' => $terms, 'wwwroot' => $CFG->wwwroot)
    );
}

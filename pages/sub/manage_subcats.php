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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . "/local/eduvidual/classes/manage_subcats_form.php");

$form = new local_eduvidual_manage_subcats_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
if ($data = $form->get_data()) {
    local_eduvidual::$org->subcats1 = $data->subcats1;
    local_eduvidual::$org->subcats2 = $data->subcats2;
    local_eduvidual::$org->subcats3 = $data->subcats3;
    local_eduvidual::$org->subcats1lbl = $data->subcats1lbl;
    local_eduvidual::$org->subcats2lbl = $data->subcats2lbl;
    local_eduvidual::$org->subcats3lbl = $data->subcats3lbl;
    local_eduvidual::$org->subcats4lbl = $data->subcats4lbl;
    $DB->update_record('local_eduvidual_org', local_eduvidual::$org);
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array('type' => 'success', 'content' => get_string('store:success', 'local_eduvidual')));
}
$form = new local_eduvidual_manage_subcats_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
$form->set_data(local_eduvidual::$org);
$form->display();

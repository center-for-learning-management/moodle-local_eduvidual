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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . "/blocks/eduvidual/classes/manage_subcats_form.php");

$form = new block_eduvidual_manage_subcats_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
if ($data = $form->get_data()) {
    block_eduvidual::$org->subcats1 = $data->subcats1;
    block_eduvidual::$org->subcats2 = $data->subcats2;
    block_eduvidual::$org->subcats3 = $data->subcats3;
    block_eduvidual::$org->subcats1lbl = $data->subcats1lbl;
    block_eduvidual::$org->subcats2lbl = $data->subcats2lbl;
    block_eduvidual::$org->subcats3lbl = $data->subcats3lbl;
    block_eduvidual::$org->subcats4lbl = $data->subcats4lbl;
    $DB->update_record('block_eduvidual_org', block_eduvidual::$org);
    echo $OUTPUT->render_from_template('block_eduvidual/alert', array('type' => 'success', 'content' => get_string('store:success', 'block_eduvidual')));
}
$form = new block_eduvidual_manage_subcats_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
$form->set_data(block_eduvidual::$org);
$form->display();

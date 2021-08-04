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

?>
<h5><?php echo get_string('manage:orgmenu:title', 'local_eduvidual'); ?></h5>
<p class="alert alert-info"><?php echo get_string('manage:orgmenu:description', 'local_eduvidual'); ?></p>
<p class="alert alert-danger"><?php echo get_string('manage:orgmenu:cachewarning', 'local_eduvidual'); ?></p>
<?php
require_once($CFG->dirroot . "/local/eduvidual/classes/manage_orgmenu_form.php");

$form = new local_eduvidual_manage_orgmenu_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);

if ($data = $form->get_data()) {
    $org->orgmenu = $data->orgmenu;
    $DB->set_field('local_eduvidual_org', 'orgmenu', $org->orgmenu, array('orgid' => $org->orgid));
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => get_string('store:success', 'local_eduvidual'),
        'type' => 'success'
    ));
    echo "<script>window.sessionStorage.clear();</script>\n";
}

$form->set_data($org);
$form->display();

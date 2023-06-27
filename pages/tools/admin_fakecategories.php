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
 * Create a huge amount of fake categories.
 */

require_once('../../../../config.php');

// The parent node we append to.
$parent = optional_param('parent', 0, PARAM_INT);
// How many children per node should be created.
$children = optional_param('children', 0, PARAM_INT);
// The depth of sub-nodes we create
$depth = optional_param('depth', 0, PARAM_INT);


$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/tools/admin_fakecategories.php', array(
    'parent' => $parent, 'children' => $children, 'depth' => $depth,
));
$PAGE->set_title('Fake categories');
$PAGE->set_heading('Fake categories');

require_login();

if (!is_siteadmin()) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => get_string('access_denied', 'local_eduvidual'),
        'type' => 'danger',
    ));
    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->header();

?>
    <div class="alert alert-danger">
        Attention, be carefully with this!!!
        <form action="" method="post" enctype="multipart/form-data">
            <div class="grid-eq-3">
                <div>
                    <input type="number" name="parent" placeholder="Parent category" value="<?php echo $parent; ?>"/>
                </div>
                <div>
                    <input type="number" name="children" placeholder="Children per node" value="<?php echo $children; ?>"/>
                </div>
                <div>
                    <input type="number" name="depth" placeholder="Depth of nodes" value="<?php echo $depth; ?>"/>
                </div>
            </div>
            <div>
                <input type="submit" value="create" class="btn btn-primary btn-block"/>
            </div>
        </form>
    </div>


<?php

if (!empty($parent)) {
    $category = $DB->get_record('course_categories', array('id' => $parent));
    if (empty($category->id)) {
        echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => 'Invalid categoryid',
            'type' => 'danger',
        ));
    } else {
        echo "Create in $parent $children children with a depth of $depth";
        admin_fakecategories_create($category, $children, $depth, 'fake#' . $category->id);
    }

}

echo $OUTPUT->footer();

function admin_fakecategories_create($parentnode, $children, $depth, $namepath) {
    if ($depth < 1)
        return;
    $depth--;
    global $OUTPUT;
    if (empty($parentnode->id)) {
        echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => 'Invalid categoryid for parentnode ' . $namepath,
            'type' => 'danger',
        ));
    } else {
        for ($a = 1; $a <= $children; $a++) {
            $data = (object)array(
                'name' => $namepath . '/' . $a,
                'description' => 'created by category faker',
                'descriptionformat' => 0,
                'parent' => $parentnode->id,
                'visible' => 1,
            );
            $subnode = \core_course_category::create($data);
            echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
                'content' => 'Created subnode ' . $subnode->name,
                'type' => 'success',
            ));
            admin_fakecategories_create($subnode, $children, $depth, $namepath . '/' . $a);
        }
    }
}

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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$questioncategories = explode(",", get_config('local_eduvidual', 'questioncategories'));
$userqcats = array();
$userwants = $DB->get_records('local_eduvidual_userqcats', array('userid' => $USER->id));
foreach($userwants AS $qcat) {
    $userqcats[] = $qcat->categoryid;
}

// User views question categories
$needle = $CFG->wwwroot . '/question/category.php?courseid=' . $PAGE->course->id .  '&edit=';
$anchors = pq($qcatslevel10)->find('li a:first-child');
foreach($anchors AS $anchor) {
    //print_r($anchor);
    $href = pq($anchor)->attr('href');
    $cat = str_replace($needle, '', $href);

    if (in_array($cat, $questioncategories) && !in_array($cat, $userqcats)) {
        // We only flag to remove later, otherwise we would get an exception as $anchor is null
        pq($anchor)->parent()->parent()->addClass('REMOVE_ME');
        pq($anchor)->html('! ' . pq($anchor)->html());
    }
}
if(!is_siteadmin()) {
    pq('.REMOVE_ME')->remove();
}

// User views question bank
$localized_coresystem = get_string('coresystem');
$optgroup = pq('optgroup[label="' . $localized_coresystem . '"]');
$options = pq($optgroup)->find('option');

$skiptrigger = false;
foreach($options AS $option) {
    // Here we should only have question-categories from the core system
    $cat = explode(',', pq($option)->attr('value'));
    $cat = $cat[0];
    if (in_array($cat, $questioncategories) && !in_array($cat, $userqcats)) {
        $skiptrigger = true;
    } elseif(in_array($cat, $questioncategories)) {
        $skiptrigger = false;
    }
    if ($skiptrigger) {
        pq($option)->html('! ' . pq($option)->html())->addClass('REMOVE_ME');
        //pq($option)->html('REMOVED ' . pq($option)->html());
    }
}
if(!is_siteadmin()) {
    pq('optgroup[label="' . $localized_coresystem . '"] .REMOVE_ME')->remove();
}

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
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG, $COURSE, $DB, $PAGE, $SESSION, $USER;

if (!class_exists('phpQuery', true)) {
    require_once($CFG->dirroot . '/blocks/eduvidual/vendor/somesh/php-query/phpQuery/phpQuery.php');
}
try {
    // Load available organistions
    $orgs = block_eduvidual::get_organisations('*');
    $orgids = array();
    foreach($orgs AS $org) {
        $orgids[] = $org->orgid;
    }
    $doc = phpQuery::newDocumentHTML($buffer);
    if ($PAGE->context->contextlevel == CONTEXT_COURSE) {
        //echo "set org by courseid " . $PAGE->course->id;
        block_eduvidual::set_org_by_courseid($PAGE->course->id);
    } elseif ($PAGE->context->contextlevel == CONTEXT_COURSECAT && isset($PAGE->category) && isset($PAGE->category->id)) {
        //echo "set org by categoryid " . $PAGE->category->id;
        block_eduvidual::set_org_by_categoryid($PAGE->category->id);
    }

    $org = block_eduvidual::get('org');

    require($CFG->dirroot . '/blocks/eduvidual/buffer/html_hook_login.php');
    require($CFG->dirroot . '/blocks/eduvidual/buffer/html_hook_head.php');

    require($CFG->dirroot . '/blocks/eduvidual/buffer/html_hook_pages.php');

    if ($PAGE->context->contextlevel == CONTEXT_COURSE && $PAGE->course->id > 1 && count(pq('.section-modchooser')) > 0) {
        require($CFG->dirroot . '/blocks/eduvidual/buffer/html_hook_enhance_courseedit.php');
    }

    pq('body')->addClass('theme-' . $CFG->theme);
    require($CFG->dirroot . '/blocks/eduvidual/buffer/html_theme_boost.php');

    /**
     * BELOW THIS LINE WE ARE CHECKING FOR THE Q\UESTION BANK
    **/
    $qcatslevel10 = pq('div.questioncategories.contextlevel10');
    $localized_coresystem = get_string('coresystem');
    if (pq($qcatslevel10)->length() > 0 || pq('optgroup[label="' . $localized_coresystem . '"]')->length() > 0) {
        require($CFG->dirroot . '/blocks/eduvidual/buffer/html_hook_qcats.php');
    }

    // Disable Ajax for all pages - ever (this breaks fileareas)
    pq('a, form')->attr('data-ajax', 'false');

    $buffer = $doc->htmlOuter();

} catch(Exception $e) {
    error_log("Parsing of Page failed");
    error_log($e);
    //error_log($buffer);
}

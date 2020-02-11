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

global $CFG, $DB, $PAGE, $USER;

// Modify Logout-Button
pq("a[href*='/login/logout.php?sesskey']")->attr('href', $CFG->wwwroot . '/blocks/eduvidual/pages/logout.php');

// Not sure if this code is obsolete....
if (optional_param('contentonly', 0, PARAM_INT) == 1) {
    $selectors = implode(', ', array(
        "body #page-header",
        "body #page-wrapper>nav",
        "body #nav-drawer",
        "body #region-main-box>section:not(#region-main)",
        "body #page-footer",
        "body>.footnote",
    ));
    pq('body')->addClass('pagelayout-embedded');
    pq($selectors)->addClass('hidden');
}

$selectors = array(
    'iframe[src^="' . $CFG->wwwroot . '"]',
);
if (optional_param('contentonly', 0, PARAM_INT) == 1) {
    $selectors = array_merge($selectors, array(
        'a[href^="' . $CFG->wwwroot . '"]',
        'form[action^="' . $CFG->wwwroot . '"]',
        'a:not([href*="https://"])',
        'a:not([href*="http://"])',
        'form:not([action*="https://"])',
        'form:not([action*="http://"])',
    ));
}
$selectors = implode(', ', $selectors);
$els = pq($selectors);
foreach ($els AS $el) {
    $action = pq($el)->attr('action');
    $href = pq($el)->attr('href');
    $src = pq($el)->attr('src');

    if (!empty($action)) {
        $attr = 'action';
    } elseif(!empty($href)) {
        $attr = 'href';
    } elseif(!empty($src)) {
        $attr = 'src';
    }
    if (empty($attr)) continue;
    $url = explode("#", ${$attr});
    if (!empty($url[0])) {
        if (strpos($url[0], '?') === false) {
            $url[0] .= '?';
        }
        $url[0] .= '&contentonly=1';
        pq($el)->attr($attr, implode('#', $url));
    }
}

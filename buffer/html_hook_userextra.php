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

// Print the users background image only if page is not embedded!
$extra = block_eduvidual::get('userextra');
if (!empty(block_eduvidual::$pagelayout)) {
    pq('body')->attr('style', 'background-color: transparent');
} elseif (!isguestuser($USER) && isset($extra->background) && !empty($extra->background)) {
    $wrapper = pq('<div>');
    pq($wrapper)->attr('style', 'position: fixed; top: 0px; width: 100vw; height: 100vh; left: 0px; z-index: -1; -webkit-transform: translate3d(0, 0, 0); transform : translate3d(0, 0, 0); overflow:hidden; -webkit-overflow-scrolling:touch;');
    pq($wrapper)->addClass('hide-on-print');
    $bg = pq('<div>');
    pq($bg)->attr('id', 'block_eduvidual_background');
    pq($bg)->attr('style', 'background-image: url(' . $extra->background . '); background-size: cover; background-attachment: fixed; background-position: center center; width: 100vw; height: 100vh; overflow: hidden; -webkit-overflow-scrolling: touch;');
    pq($wrapper)->append($bg);
    pq('body')->append($wrapper);
}

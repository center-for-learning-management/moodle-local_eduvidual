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
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class block_eduvidual_lib_helper {
    /**
     * Makes a natural sort on an array of objects.
     * @param os The array.
     * @param indexname the property name of the objects that is used for sorting.
    **/
    public static function natsort($os, $indexname, $debugname = '') {
        global $reply;
        $reply['natsort_' . $debugname . '_os'] = $os;
        $unsortedos = array();
        foreach($os AS $o) {
            $unsortedos[$o->{$indexname}] = $o;
        }
        $reply['natsort_' . $debugname . '_unsortedos'] = $unsortedos;
        $indices = array_keys($unsortedos);
        natcasesort($indices);
        $reply['natsort_' . $debugname . '_indices'] = $indices;
        $sorted = array();
        foreach($indices AS $index) {
            $sorted[] = $unsortedos[$index];
        }
        $reply['natsort_' . $debugname . '_sorted'] = $sorted;
        return $sorted;
    }
}

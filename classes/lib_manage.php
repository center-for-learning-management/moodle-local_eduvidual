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

namespace local_eduvidual;

defined('MOODLE_INTERNAL') || die;

class lib_manage {
    /**
     * Sum up all filesizes for all contexts within a category.
     * @param the parent categoryid
     */
    public static function get_category_filesize($categoryid) {
        global $DB;
        $context = \context_coursecat::instance($categoryid, 'IGNORE_MISSING');
        if (empty($context->id)) {
            // This context does not exist.
            return -1;
        }
        $db_context = $DB->get_record('context', array('id' => $context->id));

        $sql = "SELECT SUM(filesize) fs
                    FROM {files}
                    WHERE contextid IN (
                        SELECT id
                                    FROM {context}
                                    WHERE `path` LIKE ?
                    )";

        $sizes = $DB->get_records_sql($sql, array($db_context->path . '/%'));
        foreach($sizes AS $size) { break; }
        return $size->fs;
    }
    public static function readable_filesize($bytes, $decimals = 2) {
        $factor = floor((strlen($bytes) - 1) / 3);
        if ($factor > 0) $sz = 'KMGT';
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor - 1] . 'B';
    }
}

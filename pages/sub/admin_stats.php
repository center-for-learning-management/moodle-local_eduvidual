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
if (!is_siteadmin()) die;

// Restrit
$ROWS = array(
    0 => array(
        0 => array('id' => 0, 'label' => ''),
        1 => array('id' => 1, 'label' => 'Burgenland'),
        2 => array('id' => 2, 'label' => 'Kärnten'),
        3 => array('id' => 3, 'label' => 'Niederösterreich'),
        4 => array('id' => 4, 'label' => 'Oberösterreich'),
        5 => array('id' => 5, 'label' => 'Salzburg'),
        6 => array('id' => 6, 'label' => 'Steiermark'),
        7 => array('id' => 7, 'label' => 'Tirol'),
        8 => array('id' => 8, 'label' => 'Vorarlberg'),
        9 => array('id' => 9, 'label' => 'Wien'),
    ),
    1 => array(
        0 => array('id' => 0, 'label' => 'Sonstige'),
        1 => array('id' => 1, 'label' => 'VS'),
        2 => array('id' => 2, 'label' => 'HS/NMS/MS'),
        3 => array('id' => 3, 'label' => 'Sonderschule'),
        4 => array('id' => 4, 'label' => 'PTS'),
        5 => array('id' => 5, 'label' => 'BS'),
        6 => array('id' => 6, 'label' => 'Gymnasium'),
        7 => array('id' => 7, 'label' => 'HTL'),
        8 => array('id' => 8, 'label' => 'HAK'),
        9 => array('id' => 9, 'label' => 'HUM'),
    ),
);

$STATS = array();

for ($z = 0; $z < 2; $z++) {
    $STATS[$z] = array(
        'id' => $z,
        'label' => 'n/a',
        'restrictions' => $ROWS[($z == 0) ? 1 : 0],
        'rows' => $ROWS[$z],
        'sum_all' => 0,
        'sum_lpfeduv' => 0,
        'sum_neweduv' => 0,
        'sum_reg' => 0,
    );
    $restriction = optional_param('restriction_' . $z, -1, PARAM_INT);
    if ($restriction > -1) {
        $STATS[$z]['restrictions'][$restriction]['selected'] = '1';
    }

    switch($z) {
        case 0:
            $STATS[$z]['label'] = get_string('admin:stats:states', 'local_eduvidual');
            $rest = (($restriction > -1) ? "AND RIGHT(orgid, 1) = " . $restriction : "");
            $sql = "SELECT LEFT(orgid,1) AS ord, count(id) AS cnt
                        FROM {local_eduvidual_org}
                        WHERE orgid LIKE '______'
                            $rest
                        GROUP BY ord
                        ORDER BY ord ASC";
            $all = $DB->get_records_sql($sql, array());

            $sql = "SELECT LEFT(orgid,1) AS ord, count(id) AS cnt
                        FROM {local_eduvidual_org}
                        WHERE orgid LIKE '______'
                            $rest
                            AND authenticated>0
                        GROUP BY ord
                        ORDER BY ord ASC";
            $registered = $DB->get_records_sql($sql, array());

            $sql = "SELECT LEFT(orgid,1) AS ord, count(id) AS cnt
                        FROM {local_eduvidual_org}
                        WHERE orgid LIKE '______'
                            $rest
                            AND lpf IS NOT NULL
                        GROUP BY ord
                        ORDER BY ord ASC";
            $lpf = $DB->get_records_sql($sql, array());

            $sql = "SELECT LEFT(orgid,1) AS ord, count(id) AS cnt
                        FROM {local_eduvidual_org}
                        WHERE orgid LIKE '______'
                            $rest
                            AND lpf IS NOT NULL
                            AND authenticated>0
                        GROUP BY ord
                        ORDER BY ord ASC";
            $lpfeduv = $DB->get_records_sql($sql, array());
        break;
        case 1:
            $STATS[$z]['label'] = get_string('admin:stats:types', 'local_eduvidual');
            $rest = (($restriction > -1) ? "AND LEFT(orgid, 1) = " . $restriction : "");

            $sql = "SELECT RIGHT(orgid,1) AS ord, count(id) AS cnt
                        FROM {local_eduvidual_org}
                        WHERE orgid LIKE '______'
                            $rest
                        GROUP BY ord
                        ORDER BY ord ASC";
            $all = $DB->get_records_sql($sql, array());

            $sql = "SELECT RIGHT(orgid,1) AS ord, count(id) AS cnt
                        FROM {local_eduvidual_org}
                        WHERE orgid LIKE '______'
                            $rest
                            AND authenticated>0
                        GROUP BY ord
                        ORDER BY ord ASC";
            $registered = $DB->get_records_sql($sql, array());

            $sql = "SELECT RIGHT(orgid,1) AS ord, count(id) AS cnt
                        FROM {local_eduvidual_org}
                        WHERE orgid LIKE '______'
                            $rest
                            AND lpf IS NOT NULL
                        GROUP BY ord
                        ORDER BY ord ASC";
            $lpf = $DB->get_records_sql($sql, array());

            $sql = "SELECT RIGHT(orgid,1) AS ord, count(id) AS cnt
                        FROM {local_eduvidual_org}
                        WHERE orgid LIKE '______'
                            $rest
                            AND lpf IS NOT NULL
                            AND authenticated>0
                        GROUP BY ord
                        ORDER BY ord ASC";
            $lpfeduv = $DB->get_records_sql($sql, array());
        break;
    }

    foreach ($STATS[$z]['rows'] AS $a => $row) {
        if (empty($row['label'])) continue;
        if (!empty($all[$a]->cnt)) {
            $rate = round(intval(@$registered[$a]->cnt) / intval(@$all[$a]->cnt) * 100, 1) . '%';
        } else {
            $rate = 'n/a';
        }
        $neweduv = number_format(@$registered[$a]->cnt, 0, ",", ".");

        $STATS[$z]['states'][] = array(
            'cntall' => !empty(@$all[$a]->cnt) ? number_format(@$all[$a]->cnt, 0, ",", ".") : '-',
            'cntlpfeduv' => !empty(@$lpf_and_eduv) ? number_format($lpf_and_eduv, 0, ",", ".") : '-',
            'cntneweduv' => $neweduv,
            'cntreg' => !empty(@$registered[$a]->cnt) ? number_format(@$registered[$a]->cnt, 0, ",", ".") : '-',
            'name' => $row['label'],
            'rate' => $rate,
        );
        $STATS[$z]['sum_all'] += @$all[$a]->cnt;
        $STATS[$z]['sum_lpfeduv'] += @$lpfeduv[$a]->cnt;
        $STATS[$z]['sum_neweduv'] += $neweduv;
        $STATS[$z]['sum_reg'] += @$registered[$a]->cnt;
    }
    $STATS[$z]['sum_all'] = number_format($STATS[$z]['sum_all'], 0, ",", ".");
    $STATS[$z]['sum_lpfeduv'] = number_format($STATS[$z]['sum_lpfeduv'], 0, ",", ".");
    $STATS[$z]['sum_neweduv'] = number_format($STATS[$z]['sum_neweduv'], 0, ",", ".");
    $STATS[$z]['sum_reg'] = number_format($STATS[$z]['sum_reg'], 0, ",", ".");
}
$o = array(
    'show_all' => optional_param('show_all', true, PARAM_BOOL),
    'show_lpfeduv' => optional_param('show_lpfeduv', true, PARAM_BOOL),
    'show_neweduv' => optional_param('show_neweduv', true, PARAM_BOOL),
    'show_rate' => optional_param('show_rate', true, PARAM_BOOL),
    'show_registered' => optional_param('show_registered', true, PARAM_BOOL),
    'stats' => $STATS,
);

echo $OUTPUT->render_from_template('local_eduvidual/admin_stats', $o);

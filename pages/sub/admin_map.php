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
$updatedb = optional_param('updatedb', 0, PARAM_INT);
if ($updatedb == 0) {
    echo $OUTPUT->render_from_template('local_eduvidual/admin_map', array(
        "filters_count" => 3,
        "filters" => array(
            array(
                "filterid" => "platformtypes",
                "selections" => array(
                    array(
                        "checked" => true,
                        "key" => "eduv",
                        "label" => get_string("admin:map:eduv", "local_eduvidual"),
                        "icon" => $OUTPUT->image_icon('google-maps-pin-green', 'pin', 'local_eduvidual'),
                    ),
                    array(
                        "checked" => true,
                        "key" => "both",
                        "label" => get_string("admin:map:both", "local_eduvidual"),
                        "icon" => $OUTPUT->image_icon('google-maps-pin-orange', 'pin', 'local_eduvidual'),
                    ),
                    array(
                        "checked" => true,
                        "key" => "lpf",
                        "label" => get_string("admin:map:lpf", "local_eduvidual"),
                        "icon" => $OUTPUT->image_icon('google-maps-pin-blue', 'pin', 'local_eduvidual'),
                    ),
                    array(
                        "checked" => true,
                        "key" => "none",
                        "label" => get_string("admin:map:none", "local_eduvidual"),
                        "icon" => $OUTPUT->image_icon('google-maps-pin-lightgray', 'pin', 'local_eduvidual'),
                    ),
                ),
            ),
            array(
                "filterid" => "districttypes",
                "selections" => array(
                    array(
                        "checked" => true,
                        "key" => "Burgenland",
                        "label" => "Burgenland",
                    ),
                    array(
                        "checked" => true,
                        "key" => "Kärnten",
                        "label" => "Kärnten",
                    ),
                    array(
                        "checked" => true,
                        "key" => "Niederösterreich",
                        "label" => "Niederösterreich",
                    ),
                    array(
                        "checked" => true,
                        "key" => "Oberösterreich",
                        "label" => "Oberösterreich",
                    ),
                    array(
                        "checked" => true,
                        "key" => "Salzburg",
                        "label" => "Salzburg",
                    ),
                    array(
                        "checked" => true,
                        "key" => "Steiermark",
                        "label" => "Steiermark",
                    ),
                    array(
                        "checked" => true,
                        "key" => "Tirol",
                        "label" => "Tirol",
                    ),
                    array(
                        "checked" => true,
                        "key" => "Vorarlberg",
                        "label" => "Vorarlberg",
                    ),
                    array(
                        "checked" => true,
                        "key" => "Wien",
                        "label" => "Wien",
                    ),
                ),
            ),
            array(
                "filterid" => "orgtypes",
                "selections" => array(
                    array(
                        "checked" => true,
                        "key" => "VS",
                        "label" => "VS",
                    ),
                    array(
                        "checked" => true,
                        "key" => "MS",
                        "label" => "NMS/MS",
                    ),
                    array(
                        "checked" => true,
                        "key" => "Sonderschule",
                        "label" => "Sonderschule",
                    ),
                    array(
                        "checked" => true,
                        "key" => "PTS",
                        "label" => "PTS",
                    ),
                    array(
                        "checked" => true,
                        "key" => "BS",
                        "label" => "BS",
                    ),
                    array(
                        "checked" => true,
                        "key" => "Gymnasium",
                        "label" => "Gymnasium",
                    ),
                    array(
                        "checked" => true,
                        "key" => "HTL",
                        "label" => "HTL",
                    ),
                    array(
                        "checked" => true,
                        "key" => "HAK",
                        "label" => "HAK",
                    ),
                    array(
                        "checked" => true,
                        "key" => "HUM",
                        "label" => "HUM",
                    ),
                ),
            ),

        ),
    ));
} else {
    echo "<h3>Updating GPS-Data</h3><ul>\n";
    $mapquestkey = get_config('local_eduvidual', 'mapquest_apikey');
    $googlekey = get_config('local_eduvidual', 'google_apikey');

    /*
    if ($usemapquest && empty($mapquestkey)) {
        echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => get_string('admin:map:mapquest:apikey:description', 'local_eduvidual'),
            'type' => 'warning',
            'url' => '/admin/settings.php?section=blocksettingeduvidual',
        ));
        echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => get_string('admin:map:nominatim:directly', 'local_eduvidual'),
            'type' => 'warning',
            'url' => '/local/eduvidual/pages/admin.php?act=map&updatedb=1&limit=' . $limit,
        ));
    } elseif ($usegoogle && empty($googlekey)) {
        echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => get_string('admin:map:google:apikey:description', 'local_eduvidual'),
            'type' => 'warning',
            'url' => '/admin/settings.php?section=blocksettingeduvidual',
        ));
        echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => get_string('admin:map:nominatim:directly', 'local_eduvidual'),
            'type' => 'warning',
            'url' => '/local/eduvidual/pages/admin.php?act=map&updatedb=1&limit=' . $limit,
        ));
    } else {
    */
        if (optional_param('resetfailed', 0, PARAM_INT) == 1) {
            $DB->execute("UPDATE {local_eduvidual_org_gps} SET failed=0", array());
        }
        // Only update orgs that have not been updated in the last week!
        $since = time() - 60*60*24*7;
        $limit = optional_param('limit', 500, PARAM_INT);
        $sql = "SELECT o.orgid,o.street,o.zip,o.city,o.district,o.country
                    FROM {local_eduvidual_org} o, {local_eduvidual_org_gps} og
                    WHERE o.orgid=og.orgid
                        AND o.orgid LIKE ?
                        AND og.modified < ?
                        AND og.failed < ?
                    LIMIT 0," . $limit;
        $orgs = $DB->get_records_sql($sql, array('______', $since, $since));
        echo "<li>Going through " . count(array_keys($orgs)) . " orgs</li>";
        flush();
        $services = array();
        if (!empty($googlekey)) $services[] = 'google';
        if (!empty($mapquestkey)) $services[] = 'mapquest';
        $services[] = 'nominatim';
        foreach ($orgs AS $orgid => $org) {
            foreach ($services AS $srvnr => $service) {
                switch ($service) {
                    case 'nominatim':
                        $searchurl = "https://nominatim.openstreetmap.org/search?";
                        $searchurl .= "&street=" . urlencode($org->street);
                        $searchurl .= "&city=" . urlencode($org->city);
                        $searchurl .= "&state=" . urlencode($org->district);
                        $searchurl .= "&country=" . urlencode($org->country);
                        $searchurl .= "&postalcode=" . urlencode($org->zip);
                        $searchurl .= "&addressdetails=0";
                        $searchurl .= "&namedetails=0";
                        $searchurl .= "&format=json";
                        $searchurl .= "&limit=1";
                        $data = do_curl($searchurl);
                        $data = substr($data, 1, strlen($data) - 2);
                        $data = json_decode($data);

                    break;
                    case 'mapquest':
                        $searchurl = "http://open.mapquestapi.com/nominatim/v1/search.php?";
                        $searchurl .= "&key=" . $mapquestkey;
                        $searchurl .= "&format=json";
                        $searchurl .= "&addressdetails=0";
                        $searchurl .= "&limit=1";
                        $searchurl .= "&q=" . urlencode($org->street . ", " . $org->zip . " " . $org->city . ", " . $org->country);
                        $data = do_curl($searchurl);
                        $data = substr($data, 1, strlen($data) - 2);
                        $data = json_decode($data);
                    break;
                    case 'google':
                        $searchurl = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json?";
                        $searchurl .= "&key=" . $googlekey . "&inputtype=textquery";
                        $searchurl .= "&input=" . urlencode($org->street . ", " . $org->zip . " " . $org->city . ", " . $org->country);
                        $searchurl .= "&fields=geometry";
                        $data = do_curl($searchurl);
                        $data = json_decode($data);
                        if (!empty($data->candidates[0]->geometry->location)) {
                            $data = $data->candidates[0]->geometry->location;
                            $data->lon = $data->lng;
                        }
                    break;
                }
                echo "<li>Retrieved data from " . $service . " for <a href=\"" . $searchurl . "\" target=\"_blank\">" . $orgid . "</a></li>\n";

                $testorg = $DB->get_record('local_eduvidual_org_gps', array('orgid' => $orgid));
                if (!empty($data->lat) && !empty($data->lon)) {
                    echo "<li>Retrieved from " . $service . " for " . $orgid . " lon " . $data->lon . " lat " . $data->lat . "</li>";
                    if (!empty($testorg->id)) {
                        $testorg->lat = $data->lat;
                        $testorg->lon = $data->lon;
                        $testorg->modified = time();
                        $DB->update_record('local_eduvidual_org_gps', $testorg);
                        echo "<li>Updated " . $orgid . "</li>";
                    } else {
                        $testorg = (object) array(
                            'orgid' => $orgid,
                            'lat' => $data->lat,
                            'lon' => $data->lon,
                            'modified' => time(),
                            'failed' => 0,
                        );
                        $DB->insert_record('local_eduvidual_org_gps', $testorg);
                        echo "<li>Inserted " . $orgid . "</li>";
                    }
                    // Escape foreach
                    break;
                } else {
                    echo "<li>Failed for " . $orgid . " using " . $service . "</li>";
                    if ($srvnr == count($services)){
                        echo "<li>This was the last service. Flag as failed.</li>";
                        if (!empty($testorg->id)) {
                            $testorg->failed = time();
                            $DB->update_record('local_eduvidual_org_gps', $testorg);
                        } else {
                            $testorg = (object) array(
                                'orgid' => $orgid,
                                'lat' => 0,
                                'lon' => 0,
                                'modified' => 0,
                                'failed' => time(),
                            );
                            $DB->insert_record('local_eduvidual_org_gps', $testorg);
                        }
                    }
                }
                flush();
                if ($service == 'nominatim') {
                    sleep(1);
                }
            }
        }
    //}

}

function do_curl($searchurl) {
    $options = array(
        CURLOPT_RETURNTRANSFER => true,   // return web page
        CURLOPT_HEADER         => false,  // don't return headers
        CURLOPT_FOLLOWLOCATION => true,   // follow redirects
        CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
        CURLOPT_ENCODING       => "",     // handle compressed
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13', //, id-' . time(), // name of client
        CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
        CURLOPT_TIMEOUT        => 120,    // time-out on response
        CURLOPT_URL            => $searchurl,
    );

    $ch = curl_init($searchurl);
    curl_setopt_array($ch, $options);

    $data = make_safe_for_utf8_use(curl_exec($ch));
    curl_close($ch);
    return $data;
}

function make_safe_for_utf8_use($string) {
    $encoding = mb_detect_encoding($string, "UTF-8,ISO-8859-1,WINDOWS-1252");

    if ($encoding != 'UTF-8') {
        return iconv($encoding, 'UTF-8//TRANSLIT', $string);
    }
    else {
        return $string;
    }
}

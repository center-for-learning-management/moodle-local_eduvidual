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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/blocks/eduvidual/pages/login_mnet.php', array());
$PAGE->set_title(get_string('Login', 'block_eduvidual'));
$PAGE->set_heading(get_string('Login', 'block_eduvidual'));
//$PAGE->set_cacheable(false);
$PAGE->requires->css('/blocks/eduvidual/style/login_mnet.css');

if (!empty($SESSION->wantsurl)) $wantsurl = str_replace($CFG->wwwroot, "", $SESSION->wantsurl);
if (empty($wantsurl)) $wantsurl = '/my';

block_eduvidual::print_app_header();

$context = context_system::instance();
$fs = get_file_storage();
$hosts = array();

$orgs = $DB->get_records_sql('SELECT * FROM {block_eduvidual_org} WHERE mnetid > 1 ORDER BY name ASC', array());
foreach ($orgs AS $org) {
    $mnets = $DB->get_records_sql('SELECT mh.name, mh.id, mh.wwwroot, ma.sso_jump_url FROM {mnet_host} AS mh, {mnet_application} AS ma WHERE mh.applicationid = ma.id AND mh.id = ?', array($org->mnetid));
    if ($org->orgid == 601457) {
        $mnets2 = $DB->get_records_sql('SELECT mh.name, mh.id, mh.wwwroot, ma.sso_jump_url FROM {mnet_host} AS mh, {mnet_application} AS ma WHERE mh.applicationid = ma.id AND mh.id = ?', array(3));
        $mnets = array_merge($mnets, $mnets2);
    }
    foreach($mnets AS $mnet) {
        $host = (object) array('name' => $org->name);
        // The str_replace can be removed after the 1st of may 2019
        $params = array('hostwwwroot' => str_replace('https://www.eduvidual.org', 'https://www.eduvidual.at', $CFG->wwwroot), 'wantsurl' => $wantsurl, 'remoteurl' => 1);
        $host->loginurl = '' . new moodle_url($mnet->wwwroot . $mnet->sso_jump_url, $params);

        $files = $fs->get_area_files($context->id, 'block_eduvidual', 'mnetlogo', $org->orgid);
        foreach ($files as $file) {
            if (str_replace('.', '', $file->get_filename()) != ""){
                $host->logo = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
            }
        }
        if ($org->orgid == 601457 && $mnet->id == 3) {
            $host->logo = $CFG->wwwroot . '/pluginfile.php/1/block_eduvidual/globalfiles/0/_sys/bulme-tweak/bulme.png';
            $host->name .= ' via edunet-dl';
        }
        $hosts[] = $host;
    }
}

echo $OUTPUT->render_from_template(
    'block_eduvidual/login_mnet',
    (object) array(
        'hosts' => $hosts,
    )
);
block_eduvidual::print_app_footer();

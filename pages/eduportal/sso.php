<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

require_once __DIR__ . '/../../../../config.php';

$url = required_param('url', PARAM_URL);

// check url
if (!$url || str_contains($url, ':') || str_starts_with($url, '//')) {
    // this is a url outside of moodle!
    throw new moodle_exception('invalidurl');
} else {
    $url = new moodle_url($url);
}

if (isloggedin() && !isguestuser()) {
    redirect($url);
    exit;
}

$SESSION->wantsurl = $url->out(false);

$idps = explode("\n", get_config('auth_shibboleth', 'organization_selection'));
$idpX = explode(",", $idps[0]);
$idp = trim($idpX[0]);

$url = $CFG->wwwroot . '/auth/shibboleth_link/login.php?idp=' . rawurlencode($idp);
redirect($url);

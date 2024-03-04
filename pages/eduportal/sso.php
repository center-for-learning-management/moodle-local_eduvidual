<?php

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

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

defined('MOODLE_INTERNAL') || die;

global $CFG, $DB, $PAGE, $SESSION, $SITE, $USER;

if (strpos($_SERVER["SCRIPT_FILENAME"], '/login/index.php') > 0 && ($USER->id <= 1 || isguestuser($USER))) {
    if (get_config('local_eduvidual', 'modifylogin') == 1) {
        $wwwroot = str_replace('www.eduvidual.org', 'www.eduvidual.at', $CFG->wwwroot);

        // Must be included manually as the eduvidual_block is not active on the login page.
        pq('head')->append(pq('<link rel="stylesheet" href="' . $CFG->wwwroot . '/local/eduvidual/style/main.css"></link>'));
        pq('body')->addClass('eduvidual-default-bg');

        // Append the sitename to our header.
        $cardheader = pq('div#page-content div[role="main"]>div.row .card .card-header');
        $cardheader->attr('style', 'color: rgba(27,137,218,1); text-shadow: 2px 2px 4px rgb(100,100,100);');
        $cardheader->append(pq('<br />'));
        $cardheader->append(pq('<span>')->html($SITE->fullname));

        // Grab and create all buttons.
        $buttons = array();
        $b = array(
            'GUEST_FORM' => 0,
            'LOGIN_FORM' => 1,
            'MNET' => 2,
            'MICROSOFT' => 3,
            'GOOGLE' => 4,
            'REGISTER' => 5,
            'REGISTERORG' => 6,
            'HELP' => 7,
            'FORGOTPW' => 8,
            'EDUIDAM' => 9,
            'PORTAL' => 10,
            'IMPRINT' => 11,
        );
        foreach ($b AS $x) {
            $buttons[$x] = false;
        }

        $buttons[$b['FORGOTPW']] = pq('a[href*="/login/forgot_password.php"]');

        // FIRST GRAB ALL FORMS AND BUTTONS
        if (count(pq('.potentialidp a[title="Microsoft"]')) > 0) {
            $buttons[$b['MICROSOFT']] = pq('<div class="btn-eduviduallogin">')->addClass('potentialidp')->append(
                pq('.potentialidp a[title="Microsoft"]')->clone()->attr('id', 'eduvidual-btn-sso-microsoft')
            );
            //$href = pq($buttons[$b['MICROSOFT']])->find('a')->attr('href');
            $href = $wwwroot . '/local/eduvidual/pages/login_microsoft.php';
            pq($buttons[$b['MICROSOFT']])->find('a')->attr('href', $href);
            //pq($buttons[$b['MICROSOFT']])->find('a')->attr('href', $wwwroot . '/local/eduvidual/pages/login_microsoft.php');
        }

        if (count(pq('.potentialidp a[title="Google"]')) > 0) {
            $buttons[$b['GOOGLE']] = pq('<div class="btn-eduviduallogin">')->addClass('potentialidp')->append(
                pq('.potentialidp a[title="Google"]')->clone()->attr('id', 'eduvidual-btn-sso-google')
            );
            //$href = pq($buttons[$b['GOOGLE']])->find('a')->attr('href');
            $href = $wwwroot . '/local/eduvidual/pages/login_google.php';
            pq($buttons[$b['GOOGLE']])->find('a')->attr('href', $href);
            //pq($buttons[$b['GOOGLE']])->find('a')->attr('href', $wwwroot . '/local/eduvidual/pages/login_google.php');
        }

        if (count(pq('.potentialidp a[href*="/auth/mnet/"]')) > 0) {
            parse_str(parse_url(pq('.potentialidp a[href*="/auth/mnet/"]:nth-of-type(1)')->attr('href'), PHP_URL_QUERY), $params);
            //pq('.potentialidp a[href*="/auth/mnet/"]')->remove();
            $mnetbtn = pq('<a>')
                ->attr('class', 'btn btn-secondary btn-block')
                ->attr('id', 'eduvidual-btn-sso-verbund')
                ->attr('href', $wwwroot . '/local/eduvidual/pages/login_mnet.php')->addClass('btn');
            $img = pq('<img>')->attr('src', '/pix/i/mnethost.svg')->attr('alt', '')->attr('width', '24')->attr('height', '24');
            $span = pq('<span>')->html(' ' . get_string('login:network_btn', 'local_eduvidual'));
            pq($mnetbtn)->append($img)->append($span);
            $buttons[$b['MNET']] = pq('<div class="btn-eduviduallogin">')->addClass('potentialidp')->append($mnetbtn);
        }
        if ($CFG->wwwroot == 'https://www.eduvidual.org') {
            $buttons[$b['LOGIN_FORM']] = pq('<div>Nutzen Sie die Anmeldung unter <a href="https://www.eduvidual.at">eduvidual.at</a>!</div>');
            $buttons[$b['FORGOTPW']] = '';
        } else {
            $buttons[$b['GUEST_FORM']] = pq('<div class="btn-eduviduallogin">')->append(pq('form#guestlogin')->clone());
            //pq($buttons[$b['GUEST_FORM']])->find('form#guestlogin')->attr('action', $wwwroot . '/login/index.php');
            // Using the former form-action always redirected guests to login instead of the dashboard.
            pq($buttons[$b['GUEST_FORM']])->find('form#guestlogin')->attr('action', $wwwroot . '/my');
            $buttons[$b['LOGIN_FORM']] = pq('<div>')->append(pq('form#login')->clone());
            pq($buttons[$b['LOGIN_FORM']])->find('form#login')->attr('action', $wwwroot . '/login/index.php');
        }

        $buttons[$b['REGISTER']] = pq('<div class="btn-eduviduallogin">')
            ->append(pq('<a>')->attr('href', $wwwroot . '/local/eduvidual/pages/check_js.php?forwardto=' . urlencode('/login/verify_age_location.php'))
            //->append(pq('<a>')->attr('href', $wwwroot . '/login/verify_age_location.php')
                    ->addClass('btn btn-secondary btn-block')
                    ->append(pq('<span>')->html(get_string('register:individual', 'local_eduvidual')))
            );
        $buttons[$b['REGISTERORG']] = pq('<div class="btn-eduviduallogin">')
            ->append(pq('<a>')->attr('href', $wwwroot . '/local/eduvidual/pages/register.php')
                    ->addClass('btn btn-secondary btn-block')
                    ->append(pq('<span>')->html(get_string('register:org', 'local_eduvidual'))->attr('style', 'height: 24px;'))
            );
        $buttons[$b['HELP']] = pq('<div class="btn-eduviduallogin">')
            ->append(pq('<a>')->attr('href', $wwwroot . get_config('block_edusupport', 'relativeurlsupportarea'))
                     ->addClass('btn btn-secondary btn-block')
                     ->append(pq('<span>')->html(get_string('goto_tutorials', 'block_edusupport'))->attr('style', 'height: 24px;'))
            );

        $enableidam = optional_param('enableidam', 0, PARAM_INT);
        $hidden = $enableidam ? '' : 'hidden';
        $buttons[$b['EDUIDAM']] = pq('<div class="btn-eduviduallogin ' . $hidden . '">')
            ->append(pq('<a>')->attr('href', $wwwroot . '/auth/shibboleth_link/login.php?idp=' . urlencode('https://www.eduidam.at/idp_metadata.xml'))
                    ->addClass('btn btn-secondary btn-block')
                    ->append(pq('<img>')->attr('src', $CFG->wwwroot . '/local/eduvidual/pix/logo_eduidam.png')->attr('height', '24')->attr('alt', 'eduIDAM'))
                    ->append(pq('<span>')->html('&nbsp;edu.IDAM')->attr('style', 'height: 24px;'))
            );
        $buttons[$b['PORTAL']] = pq('<div class="btn-eduviduallogin ' . $hidden . '">')
            ->append(pq('<a>')->attr('href', $wwwroot . '/auth/shibboleth_link/login.php?idp=' . urlencode('https://federation.portal.at/idp_metadata.xml'))
                    ->addClass('btn btn-secondary btn-block')
                    ->append(pq('<img>')->attr('src', $CFG->wwwroot . '/local/eduvidual/pix/logo_portalat.png')->attr('height', '24')->attr('alt', 'eduIDAM'))
                    ->append(pq('<span>')->html('&nbsp;Portal.at')->attr('style', 'height: 24px;'))
            );
        $buttons[$b['IMPRINT']] = pq('<div class="btn-eduviduallogin">')
            ->append(pq('<a>')->attr('href', $wwwroot . '/static/imprint.html')
                    ->addClass('btn btn-secondary btn-block')
                    ->append(pq('<span>')->html('Impressum')->attr('style', 'height: 24px;'))
            );

        // Remove elements from role="main" that are no row.
        pq('div#page-content div[role="main"]>*:not(.row)')->remove();
        pq('div#page-content div[role="main"]>div.row:nth-child(1) .card .card-body')->append(pq('<div class="fullwidth row justify-content-md-center">'));

        $containerleft  = pq('div#page-content div[role="main"]>div.row:nth-child(1) .card .card-body div.row>div:nth-child(1)')->empty();
        $containerright = pq('div#page-content div[role="main"]>div.row:nth-child(1) .card .card-body div.row>div:nth-child(2)')->empty();
        $containerbelow = pq('div#page-content div[role="main"]>div.row:nth-child(2) .card-body')->empty();

        if ($enableidam) {
            $containerleft->append($buttons[$b['EDUIDAM']]);
            $containerleft->append($buttons[$b['PORTAL']]);
            if (!empty($buttons[$b['MICROSOFT']])) { $containerleft->append($buttons[$b['MICROSOFT']]); }
            if (!empty($buttons[$b['GOOGLE']])) { $containerleft->append($buttons[$b['GOOGLE']]); }
            $containerleft->append($buttons[$b['MNET']]);

            $containerright->append($buttons[$b['REGISTER']]);
            $containerright->append($buttons[$b['REGISTERORG']]);
            $containerright->append($buttons[$b['HELP']]);
            $containerright->append($buttons[$b['GUEST_FORM']]);
            $containerright->append($buttons[$b['IMPRINT']]);
        } else {
            $containerleft->append($buttons[$b['EDUIDAM']]);
            $containerleft->append($buttons[$b['PORTAL']]);
            if (!empty($buttons[$b['MICROSOFT']])) { $containerleft->append($buttons[$b['MICROSOFT']]); }
            if (!empty($buttons[$b['GOOGLE']])) { $containerleft->append($buttons[$b['GOOGLE']]); }
            $containerleft->append($buttons[$b['MNET']]);
            $containerleft->append($buttons[$b['GUEST_FORM']]);

            $containerright->append($buttons[$b['REGISTER']]);
            $containerright->append($buttons[$b['REGISTERORG']]);
            $containerright->append($buttons[$b['HELP']]);
            $containerright->append($buttons[$b['IMPRINT']]);
        }


        $containerbelow->append($buttons[$b['LOGIN_FORM']]);
        $containerbelow->append($buttons[$b['FORGOTPW']]);

        // Add same style to all buttons
        pq('.btn-eduviduallogin')->attr('style', 'margin-bottom: 3px; padding-bottom: 3px; border-bottom: 1px dashed darkgray;');
        pq('.btn-eduviduallogin a, .btn-eduviduallogin button')->attr('style', 'line-height: 29px; height: 41px;');
    }
}

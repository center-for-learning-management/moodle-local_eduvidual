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
 * @copyright  2020 Center for Learning Management (www.lernmanagement.at)
 * @author     Marianne Täubl (HTML+CSS), Robert Schrenk (PHP)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

$errorcode = optional_param('errorcode', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_url('/local/eduvidual/pages/login.php', array());
$PAGE->set_title(get_string('login'));
$PAGE->set_heading(get_string('login'));

$idps = explode("\n", get_config('auth_shibboleth', 'organization_selection'));
if (count($idps) > 0) {
    $idpX = explode(",", $idps[0]);
    $idp = rawurlencode(trim($idpX[0]));
} else {
    $idp = rawurlencode("http://digitaleschuleprod.onmicrosoft.com/B2C_1A_signin_saml");
}

$isproductionsite = ($CFG->wwwroot == 'https://www.eduvidual.at');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php if (!$isproductionsite) { ?>
        <meta name="robots" content="noindex" />
    <?php } ?>

    <title>Moodle Startseite: Hier können Sie sich anmelden</title>
    <link rel="stylesheet" href="<?php echo $CFG->wwwroot; ?>/local/eduvidual/style/login.css">
    <!-- BOOTSTRAP -->
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- Popper JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <script type="text/javascript"> window.sessionStorage.clear(); </script>
</head>

<body spellcheck="false">
    <div id="page-login-index">
    <div id="page" class="container ">
        <header class="container justify-content-center">
            <img src="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pix/icon.svg" alt="eduvidual Logo">
            <h1>eduvidual - <?php echo get_string('your_learning_environment', 'local_eduvidual'); ?></h1>
        </header>

        <?php if (!$isproductionsite) { ?>
            <div class="alert alert-danger">
                <strong>Achtung, Test- und Entwicklungsserver!</strong><br /><br />
                Das hier ist ein Test- und Entwicklungsserver für die Lernplattform eduvidual.at!
                Bitte melden Sie sich hier nur an, wenn Sie wissen, was Sie tun. Die eigentliche
                eduvidual.at-Lernplattform finden Sie unter <a href="https://www.eduvidual.at">www.eduvidual.at</a>!
            </div>
        <?php } ?>

        <main class="page-wrapper justify-content-center">
            <div class="login_buttons">
                <div class="row justify-content-md-center">
                    <div class="col-lg-12 col-sm-12">
                        <a href="<?php echo $CFG->wwwroot; ?>/auth/shibboleth_link/login.php?idp=<?php echo $idp; ?>"
                            title="Bildungsportal" id="eduvidual-btn-sso-bip" data-ajax="false">
                            <button class="btn btn-block" type="button" name="portal">
                                <img src="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pix/logo_bip.svg" width="38" alt="BiP">&nbsp;Bildungportal
                            </button>
                        </a>
                    </div>
                </div>
                <div class="row justify-content-md-center">
                    <div class="col-lg-6 col-sm-12">
                        <a href="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pages/redirects/login_oauth.php?issuer=Microsoft" title="Microsoft" id="eduvidual-btn-sso-microsoft" data-ajax="false">
                            <button class="btn btn-block" type="button" name="microsoft">
                            <img src="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pix/logo_microsoft.svg" width="20" alt="Microsoft">&nbsp;Microsoft
                            </button>
                        </a>
                    </div>
                    <div class="col-lg-6 col-sm-12">
                        <a href="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pages/redirects/login_oauth.php?issuer=Google" title="Google" id="eduvidual-btn-sso-google" data-ajax="false">
                            <button class="btn btn-block" type="button" name="google">
                                <img src="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pix/logo_google.svg" width="20" alt="Google">&nbsp;Google
                            </button>
                        </a>
                    </div>
                </div>
            </div><!-- Ende Login-Buttons -->

            <div class="row justify-content-center">
                <div class="divider-text">
                    <div class="separator">
                        <?php echo get_string('adverbfor_or', 'user'); ?>
                    </div>
                    <p>
                        <?php echo get_string('login:direct', 'local_eduvidual') ?>
                    </p>
                </div>
            </div>
<?php

if ($errorcode > 0) {
    $errormessage = "";
    require_once($CFG->dirroot . '/lib/authlib.php');
    switch ($errorcode) {
        /** Can not login because user does not exist. */
        case AUTH_LOGIN_NOUSER: // 1
            $errormessage = get_string('nousers', 'error');
        break;
        /** Can not login because user is suspended. */
        case AUTH_LOGIN_SUSPENDED: // 2
            $errormessage = get_string('suspended');
        break;
        /** Can not login, most probably password did not match. */
        case AUTH_LOGIN_FAILED: // 3
            $errormessage = get_string('invalidlogin');
        break;
        /** Can not login because user is locked out. */
        case AUTH_LOGIN_LOCKOUT: // 4
            $errormessage = get_string('sessionexpired', 'error');
        break;
        /** Can not login becauser user is not authorised. */
        case AUTH_LOGIN_UNAUTHORISED: // 5
            $errormessage = get_string("unauthorisedlogin", "", optional_param('username', '', PARAM_TEXT));
        break;
    }
    if (!empty($errormessage)) {
        echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => $errormessage,
            'type' => 'danger'
        ));
    }
}


?>
            <form action="<?php echo $CFG->wwwroot; ?>/login/index.php" method="post" id="login">
                <div class="form-row">
                    <input type="text" class="form-control" name="username" id="username" placeholder="<?php echo get_string('username') . ' / ' . get_string('email'); ?>">
                </div>
                <div class="form-row">
                    <input type="password" class="form-control" name="password" id="password" placeholder="<?php echo get_string('password'); ?>">
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberusername" name="rememberusername">
                        <label class="form-check-label" for="gridCheck">
                            <?php echo get_string('rememberusername', 'admin'); ?>
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <input type="submit" class="login-button btn btn-block btn-primary" id="loginbtn" value="<?php echo get_string('login'); ?>">
                </div>
            </form>

            <p class="pass justify-content-end">
                <a href="<?php echo $CFG->wwwroot; ?>/login/forgot_password.php">
                    <?php echo get_string('forgotten'); ?>
                </a>
            </p>


            <div class="btn-lower row justify-content-md-center">
                <div class="col-lg-6 col-sm-12">
                    <a href="<?php echo $CFG->wwwroot; ?>/login/verify_age_location.php" title="reg-einzelperson" data-ajax="false">
                        <button class="btn-block btn" type="button" name="Als Einzelperson registrieren" id="reg-einzelperson">
                            <?php echo get_string('register:individual', 'local_eduvidual'); ?>
                        </button>
                    </a>
                </div>
                <div class="col-lg-6 col-sm-12">
                    <form action="<?php echo $CFG->wwwroot; ?>/login/index.php" method="post" id="guestlogin">
                        <input type="hidden" name="logintoken" value="WBOYNquPhvP8c2NXtWKVztYVQ73yXVXH">
                        <input type="hidden" name="username" value="guest">
                        <input type="hidden" name="password" value="guest">
                        <a href="#" title="gast" id="eduvidual-btn-sso-gast" data-ajax="false">
                            <button class="btn-block btn" type="submit" name="gast" id="gast">
                                <?php echo get_string('loginguest'); ?>
                            </button>
                        </a>
                    </form>
                </div>
            </div>
            <div class="logos" style="margin-bottom: 15px;">
                <hr />
                <div class="row justify-content-md-center" style="justify-content: space-around !important;">
                    <div class="col-lg-4 col-sm-4" style="text-align: right;">
                        <a href="https://www.bmbwf.gv.at" target="_blank" data-ajax="false"
                            title="Bundesministerium für Bildung, Wissenschaft und Forschung">
                            <img src="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pix/logo_bmbwf.png"
                                height="60" alt="Bundesministerium für Bildung, Wissenschaft und Forschung">
                        </a>
                    </div>
                    <div class="col-lg-2 col-sm-2" style="text-align: center;">
                        <a href="https://www.ph-ooe.at" target="_blank" data-ajax="false"
                            title="Pädagogische Hochschule Oberösterreich">
                            <img src="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pix/logo_phooe.png"
                                height="60" alt="Pädagogische Hochschule Oberösterreich">
                        </a>
                    </div>
                    <div class="col-lg-4 col-sm-4" style="text-align: left;">
                        <a href="https://www.lernmanagement.at" target="_blank" data-ajax="false"
                            title="Zentrum für Lernmanagement">
                            <img src="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pix/logo_zlm.svg"
                                height="60" alt="Zentrum für Lernmanagement">
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <footer>
        <div class="container-fluid footer-menu">
            <div class="container">
                <ul class="nav justify-content-center">
                    <li class="nav-item">
                        <a href="https://www.eduvidual.at/course/view.php?id=606&section=3" class="nav-link">
                            <?php echo get_string('help_and_tutorials', 'local_eduvidual'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="https://www.eduvidual.at/static/imprint.html" class="nav-link">
                            <?php echo get_string('imprint', 'local_eduvidual'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $CFG->wwwroot; ?>/" class="nav-link">
                            <?php echo get_string('privacy', 'local_eduvidual'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </footer>
        </div>
</body>
</html>

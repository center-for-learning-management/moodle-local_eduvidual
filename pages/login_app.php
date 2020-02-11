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
 * THIS FILE WAS PREVIOUSLY USED BY THE EDUVIDUAL-APP TO LOGIN AS "login.php".
 * NOW WE HAVE THE DEDICATED PLUGIN local_eduvidualapp FOR THIS.
 * WE EXPECT THIS FILE IS OBSOLETE, BUT IT IS KEPT UNTIL WE ARE SURE.
 */

/**
 * @package    block_eduvidual
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('embedded');
$PAGE->set_url('/blocks/eduvidual/pages/login_app.php');
$PAGE->set_title(get_string('Login', 'block_eduvidual'));
$PAGE->set_heading(get_string('Login', 'block_eduvidual'));
//$PAGE->set_cacheable(false);
$PAGE->requires->css('/blocks/eduvidual/style/login_mnet.css');

$wantsurl = optional_param('wantsurl', '', PARAM_TEXT);
if ($wantsurl != '') {
    $_SESSION['wantsurl'] = $wantsurl;
}
$dologout = optional_param('dologout', 0, PARAM_INT);
$redirect = optional_param('redirect', 0, PARAM_INT);
$logindirect = optional_param('logindirect', 0, PARAM_INT);

$username = optional_param('username', '', PARAM_TEXT);
if (!empty($username)) {
    // Attempt login
    require_once($CFG->dirroot . '/login/lib.php');
    $username = trim(core_text::strtolower($username));
    $password = optional_param('password', '', PARAM_TEXT);
    $user = authenticate_user_login($username, $password, false, $errorcode);
    if ($user) {
        // language setup
        if (isguestuser($user)) {
            // no predefined language for guests - use existing session or default site lang
            unset($user->lang);
        } else if (!empty($user->lang)) {
            // unset previous session language - use user preference instead
            unset($SESSION->lang);
        }
        if (empty($user->confirmed)) {       // This account was never confirmed
            block_eduvidual::print_app_header();
            echo $OUTPUT->heading(get_string("mustconfirm"));
            if ($resendconfirmemail) {
                if (!send_confirmation_email($user)) {
                    echo $OUTPUT->notification(get_string('emailconfirmsentfailure'), \core\output\notification::NOTIFY_ERROR);
                } else {
                    echo $OUTPUT->notification(get_string('emailconfirmsentsuccess'), \core\output\notification::NOTIFY_SUCCESS);
                }
            }
            echo $OUTPUT->box(get_string("emailconfirmsent", "", $user->email), "generalbox boxaligncenter");
            $resendconfirmurl = new moodle_url('/login/index.php',
                [
                    'username' => $frm->username,
                    'password' => $frm->password,
                    'resendconfirmemail' => true
                ]
            );
            echo $OUTPUT->single_button($resendconfirmurl, get_string('emailconfirmationresend'));
            ?><div class"card"><a href="#" onclick="require(['block_eduvidual/main'], function(MAIN) { MAIN.doLogout(); });" data-role="button" data-icon="sign-out"><?php echo get_string('logout'); ?></a></div><?php
            block_eduvidual::print_app_footer();
            die;
        }
        /// Let's get them all set up.
        complete_user_login($user);
        $wantsurl = rawurldecode(optional_param('wantsurl', '', PARAM_TEXT));
        redirect($wantsurl);
    } else {
        $LOGIN_FAILED = true;
    }
}

if ($dologout == 1) {
    $url = $CFG->wwwroot;

    require_logout();
    ?>
    <html>
        <body>
            <script>
                //localStorage.removeItem('block_eduvidual_originallocation');
                var url = "<?php echo $url; ?>";
                if (url.indexOf('file://') == 0) {
                    console.log('Navigator is ', navigator);
                    if (typeof navigator.app !== 'undefined') {
                        navigator.app.loadUrl(url);
                    } else {
                        alert('Navigator does not exist - quitting app instead');
                        cordova.plugin.exit();
                    }
                } else {
                    top.location.href = url;
                }

            </script>
        </body>
    </html>
    <?php
    die();
}

if ($redirect == 1) {
    redirect($CFG->wwwroot . '/blocks/eduvidual/pages/app.php');
}

block_eduvidual::print_app_header();

if ($USER->id == 0) {
    $usetoken = md5(date('Y-m-d H:i:s') . rand(pow(9,3), pow(9, 5)));

    if ($logindirect == 1) {
        /*
        // wantsurl is now attached to the form as hidden field
        $SESSION->wantsurl = $PAGE->url . '&redirect=1';
        */
        if (optional_param('fail', '', PARAM_TEXT) == 'wrong_credentials') {
            echo $OUTPUT->render_from_template('block_eduvidual/alert',
                (object) array(
                    'type' => 'error',
                    'content' => get_string('app:login_wrong_credentials', 'block_eduvidual'),
                )
            );
        }
        ?>
            <div data-role="fieldset" class="card">
                <h3><?php echo get_string('login:internal', 'block_eduvidual'); ?></h3>
                <form id="block_eduvidual_login_form" action="<?php echo $CFG->wwwroot; ?>/blocks/eduvidual/pages/app.php" method="POST">
                    <input type="hidden" name="usetoken" value="<?php echo $usetoken; ?>" /><br />
                    <input type="text" name="username" id="username" placeholder="<?php echo get_string('username'); ?>" /><br />
                    <input type="password" name="password" id="password" placeholder="<?php echo get_string('password'); ?>" /><br />
                    <a href="#" class="btn" data-role="button" data-icon="sign-in" onclick="document.getElementById('block_eduvidual_login_form').submit();"><?php echo get_string('login'); ?></a>
                    <a href="<?php echo $PAGE->url; ?>" class="btn" data-role="button" data-icon="back"><?php echo get_string('back'); ?></a>
                </form>
            </div>
        <?php
    } else {
        block_eduvidual::add_script_on_load('if(localStorage.getItem("block_eduvidual_mnetlogin") == "1") { localStorage.removeItem("block_eduvidual_mnetlogin"); require(["block_eduvidual/main"], function(MAIN) { top.location.href = MAIN.autoLoginUrl("' . $CFG->wwwroot . '/blocks/eduvidual/pages/courses.php", 1); }); };');
    ?>
    <div class="card">
        <div data-role="fieldset">
            <h3><?php echo get_string('login:internal', 'block_eduvidual'); ?></h3>
            <!-- <a href="<?php echo $CFG->wwwroot; ?>/blocks/eduvidual/pages/login.php?dologout=1" data-role="button" data-icon="sign-in"><?php echo get_string('app:back_to_app', 'block_eduvidual'); ?></a> -->
            <a href="<?php echo $PAGE->url; ?>&logindirect=1" data-role="button" data-icon="sign-in" class="btn ui-btn"><?php echo get_string('login:internal', 'block_eduvidual'); ?></a>
        </div>

        <?php
            // MNET Logins
            $context = context_system::instance();
            $fs = get_file_storage();
            // Taken from /auth/mnet/auth.php
            // Unfortunately we can not use loginpage_idp_list because we would lose
            // information about the mnet-id
            $sql = "SELECT DISTINCT h.id, h.wwwroot, h.name, a.sso_jump_url, a.name as application
                  FROM {mnet_host} h
                  JOIN {mnet_host2service} m ON h.id = m.hostid
                  JOIN {mnet_service} s ON s.id = m.serviceid
                  JOIN {mnet_application} a ON h.applicationid = a.id
                 WHERE s.name = ? AND h.deleted = ? AND m.publish = ?";
            $params = array('sso_sp', 0, 1);
            if (!empty($CFG->mnet_all_hosts_id)) {
                $sql .= " AND h.id <> ?";
                $params[] = $CFG->mnet_all_hosts_id;
            }
            $hosts = array(
                (object) array(
                    'name' => 'Microsoft',
                    'logo' => 'https://www.eduvidual.org/pluginfile.php/1/block_eduvidual/globalfiles/0/_sys/oauth2/microsoft_logo.png',
                    'loginurl' => $CFG->wwwroot . '/blocks/eduvidual/pages/app.php?launchurl=' . base64_encode('microsoft') . '&usetoken=' . $usetoken,
                ),
                (object) array(
                    'name' => 'Google',
                    'logo' => 'https://www.eduvidual.org/pluginfile.php/1/block_eduvidual/globalfiles/0/_sys/oauth2/google_logo.png',
                    'loginurl' => $CFG->wwwroot . '/blocks/eduvidual/pages/app.php?launchurl=' . base64_encode('google') . '&usetoken=' . $usetoken,
                )
            );

            $orgs = $DB->get_records_sql('SELECT * FROM {block_eduvidual_org} WHERE mnetid > 1', array());
            foreach ($orgs AS $org) {
                $mnets = $DB->get_records_sql('SELECT mh.name, mh.id, mh.wwwroot, ma.sso_jump_url FROM {mnet_host} AS mh, {mnet_application} AS ma WHERE mh.applicationid = ma.id AND mh.id = ?', array($org->mnetid));
                if ($org->orgid == 601457) {
                    $mnets2 = $DB->get_records_sql('SELECT mh.name, mh.id, mh.wwwroot, ma.sso_jump_url FROM {mnet_host} AS mh, {mnet_application} AS ma WHERE mh.applicationid = ma.id AND mh.id = ?', array(3));
                    $mnets = array_merge($mnets, $mnets2);
                }
                foreach($mnets AS $mnet) {
                    $host = (object) array('name' => $org->name);
                    $host->loginurl = $CFG->wwwroot . '/blocks/eduvidual/pages/app.php?launchurl=' . base64_encode($mnet->wwwroot . $mnet->sso_jump_url) . '&usetoken=' . $usetoken . '&remoteurl=1';
                    //$params = array('hostwwwroot' => $CFG->wwwroot, 'wantsurl' => $scheme, 'remoteurl' => 1);
                    //$host->loginurl = '' . new moodle_url($mnet->wwwroot . $mnet->sso_jump_url, $params);

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
        ?>
        <!--
        <div data-role="fieldset">
            <h3><?php echo get_string('login:default', 'block_eduvidual'); ?></h3>
            <a href="<?php echo $CFG->wwwroot . $wantsurl; ?>" data-role="button" data-icon="sign-in"><?php echo get_string('login:default', 'block_eduvidual'); ?></a>
        </div>
        -->
    </div>
    <?php
    }
} else {
    ?>
    <div class="card">
        <a href="#" onclick="require(['block_eduvidual/main'], function(MAIN) { MAIN.doLogout(); });" data-role="button" data-icon="sign-out" class="btn ui-btn"><?php echo get_string('logout'); ?></a>
    </div>
    <?php
}

block_eduvidual::print_app_footer();

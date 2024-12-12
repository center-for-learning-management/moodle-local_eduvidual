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
 * @copyright  2019 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual;

defined('MOODLE_INTERNAL') || die;

class hook_callbacks {
    public static function after_config() {
        global $CFG, $DB, $PAGE, $USER;

        \local_eduvidual\locallib::set_xorg_data();

        $PAGE->add_body_class('theme-' . $CFG->theme);
        // Check for particular scripts, whose output has to be protected.
        $scripts = array(
            '/enrol/manual/manage.php', '/mod/jazzquiz/edit.php', '/mod/activequiz/edit.php',
            '/question/category.php', '/question/edit.php',
            '/question/export.php', '/user/selector/search.php',
        );
        $script = str_replace($CFG->dirroot, '', $_SERVER["SCRIPT_FILENAME"]);
        if (in_array($script, $scripts)) {
            \local_eduvidual\lib_wshelper::buffer();
        }

        // data privacy request for deletion only allowed when not member in any org.
        if (strpos($_SERVER["SCRIPT_FILENAME"], '/admin/tool/dataprivacy/createdatarequest.php') > 0) {
            $type = optional_param('type', 0, PARAM_INT);
            if ($type == 2) {
                $orgs = \local_eduvidual\locallib::get_organisations('*', false);
                if (count($orgs) > 0) {
                    $url = new \moodle_url('/local/eduvidual/pages/redirects/dataprivacyorgerror.php', array());
                    redirect($url);
                }
            }
        }

        // Protect core question bank from being exported.
        if (!is_siteadmin() && strpos($_SERVER["SCRIPT_FILENAME"], '/question/export.php') > 0) {
            $category = optional_param('category', 0, PARAM_RAW);
            if (!empty($category)) {
                $category = explode(',', $category);
                $ctx = \context_system::instance();
                if (count($category) > 1 && $category[1] == $ctx->id) {
                    $_POST['category'] = '';
                    $_GET['category'] = '';
                }
            }
        }

        if (strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/view.php') > 0
            || strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/guestlink.php') > 0
            || strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/bbb_ajax.php') > 0
            || strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/bbb_view.php') > 0
            || strpos($_SERVER["SCRIPT_FILENAME"], '/webservice/rest/server.php') > 0 && optional_param('component', '', PARAM_TEXT) == 'mod_bigbluebuttonbn') {
            if (strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/guestlink.php') > 0) {
                // get cmid dependent on guestlinkid (gid)
                $gid = optional_param('gid', '', PARAM_ALPHANUM);
                $bbb = $DB->get_record('bigbluebuttonbn', array('guestlinkid' => $gid));
                if ($bbb->guestlinkenabled) {
                    list($course, $cm) = get_course_and_cm_from_instance($bbb, 'bigbluebuttonbn');
                    if (!empty($cm->id)) {
                        $cmid = $cm->id;
                    }
                }
            } elseif (strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/bbb_ajax.php') > 0) {
                $bbbtn = optional_param('bigbluebuttonbn', 0, PARAM_INT);
                $bbb = $DB->get_record('bigbluebuttonbn', array('id' => $bbbtn));
                list($course, $cm) = get_course_and_cm_from_instance($bbb, 'bigbluebuttonbn');
                if (!empty($cm->id)) {
                    $cmid = $cm->id;
                }
            } elseif (strpos($_SERVER["SCRIPT_FILENAME"], '/webservice/rest/server.php') > 0) {
                $params = array_merge($_GET, $_POST);
                $args = $params['args'];
                if (!empty($args)) {
                    foreach ($args as $i => $arg) {
                        if (!empty($arg['name']) && $arg['name'] == 'cmid') {
                            $cmid = $arg['value'];
                        }
                    }
                }
            } else {
                $cmid = optional_param('id', 0, PARAM_INT);
            }
            if (!empty($cmid)) {
                $cm = get_coursemodule_from_id('bigbluebuttonbn', $cmid, 0, false, IGNORE_MISSING);
                if (!empty($cm->course)) {
                    $course = $DB->get_record('course', array('id' => $cm->course), '*', IGNORE_MISSING);
                    if (!empty($course->id)) {
                        $org = \local_eduvidual\locallib::get_org_by_courseid($course->id);
                        if (!empty($org->orgid)) {
                            $bbb_serverurl = $DB->get_record('local_eduvidual_overrides', array('orgid' => $org->orgid, 'field' => 'bigbluebuttonbn_server_url'));
                            $bbb_sharedsecret = $DB->get_record('local_eduvidual_overrides', array('orgid' => $org->orgid, 'field' => 'bigbluebuttonbn_shared_secret'));
                            if (!empty($bbb_serverurl->value) && !empty($bbb_sharedsecret->value)) {
                                $CFG->bigbluebuttonbn['server_url'] = $bbb_serverurl->value;
                                $CFG->{'bigbluebuttonbn_server_url'} = $bbb_serverurl->value;
                                $CFG->bigbluebuttonbn['shared_secret'] = $bbb_sharedsecret->value;
                                $CFG->{'bigbluebuttonbn_shared_secret'} = $bbb_sharedsecret->value;
                            }
                        }
                    }
                }
            }
        }
    }

    public static function before_standard_head_html_generation(\core\hook\output\before_standard_head_html_generation $hook): void {
        global $CFG, $CONTEXT, $COURSE, $DB, $OUTPUT, $PAGE, $USER;

        $RET = [];

        // Protect question banks on course level.
        if (!empty($PAGE->context->contextlevel) && $PAGE->context->contextlevel == CONTEXT_COURSE) {
            if (strpos($_SERVER["SCRIPT_FILENAME"], '/question/edit.php') > 0
                || strpos($_SERVER["SCRIPT_FILENAME"], '/question/category.php') > 0
                || strpos($_SERVER["SCRIPT_FILENAME"], '/question/import.php') > 0
                || strpos($_SERVER["SCRIPT_FILENAME"], '/question/export.php') > 0) {
                if (!\local_eduvidual\locallib::can_access_course_questionbank($PAGE->context)) {
                    throw new \required_capability_exception($PAGE->context, 'moodle/question:viewall', get_string('access_denied', 'local_eduvidual'), '');
                }
            }
        }

        $orgmenu = \local_eduvidual\lib_helper::render_orgmenu();

        $data = [
            'isloggedin' => isloggedin() && !isguestuser(),
            'orgmenu' => $orgmenu,
        ];

        // fastest way is to call js is with js_init_code and js_init_call
        // js_call_amd ist zeitverzögert!
        // call eduvidual_init in direct.js
        $PAGE->requires->js('/local/eduvidual/js/direct.js');
        $PAGE->requires->js_init_code('eduvidual_init(' . json_encode($data) . ');');

        $PAGE->requires->js('/local/eduvidual/js/ajax_observer.js');

        // Main styles for eduvidual.
        if (!\local_eduvidual\locallib::is_moodle_4()) {
            $PAGE->requires->css('/local/eduvidual/style/main.css');
            $PAGE->requires->css('/local/eduvidual/style/spinner.css');
            //$PAGE->requires->css('/local/eduvidual/style/ui.css');
            // General boost-modifications.
            $PAGE->requires->css('/local/eduvidual/style/theme_boost.css');

            // Wenn das neue edumaker theme (August 2022) aktiv ist, sollen die Anpassungen für theme boost_campus im theme_39.css nicht geladen werden
            if ($CFG->theme != 'edumaker') {
                $PAGE->requires->css('/local/eduvidual/style/theme_39.css');
            }
        }

        // The default banner needs to be injected via Internal Stylesheet to
        // assure the correct absolute path to the image.
        $RET[] = implode("\n", [
            '<style>',
            '    body.theme-boost #page-header .card, body.theme-boost_campus #page-header .card {',
            '        background-image: url("' . $CFG->wwwroot . '/local/eduvidual/pix/banner-curve.jpg");',
            '    }',
            '</style>',
        ]);

        $org = \local_eduvidual\locallib::get_org_by_context();

        if (strpos($_SERVER["SCRIPT_FILENAME"], '/enrol/otherusers.php') > 0) {
            redirect($CFG->wwwroot . '/user/index.php?id=' . optional_param('id', 0, PARAM_INT));
        }
        if (strpos($_SERVER["SCRIPT_FILENAME"], '/login/signup.php') > 0) {
            echo $OUTPUT->render_from_template('local_eduvidual/inject', ['signupPage' => 1]);
        }
        if (strpos($_SERVER["SCRIPT_FILENAME"], '/course/edit.php') > 0) {
            echo $OUTPUT->render_from_template('local_eduvidual/inject', ['courseEditPage' => 1, 'userid' => $USER->id, 'issiteadmin' => is_siteadmin()]);
        }

        $data = array(
            'context' => $CONTEXT,
            'course' => (object)array(
                'id' => $COURSE->id,
                'contextid' => $PAGE->context->id,
            ),
        );
        $PAGE->requires->js_call_amd("local_eduvidual/jsinjector", "run", array($data));


        if (strpos($_SERVER["SCRIPT_FILENAME"], '/course/delete.php') > 0) {
            $PAGE->requires->js_call_amd("local_eduvidual/jsinjector", "modifyRedirectUrl", array('coursedelete'));
        }

        // Now inject organisation-specific resources.
        $inject_styles = array("<style type=\"text/css\" id=\"local_eduvidual_style_userextra\">");
        $background = get_user_preferences('local_eduvidual_background');
        if (!isguestuser($USER) && !empty($background)) {
            $inject_styles[] = "body { background: url(" . $CFG->wwwroot . $background . ") no-repeat center center fixed; background-size: cover !important; }";
        }
        if (!empty($extra->background)) {
            $inject_styles[] = "body { background-image: url(" . $CFG->wwwroot . $extra->background . "); background-position: center; background-size: cover; }";
        }
        $inject_styles[] = "</style>";

        $inject_styles[] = "<style type=\"text/css\" id=\"local_eduvidual_style_org\">";
        if (!empty($org->customcss)) {
            $inject_styles[] = $org->customcss;
        }
        if (!empty($org->banner)) {
            $inject_styles[] = "body #page-header .card { background-image: url(" . $CFG->wwwroot . $org->banner . ") !important; }";
        }

        $fs = get_file_storage();

        if ($org) {
            $logo = current($fs->get_area_files(1, 'local_eduvidual', 'orglogo', $org->orgid, 'itemid', false));
            if ($logo) {
                $inject_styles[] = "
                .site-name.has-logo img.site-logo {
                    display: none;
                }
                .site-name.has-logo {
                    width: 35px;
                    height: 35px;
                    background-image: url({$CFG->wwwroot}/pluginfile.php/1/local_eduvidual/orglogo/{$org->orgid}/{$logo->get_filename()});
                    background-size: 100% 100%;
                }
            ";
            }
        }

        $inject_styles[] = "</style>";

        // Disabled, needs performance tests.
        // \local_eduvidual\lib_helper::fix_navbar();

        $RET[] = implode("\n", $inject_styles);

        // Favicon & Apple Touch Icons
        $RET[] = '<link rel="icon" href="' . $CFG->wwwroot . '/local/eduvidual/pix/favicons/favicon.ico" type="image/x-icon">';
        $RET[] = '<link rel="apple-touch-icon" href="' . $CFG->wwwroot . '/local/eduvidual/pix/favicons/apple-touch-icon.png">';
        $sizes = ['57x57', '72x72', '76x76', '114x114', '120x120', '144x144', '152x152', '180x180'];
        foreach ($sizes as $size) {
            $RET[] = '<link rel="apple-touch-icon" sizes="' . $size . '" href="' . $CFG->wwwroot . '/local/eduvidual/pix/favicons/apple-touch-icon-57x57.png">';
        }


        $hook->add_html(implode("\n", $RET));
    }
}

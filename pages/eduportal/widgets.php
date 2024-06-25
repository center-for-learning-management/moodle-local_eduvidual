<?php

require_once __DIR__ . '/../../../../config.php';

// if ($_SERVER['PHP_AUTH_PW'] != 'eduvidual') {
//     throw new \moodle_exception('not allowed (reason: password)');
// }

// $orgid = $data->orgid;
// $role = $data->role;

// testing:
// $orgid = 999999;

// if ($widget == 'timeline-test') {
//     $user = $DB->get_record('user', ['username' => 'student']) ?: $DB->get_record('user', ['username' => 'dprieler+lehrer@gmail.com']);
//     static::timetable($user);
// } elseif ($widget == 'course_list-test') {
//     $user = $DB->get_record('user', ['username' => 'student']) ?: $DB->get_record('user', ['username' => 'dprieler+lehrer@gmail.com']);
//     static::course_list($user);
// }

local_eduvidual_eduportal_widget::run();

class local_eduvidual_eduportal_widget {
    static function json_response($data) {
        $response = (object)[
            'data' => $data,
            'expiration' => 1,
        ];

        header('Content-Type: application/json');

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    static function html_response($text, $additionalData = []) {
        static::json_response(array_merge([
            'type' => 'html',
            'content' => $text,
        ], $additionalData));
    }

    static function error_response($error) {
        static::json_response([
            'type' => 'failed',
            'title' => $error,
            'errorCode' => $error,
        ]);
    }

    static function get_post_data(): object {
        static $data;

        if (!$data) {
            $postdata = file_get_contents('php://input');
            $data = json_decode($postdata) ?: (object)[];
        }

        return $data;
    }

    static function get_user(): object {
        global $USER;

        return $USER;
    }

    static function get_related_user() {
        $data = static::get_post_data();

        if (empty($data->relateduser)) {
            static::error_response('relateduser nicht übermittelt');
        }

        return static::get_user_from_bpk($data->relateduser);
    }

    static function get_idp_id() {
        $idps = explode("\n", get_config('auth_shibboleth', 'organization_selection'));
        $idpX = explode(",", $idps[0]);
        return trim($idpX[0]);
    }

    static function get_user_from_bpk($bpk) {
        global $DB;

        if ($bpk == 'BF:') {
            // eduportal bug:
            static::error_response("bpk bug! bpk: '$bpk'");
            // $bpk = 'BF:bpkBFvonMarieCurie';
        }

        if (!$bpk) {
            static::error_response('no bpk given');
        }

        $idp = static::get_idp_id();

        $shibboleth_link = $DB->get_record_select('auth_shibboleth_link',
            'idp LIKE ? AND ' . $DB->sql_like('idpusername', '?'),
            [$idp, $bpk]);

        // global $data;
        // static::html_response('https://fdfdfdsfd ' . $idp . ' ' . $bpk . print_r($data, true));

        if ($shibboleth_link) {
            $user = $DB->get_record('user', array('id' => $shibboleth_link->userid));
        } else {
            $user = null;
        }

        return $user;
    }

    static function require_user(): void {
        global $CFG, $DB;

        $data = static::get_post_data();

        if (!empty($_SERVER['PHP_AUTH_PW'])) {
            if ($_SERVER['PHP_AUTH_PW'] != get_config('local_eduvidual', 'eduportal_widget_password')) {
                header('WWW-Authenticate: Basic realm="Widgets"');
                header('HTTP/1.0 401 Unauthorized');
                static::error_response('Authorization Required #5852dffdvd');
                exit;
            }
        } elseif (isloggedin() && !isguestuser()) {
            // allow widget test
            return;
        } else {
            header('WWW-Authenticate: Basic realm="Widgets"');
            header('HTTP/1.0 401 Unauthorized');
            static::error_response('Authorization Required #5852dffdvd');
            exit;
        }

        // testing:
        if (!empty($CFG->developermode) && $_SERVER['HTTP_HOST'] == 'localhost') {
            $user = $DB->get_record('user', ['username' => 'teacher']);
            $GLOBALS['USER'] = $user;
            return;
        }

        if (empty($data->viewinguser)) {
            static::error_response('no viewinguser given');
        }

        $user = static::get_user_from_bpk($data->viewinguser);

        if (!$user) {
            $idp = static::get_idp_id();

            static::html_response(get_string('widgets:connect_users_text', 'local_eduvidual'), [
                'button' => [
                    // "icon": "fa fa-euro", // all icons of font-awesome free v6.2.1, either fa or image
                    "target" => "_blank",
                    "label" => get_string('widgets:connect_users_button', 'local_eduvidual'),
                    "link" => $CFG->wwwroot . '/auth/shibboleth_link/login.php?idp=' . rawurlencode($idp),
                ],
            ]);
        }

        // set the global user
        $GLOBALS['USER'] = $user;
    }

    static function sso_url(\moodle_url $url) {
        return new \moodle_url('/local/eduvidual/pages/eduportal/sso.php', ['url' => $url->out_as_local_url(false)]);
    }

    static function course_list() {
        global $CFG;

        require_once($CFG->libdir . '/enrollib.php');
        // $GLOBALS['USER'] is set in require_user();
        // use enrol_get_my_courses(), because it allows to pass a timeaccess sort order (recently accesses courses are on top)
        $courses = enrol_get_my_courses(null, 'ul.timeaccess DESC');

        if (!$courses) {
            static::html_response(get_string('widgets:course_list:no_courses', 'local_eduvidual'));
        }

        $responseData = (object)[
            'type' => 'btnlist',
            'items' => [],
            'button' => [
                'label' => get_string('widgets:show_all_entries', 'local_eduvidual'),
                'link' => static::sso_url(new \moodle_url('/my/courses.php'))->out(false),
                // , ['view' => 'month', 'course' => 1]),
            ],
        ];

        // max 30 Kurse
        $courses = array_slice($courses, 0, 30);

        foreach ($courses as $course) {
            $responseData->items[] = [
                'label' => $course->fullname,
                // 'detailleft' => '',
                // 'detailright' => '',
                // 'icon' => 'fa fa-calendar',
                'link' => static::sso_url(new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                // , ['view' => 'month', 'course' => 1]),
            ];
        }

        static::json_response($responseData);
    }

    static function timeline() {
        global $CFG, $USER;

        $user = static::get_related_user();
        if (!$user) {
            static::html_response(get_string('widgets:user_not_connected', 'local_eduvidual'));
        }

        $isMyself = $user->id == $USER->id;

        require_once($CFG->dirroot . '/calendar/lib.php');
        $events = \core_calendar\local\api::get_action_events_by_timesort(
            time(),
            null,
            null,
            12,
            true,
            $user,
            null
        );

        if (!$events) {
            static::html_response(get_string('widgets:timeline:no_entries', 'local_eduvidual'));
        }

        $responseData = (object)[
            'type' => 'detaillist',
            'items' => [],
        ];

        if ($isMyself) {
            // only link, if viewing my own timeline
            $responseData->button = [
                'label' => get_string('widgets:show_all_entries', 'local_eduvidual'),
                'link' => static::sso_url(new \moodle_url('/calendar/view.php'))->out(false),
                // , ['view' => 'month', 'course' => 1]),
            ];
        }

        foreach ($events as $event) {
            /** @var \core_calendar\local\event\entities\action_event $event */

            // kopiert von event_exporter_base.php
            // if ($cm = $event->get_course_module()) {
            //     $data = (object)[];
            //     $data->modulename = $cm->get('modname');
            //     $data->instance = $cm->get('id');
            //     $data->activityname = $cm->get('name');
            //
            //     $component = 'mod_' . $data->modulename;
            //     if (!component_callback_exists($component, 'core_calendar_get_event_action_string')) {
            //         $modulename = get_string('modulename', $data->modulename);
            //         $data->activitystr = get_string('requiresaction', 'calendar', $modulename);
            //     } else {
            //         $data->activitystr = component_callback(
            //             $component,
            //             'core_calendar_get_event_action_string',
            //             [$event->get_type()]
            //         );
            //     }
            // } else {
            //     $data = null;
            // }

            $url = $event->get_action()->get_url() ?: new \moodle_url('/calendar/view.php');

            $item = [
                'label' => $event->get_name(),
                'detailleft' => userdate($event->get_times()->get_start_time()->getTimestamp(), get_string('strftimedatetimeshort', 'core_langconfig')),
                'detailright' => '',
                // ($data ? $data->activitystr . ' · ' : '') .
                // $event->get_course()->get('shortname'),
                'icon' => 'fa fa-calendar',
                // only link, if viewing my own timeline:
                'link' => $isMyself ? static::sso_url($url)->out(false) : null,
                // , ['view' => 'month', 'course' => 1]),
            ];

            $responseData->items[] = $item;
        }

        static::json_response($responseData);
    }

    static function run() {
        global $SESSION;

        $data = static::get_post_data();
        // set language to the one on eduportal
        $SESSION->forcelang = $data->lang ?? 'de';

        $widget = optional_param('widget', '', PARAM_TEXT);

        if ($widget == 'timeline') {
            static::require_user();
            static::timeline();
        } elseif ($widget == 'course_list') {
            static::require_user();
            static::course_list();
        } elseif ($widget == 'test') {
            $data = static::get_post_data();
            static::html_response(print_r(['viewinguser' => $data->viewinguser], true));
        } else {
            static::error_response("Widget '{$widget}' nicht bekannt!");
        }
    }
}

// if (!$orgid) {
//     static::error_response('Error: no orgid');
// }

// $org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));
//
// if (!$org) {
//     if ($role == 'tch') {
//         static::html_response("Ihre Schule ist noch nicht bei eduvidual.at registriert!", [
//             'button' => [
//                 // "icon": "fa fa-euro", // all icons of font-awesome free v6.2.1, either fa or image
//                 "label" => "Schule über eeducation.at auf eduvidual.at registrieren",
//                 "link" => "https://eeducation.at/meine-schule/eduvidual-registrierung",
//             ],
//         ]);
//     } else {
//         static::html_response('Deine Schule ist leider noch nicht auf eduvidual.at registriert!');
//     }
// }

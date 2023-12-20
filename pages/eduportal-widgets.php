<?php

require_once __DIR__ . '/../../../config.php';

$widget = optional_param('widget', '', PARAM_TEXT);

// if ($_SERVER['PHP_AUTH_PW'] != 'eduvidual') {
//     throw new \moodle_exception('not allowed (reason: password)');
// }

if (!empty($_SERVER['PHP_AUTH_PW'])) {
    if ($_SERVER['PHP_AUTH_PW'] != get_config('local_eduvidual', 'eduportal_widget_password')) {
        header('WWW-Authenticate: Basic realm="Widgets"');
        header('HTTP/1.0 401 Unauthorized');
        local_eduvidual_widget_error_response('Authorization Required #5852dffdvd');
    }

    $postdata = file_get_contents('php://input');
    $data = json_decode($postdata);
} elseif (isloggedin() && !isguestuser()) {
    // allow widget test
    $data = (object)[];
} else {
    header('WWW-Authenticate: Basic realm="Widgets"');
    header('HTTP/1.0 401 Unauthorized');
    local_eduvidual_widget_error_response('Authorization Required #5852dffdvd');
}

// $orgid = $data->orgid;
// $role = $data->role;

// testing:
// $orgid = 999999;

// set language to the one on eduportal
$SESSION->forcelang = $data->lang ?? 'de';

// if ($widget == 'timeline-test') {
//     $user = $DB->get_record('user', ['username' => 'student']) ?: $DB->get_record('user', ['username' => 'dprieler+lehrer@gmail.com']);
//     local_eduvidual_widget_timetable($user);
// } elseif ($widget == 'course_list-test') {
//     $user = $DB->get_record('user', ['username' => 'student']) ?: $DB->get_record('user', ['username' => 'dprieler+lehrer@gmail.com']);
//     local_eduvidual_widget_course_list($user);
// }

function local_eduvidual_widget_response($data) {
    $response = (object)[
        'data' => $data,
        'expiration' => 1,
    ];

    header('Content-Type: application/json');

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function local_eduvidual_widget_html_response($text, $additionalData = []) {
    local_eduvidual_widget_response(array_merge([
        'type' => 'html',
        'content' => $text,
    ], $additionalData));
}

function local_eduvidual_widget_error_response($error) {
    local_eduvidual_widget_response([
        'type' => 'failed',
        'title' => $error,
        'errorCode' => $error,
    ]);
}

function local_eduvidual_widget_require_user() {
    global $CFG, $DB, $USER, $data;

    if (isloggedin() && !isguestuser()) {
        return $USER;
    }

    // testing:
    // $user = $DB->get_record('user', ['username' => 'student']);
    // return $user;

    $bpk = $data->viewinguser ?? '';

    if ($bpk == 'BF:') {
        // eduportal bug:
        local_eduvidual_widget_error_response("bpk bug! bpk: '$bpk'");
        // $bpk = 'BF:bpkBFvonMarieCurie';
    }

    if (!$bpk) {
        local_eduvidual_widget_error_response('no bpk given');
    }

    $idps = explode("\n", get_config('auth_shibboleth', 'organization_selection'));
    $idpX = explode(",", $idps[0]);
    $idp = trim($idpX[0]);

    $shibboleth_link = $DB->get_record_select('auth_shibboleth_link',
        'idp LIKE ? AND ' . $DB->sql_like('idpusername', '?'),
        [$idp, $bpk]);

    // global $data;
    // local_eduvidual_widget_html_response('https://fdfdfdsfd ' . $idp . ' ' . $bpk . print_r($data, true));

    if ($shibboleth_link) {
        $user = $DB->get_record('user', array('id' => $shibboleth_link->userid));
    } else {
        $user = null;
    }

    if (!$user) {
        local_eduvidual_widget_html_response('Zur Anzeige der Daten ist eine Verknüpfung der Bildungsportal- und Eduvidual-Benutzer notwendig!', [
            'button' => [
                // "icon": "fa fa-euro", // all icons of font-awesome free v6.2.1, either fa or image
                "target" => "_blank",
                "label" => "Benutzer verknüpfen",
                "link" => $CFG->wwwroot . '/auth/shibboleth_link/login.php?idp=' . rawurlencode($idp),
            ],
        ]);
    }

    return $user;
}

function local_eduvidual_widget_timetable($user) {
    global $CFG;

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
        local_eduvidual_widget_html_response('Die Zeitleiste enthält keine Einträge!');
    }

    $responseData = (object)[
        'type' => 'detaillist',
        'items' => [],
        'button' => [
            'label' => 'Alle anzeigen',
            'link' => (new \moodle_url('/calendar/view.php'))->out(false),
            // , ['view' => 'month', 'course' => 1]),
        ],
    ];

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

        $responseData->items[] = [
            'label' => $event->get_name(),
            'detailleft' => userdate($event->get_times()->get_start_time()->getTimestamp(), get_string('strftimedatetimeshort', 'core_langconfig')),
            'detailright' =>
            // ($data ? $data->activitystr . ' · ' : '') .
                $event->get_course()->get('shortname'),
            'icon' => 'fa fa-calendar',
            'link' => $url->out(false),
            // , ['view' => 'month', 'course' => 1]),
        ];
    }

    local_eduvidual_widget_response($responseData);
}

function local_eduvidual_widget_course_list($user) {
    global $CFG;

    require_once($CFG->libdir . '/enrollib.php');
    $courses = enrol_get_users_courses($user->id);

    if (!$courses) {
        local_eduvidual_widget_html_response('Sie sind in keinen Kursen eingeschrieben!');
    }

    $responseData = (object)[
        'type' => 'btnlist',
        'items' => [],
        'button' => [
            'label' => 'Alle anzeigen',
            'link' => (new \moodle_url('/my/courses.php'))->out(false),
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
            'link' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            // , ['view' => 'month', 'course' => 1]),
        ];
    }

    local_eduvidual_widget_response($responseData);
}

// if (!$orgid) {
//     local_eduvidual_widget_error_response('Error: no orgid');
// }

// $org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));
//
// if (!$org) {
//     if ($role == 'tch') {
//         local_eduvidual_widget_html_response("Ihre Schule ist noch nicht bei eduvidual.at registriert!", [
//             'button' => [
//                 // "icon": "fa fa-euro", // all icons of font-awesome free v6.2.1, either fa or image
//                 "label" => "Schule über eeducation.at auf eduvidual.at registrieren",
//                 "link" => "https://eeducation.at/meine-schule/eduvidual-registrierung",
//             ],
//         ]);
//     } else {
//         local_eduvidual_widget_html_response('Deine Schule ist leider noch nicht auf eduvidual.at registriert!');
//     }
// }

if ($widget == 'timeline') {
    $user = local_eduvidual_widget_require_user();
    local_eduvidual_widget_timetable($user);
} elseif ($widget == 'test') {
    local_eduvidual_widget_html_response(print_r(['viewinguser' => $data->viewinguser], true));
} elseif ($widget == 'course_list') {
    $user = local_eduvidual_widget_require_user();
    local_eduvidual_widget_course_list($user);
} else {
    local_eduvidual_widget_error_response("Widget '{$widget}' nicht bekannt!");
}

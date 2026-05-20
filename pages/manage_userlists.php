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

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');

$orgid = optional_param('orgid', 0, PARAM_INT);
$cohort = optional_param('cohort', '___all', PARAM_TEXT);
$format = optional_param('format', 'list', PARAM_ALPHANUM);

$org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid), '*', MUST_EXIST);
$context = \context_coursecat::instance($org->categoryid);
$PAGE->set_context($context);

$PAGE->set_url(new \moodle_url('/local/eduvidual/pages/manage_userlists.php', array('orgid' => $orgid, 'cohort' => $cohort, 'format' => $format)));
$PAGE->set_title(get_string('manage:userlist', 'local_eduvidual', $org));
$PAGE->set_heading(get_string('manage:userlist', 'local_eduvidual', $org));
$PAGE->requires->css('/local/eduvidual/style/manage_bunch.css');

require_capability('local/eduvidual:canmanage', $context);

$PAGE->navbar->add(get_string('Management', 'local_eduvidual'), new moodle_url('/local/eduvidual/pages/manage.php', array('orgid' => $orgid)));
$PAGE->navbar->add(get_string('manage:userlist', 'local_eduvidual', $org), $PAGE->url);

$table = new class($org, $cohort) extends local_table_sql\table_sql_form {
    function __construct(private object $org, private string $cohort) {
        parent::__construct([$org->orgid, $cohort]);
    }

    function define_table_configs() {
        $where = '';
        $params = [$this->org->orgid];

        switch ($this->cohort) {
            case '___all':
                $role = '';
                break;
            case '___allparents':
                $role = \local_eduvidual\locallib::ROLE_PARENT;
                break;
            case '___allstudents':
                $role = \local_eduvidual\locallib::ROLE_STUDENT;
                break;
            case '___allteachers':
                $role = \local_eduvidual\locallib::ROLE_TEACHER;
                break;
            case '___allmanagers':
                $role = \local_eduvidual\locallib::ROLE_MANAGER;
                break;
            default:
                $where .= ' AND u.id IN (SELECT userid FROM {cohort_members} WHERE cohortid=?)';
                $params[] = $this->cohort;
                $role = '';
        }

        if ($role) {
            $where .= ' AND ou.role=?';
            $params[] = $role;
        }

        $linkedsql = "CASE WHEN EXISTS(SELECT 1 FROM {auth_shibboleth_link} ash WHERE ash.userid=u.id) THEN 1 ELSE 0 END";
        $this->set_sql_query("
            SELECT u.*, ou.role,
                ($linkedsql) AS linked
            FROM {user} u
            JOIN {local_eduvidual_orgid_userid} ou ON u.id=ou.userid AND ou.orgid=?
            WHERE deleted=0 $where
        ", $params);

        if ($this->is_downloading()) {
            $cols = array(
                'id' => 'id',
                'auth' => 'auth',
                'username' => 'username',
                'email' => 'email',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'role' => 'role',
                'linked' => 'linked',
                'lastlogin' => 'lastlogin',
                'cohorts_add' => 'cohorts_add',
                'cohorts_remove' => 'cohorts_remove',
                'secret' => 'secret',
                'password' => 'password',
                'forcechangepassword' => 'forcechangepassword',
            );
        } else {
            $cols = [
                'userpic' => get_string('pictureofuser'),
                'lastname' => get_string('lastname'),
                'firstname' => get_string('firstname'),
                'email' => get_string('email'),
                'role' => get_string('role'),
                'linked' => get_string('manage:userlist:linked', 'local_eduvidual'),
                'secret' => get_string('secret', 'local_eduvidual'),
                'auth' => get_string('manage:authtype', 'local_eduvidual'),
                'lastlogin' => get_string('lastlogin'),
            ];
        }

        $this->set_table_columns($cols);

        $this->sortable(true, 'lastname', SORT_ASC);

        if (isset($cols['userpic'])) {
            $this->set_column_options('userpic', no_sorting: true, no_filter: true);
        }
        $this->set_column_options('lastlogin', data_type: static::PARAM_TIMESTAMP);
        $this->set_column_options('secret', no_sorting: true, no_filter: true);
        $this->set_column_options('linked',
            sql_column: 'linked',
            select_options: [
                '1' => get_string('manage:userlist:linked:yes', 'local_eduvidual'),
                '0' => get_string('manage:userlist:linked:no', 'local_eduvidual'),
            ],
        );

        if (isset($cols['cohorts_add'])) {
            $this->set_column_options('cohorts_add', no_sorting: true, no_filter: true);
        }
        if (isset($cols['cohorts_remove'])) {
            $this->set_column_options('cohorts_remove', no_sorting: true, no_filter: true);
        }
        if (isset($cols['forcechangepassword'])) {
            $this->set_column_options('forcechangepassword', no_sorting: true, no_filter: true);
        }

        $this->is_downloadable(true, 'users_' . date("Ymd-His"));

        $this->set_sql_table('user');
        $this->add_form_action(new class extends \local_table_sql\table_sql_subform {
            function definition() {
                $mform = $this->_form;

                $mform->addElement('text', 'firstname', get_string('firstname'));
                $mform->setType('firstname', PARAM_TEXT);
                $mform->addElement('text', 'lastname', get_string('lastname'));
                $mform->setType('lastname', PARAM_TEXT);
                $mform->addElement('text', 'email', get_string('email'));
                $mform->setType('email', PARAM_TEXT);
            }

            function set_data($default_values) {
                // Bei verknüpften Konten kommen Vor-/Nachname aus dem IdP - der Manager
                // darf sie nicht überschreiben. Hinweis als statischer Text einblenden.
                if (!empty($default_values->linked)) {
                    $this->_form->hardFreeze(['firstname', 'lastname']);
                    $this->_form->addElement('static', 'linkednote', '',
                        get_string('manage:userlist:linked:note', 'local_eduvidual'));
                }
                parent::set_data($default_values);
            }

            //Custom validation should be added here
            function validation($data, $files) {
                $errors = array();
                if (strlen($data['firstname']) < 2) {
                    $errors['firstname'] = get_string('manage:profile:tooshort', 'local_eduvidual', array('fieldname' => get_string('firstname'), 'minchars' => '2'));
                }
                if (strlen($data['lastname']) < 2) {
                    $errors['lastname'] = get_string('manage:profile:tooshort', 'local_eduvidual', array('fieldname' => get_string('lastname'), 'minchars' => '2'));
                }
                if (!validate_email($data['email'])) {
                    $errors['email'] = get_string('manage:profile:invalidmail', 'local_eduvidual');
                }
                return $errors;
            }
        });
    }

    function col_linked($row) {
        if ($this->is_downloading()) {
            return !empty($row->linked) ? 1 : 0;
        }
        if (!empty($row->linked)) {
            return '<i class="fa fa-check text-success" aria-hidden="true" title="' .
                s(get_string('manage:userlist:linked:yes', 'local_eduvidual')) . '"></i>';
        }
        return '<i class="fa fa-times text-danger" aria-hidden="true" title="' .
            s(get_string('manage:userlist:linked:no', 'local_eduvidual')) . '"></i>';
    }

    function col_userpic($row) {
        global $OUTPUT;

        return $OUTPUT->user_picture($row, array('size' => 50));
    }

    function col_email($row) {
        if ($this->is_downloading()) {
            return $row->email;
        } else {
            return $this->format_col_content($row->email, 'mailto:' . $row->email);
        }
    }

    function col_secret($row) {
        $this->profile_load_data($row);
        return $row->id . '#' . $row->profile_field_secret;
    }

    function col_password() {
        return '';
    }

    function col_forcechangepassword() {
        return 0;
    }

    function col_cohorts_add($row) {
        global $DB;

        $context = \context_coursecat::instance($this->org->categoryid);
        $cohorts = $DB->get_records_sql_menu("
            SELECT c.id, c.name
            FROM {cohort} c
            JOIN {cohort_members} cm ON c.id = cm.cohortid
            WHERE cm.userid = ?
                AND c.contextid = ?
        ", [$row->id, $context->id]);
        return implode(',', $cohorts);
    }

    function profile_load_data(object $row) {
        if (empty($row->profile_loaded)) {
            profile_load_data($row);
            $row->profile_loaded = true;
        }
    }

    function store_row(object $data): void {
        global $DB;

        // Bei verknüpften Konten Vor-/Nachname raus aus dem Update, selbst wenn jemand
        // das hardFreeze im Formular umgangen hat - update_record schreibt dann diese
        // Spalten nicht.
        if ($DB->record_exists('auth_shibboleth_link', ['userid' => $data->id])) {
            unset($data->firstname, $data->lastname);
        }

        parent::store_row($data);

        // Benutzername sollte immer gleich e-Mail-Adresse sein.
        $other_user = $DB->get_record_sql(
            "SELECT id FROM {user} WHERE id<>? AND (username=? OR email=?)",
            [$data->id, $data->email, $data->email]
        );
        if (!$other_user) {
            $DB->set_field('user', 'username', $data->email, ['id' => $data->id]);
        }
    }
};

echo $OUTPUT->header();

\local_eduvidual\output::print_manage_menu($orgid, 'users');
\local_eduvidual\output::print_manage_users_tabs($orgid, 'list');

$cohorts = [];
$cohorts[] = (object)array('id' => '___all', 'name' => get_string('manage:bunch:all', 'local_eduvidual'));
$cohorts[] = (object)array('id' => '___allparents', 'name' => get_string('manage:bunch:allparents', 'local_eduvidual'));
$cohorts[] = (object)array('id' => '___allstudents', 'name' => get_string('manage:bunch:allstudents', 'local_eduvidual'));
$cohorts[] = (object)array('id' => '___allteachers', 'name' => get_string('manage:bunch:allteachers', 'local_eduvidual'));
$cohorts[] = (object)array('id' => '___allmanagers', 'name' => get_string('manage:bunch:allmanagers', 'local_eduvidual'));

$other_cohorts = array_values($DB->get_records_sql("SELECT id,name FROM {cohort} WHERE contextid=? ORDER BY name ASC", array($context->id)));
if ($other_cohorts) {
    $cohorts[] = (object)array('id' => '', 'name' => '--------------------------------');
    $cohorts = array_merge($cohorts, $other_cohorts);
}

foreach ($cohorts as $c) {
    $c->selected = ($cohort == $c->id);
}

require_once($CFG->dirroot . '/user/profile/lib.php');

$formats = array(
    (object)array('format' => 'cards', 'name' => get_string('manage:user_bunches:format:cards', 'local_eduvidual')),
    (object)array('format' => 'list', 'name' => get_string('manage:user_bunches:format:list', 'local_eduvidual')),
);
foreach ($formats as &$f) {
    $f->selected = ($format == $f->format);
}

if ($format == 'cards') {
    $users = $table->get_all_rows();
    $cnt = 0;

    foreach ($users as $user) {
        profile_load_data($user);

        $user->backgroundcard = get_user_preferences('local_eduvidual_backgroundcard', '', $user->id);
        if (empty($user->backgroundcard)) {
            $user->backgroundcard = \local_eduvidual\lib_enrol::choose_background($user->id);
        }
        $user->userpicture = $OUTPUT->user_picture($user, array('size' => 200));

        $cnt++;
        if ($cnt == 18) {
            $cnt = 0;
            $user->pagebreakafter = true;
        }
    }
} else {
    $users = [];
}

echo $OUTPUT->render_from_template('local_eduvidual/manage_userlists', array(
    'cohorts' => $cohorts,
    'format_cards' => ($format == 'cards'),
    'formats' => $formats,
    'orgid' => $orgid,
    'users' => array_values($users),
    'wwwroot' => $CFG->wwwroot,
));

if ($format == 'list') {
    $table->out();
}

echo $OUTPUT->footer();

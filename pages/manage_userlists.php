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

// Only allow a certain user group access to this script
$allow = array("Manager");
if (!in_array(\local_eduvidual\locallib::get_orgrole($orgid), $allow) && !is_siteadmin()) {
    echo $OUTPUT->header();
    ?>
    <p class="alert alert-danger"><?php get_string('access_denied', 'local_eduvidual'); ?></p>
    <?php
    echo $OUTPUT->footer();
    exit;
}

$PAGE->navbar->add(get_string('Management', 'local_eduvidual'), new moodle_url('/local/eduvidual/pages/manage.php', array('orgid' => $orgid)));
$PAGE->navbar->add(get_string('manage:userlist', 'local_eduvidual', $org), $PAGE->url);

$table = new class($orgid, $cohort) extends local_table_sql\table_sql {
    function __construct(private int $orgid, private string $cohort) {
        parent::__construct([$orgid]);
    }

    function define_table_configs() {
        $where = '';
        $params = [$this->orgid];

        switch ($this->cohort) {
            case '___all':
                $role = '';
                break;
            case '___allparents':
                $role = 'Parent';
                break;
            case '___allstudents':
                $role = 'Student';
                break;
            case '___allteachers':
                $role = 'Teacher';
                break;
            case '___allmanagers':
                $role = 'Manager';
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

        $this->set_sql_query("
            SELECT u.*, ou.role
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
                'secret' => get_string('secret', 'local_eduvidual'),
                'auth' => get_string('manage:authtype', 'local_eduvidual'),
                'lastlogin' => get_string('lastlogin'),
                'action' => '',
            ];
        }

        $this->set_table_columns($cols);

        $this->sortable(true, 'lastname', SORT_ASC);

        if (isset($cols['userpic'])) {
            $this->set_column_options('userpic', no_sorting: true, no_filter: true);
        }
        $this->set_column_options('lastlogin', data_type: static::PARAM_TIMESTAMP);
        $this->set_column_options('secret', no_sorting: true, no_filter: true);
        if (isset($cols['action'])) {
            $this->set_column_options('action', no_sorting: true, no_filter: true);
        }

        $this->is_downloadable(true, 'users_' . date("Ymd-His"));
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

    function col_action($row) {
        ob_start();
        ?>
        <a href="#" style="text-decoration: none; color: unset;"
           onclick="require(['local_eduvidual/manager'], function(M) { M.editProfile(<?= $this->orgid ?>,<?= $row->id ?>, { run: function() { table_sql_reload(); } }); }); return false;">
            <i class="fa fa-edit"></i>
        </a>
        <?php
        return ob_get_clean();
    }

    function col_password() {
        return '';
    }

    function col_forcechangepassword() {
        return 0;
    }

    /**
     * TODO: die Logik dieser Funktion überprüfen / optimieren.
     */
    function col_cohorts_add($row) {
        global $DB;
        static $org;

        if (!$org) {
            $org = $DB->get_record('local_eduvidual_org', array('orgid' => $this->orgid));
            $context = \context_coursecat::instance($org->categoryid);
        }

        if (!empty($context->id)) {
            $sql = "SELECT c.id,c.name
                FROM {cohort} c, {cohort_members} cm
                WHERE c.id=cm.cohortid
                    AND cm.userid=?
                    AND c.contextid=?";
            $cohorts = $DB->get_records_sql($sql, array($row->id, $context->id));
            $cohorts_ = array();
            foreach ($cohorts as $cohort) {
                $cohorts_[] = $cohort->name;
            }
            return implode(',', $cohorts_);
        }
    }

    function profile_load_data(object $row) {
        if (empty($row->profile_loaded)) {
            profile_load_data($row);
            $row->profile_loaded = true;
        }
    }
};

echo $OUTPUT->header();

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
    $table->setup();
    $table->query_db(999999);
    $users = $table->rawdata;
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

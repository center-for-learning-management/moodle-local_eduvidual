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
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 *             2020 onwards Center for Learning Management (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
**/

defined('MOODLE_INTERNAL') || die;

// use \Box\Spout\Common\Type;

if (file_exists("$CFG->libdir/phpspreadsheet/vendor/autoload.php")) {
    require_once("$CFG->libdir/phpspreadsheet/vendor/autoload.php");
    local_eduvidual_lib_import::$variant = 'phpspreadsheet';
    /**
     * ATTENTION, by default we got an error that Reader\Xls could not be loaded.
     * We had to change the reader for type "Xls" to Reader\Xlsx in IOFactory.php
     **/
} else {
    // Fall back to PHPExcel, that was used prior to Moodle 3.8
    // This is obsolete soon.
    require_once("$CFG->libdir/phpexcel/PHPExcel.php");
    local_eduvidual_lib_import::$variant = 'phpexcel';
}

class local_eduvidual_lib_import {
    var $fields = array();
    var $rowobjects = array();
    var $compiler;
    public static $variant = "";
    /**
     * Set the valid fields we use for import
    **/
    public function set_fields($fields) {
        $this->fields = $fields;
        $this->rowobjects = array();
    }
    public function set_compiler($compiler) {
        $this->compiler = $compiler;
    }
    /**
     * Load all modules that have been sent via post
    **/
    public function load_post() {
        $this->fields = (array)json_decode(optional_param('fields', '{}', PARAM_TEXT));
        $this->rowobjects = (array)json_decode(optional_param('rowobjects', '{}', PARAM_TEXT));
        for($a = 0; $a < count($this->rowobjects); $a++) {
            $this->rowobjects[$a] = $this->compile($this->rowobjects[$a]);
        }
    }
    /**
     * Load modules from an uploaded spreadsheet
    **/
    public function load_file($filepath) {
        if (!is_array($this->fields) || count($this->fields) == 0) {
            return;
        }
        $colids = array();
        $this->rowobjects = array();

        switch(local_eduvidual_lib_import::$variant) {
            case 'phpexcel':
                $spreadsheet = PHPExcel_IOFactory::load($filepath);
                $sheet = $spreadsheet->getSheet(0);

                // Get fields from first row and let maxcols grow.
                $row = 1; $maxcols = 0;
                while (!empty($value = $sheet->getCellByColumnAndRow($maxcols, $row, false)->getCalculatedValue())) {
                    $colids[$maxcols] = strtolower($value);
                    $maxcols++;
                }

                $row++;
                $stop = false;

                while(!$stop) {
                    // Create object from row-data.
                    $foundany = false;
                    $obj = new stdClass();
                    for($col = 0; $col < $maxcols; $col++) {
                        $cell = $sheet->getCellByColumnAndRow($col, $row, false);
                        if (!empty($cell)) {
                            $obj->{$colids[$col]} = $cell->getCalculatedValue();
                            if (!empty($obj->{$colids[$col]})) $foundany = true;
                        }
                    }
                    if (!empty($obj->role)) {
                        $obj = $this->compile($obj);
                        $this->rowobjects[] = $obj;
                    }
                    if (!$foundany) {
                        $stop = true;
                    }
                    $row++;
                }
            break;
            default:
                // The default is phpspreadsheet.
                //$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filepath);
                //$spreadsheet->setReadDataOnly(true);
                //$spreadsheet->load($filepath);
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
                $sheet = $spreadsheet->getSheet(0);

                // Get fields from first row.
                // ATTENTION: In phpspreadsheet the first cell is 1:1
                $stop = false;
                $maxcols = 1;
                while (!$stop) {
                    $value = $sheet->getCellByColumnAndRow($maxcols, 1, false);
                    if (!empty($value)) {
                        $colids[$maxcols] = strtolower($value);
                        $maxcols++;
                    } else {
                        $stop = true;
                    }
                }

                $row = 2;
                $stop = false;

                while(empty($stop)) {
                    // Create object from row-data.
                    $foundany = false;
                    $obj = new stdClass();
                    for($col = 1; $col < $maxcols; $col++) {
                        if (!empty($colids[$col])) {
                            // Concat with empty string to force string conversion.
                            $obj->{$colids[$col]} = "" . $sheet->getCellByColumnAndRow($col, $row, false);
                            if (!empty($obj->{$colids[$col]})) $foundany = true;
                        }
                    }
                    if (!empty($obj->role)) {
                        $this->rowobjects[] = $this->compile($obj);
                    }
                    if ($foundany) {
                        $row++;
                    } else {
                        $stop = true;
                    }
                }
        }
    }
    /**
     * Print all rows in hidden textareas to be used in a form.
     * Returns text-areas as array to be used in a form.
    **/
    public function print_hidden_form() {
        $form = array();
        $form[] = '<textarea style="display: none;" name="fields">' . json_encode($this->fields, JSON_NUMERIC_CHECK) . '</textarea>';
        $form[] = '<textarea style="display: none;" name="rowobjects">' . json_encode($this->rowobjects, JSON_NUMERIC_CHECK) . '</textarea>';
        return "\t\t" . implode("\n\t\t", $form);
    }
    /**
     * Returns text-areas as array to be used in a form.
    **/
    public function print_hidden_array() {
        return array(
            'fields' => json_encode($this->fields, JSON_NUMERIC_CHECK),
            'rowobjects' => json_encode($this->rowobjects, JSON_NUMERIC_CHECK),
        );
    }
    /**
     * If there is a compiler we use it to compile the object
    **/
    public function compile($obj) {
        if (isset($this->compiler)) {
            $obj = $this->compiler->compile($obj);
        }
        return $obj;
    }
    /**
     * Creates an XSLX-Sheet to be downloaded.
    **/
    public function download($filename = 'import'){
        $writer = \Box\Spout\Writer\WriterFactory::create(Type::XLSX); // for XLSX files
        $writer->openToBrowser($filename . '.xlsx'); // stream data directly to the browser

        $row = array();
        for($fieldid = 0; $fieldid < count($this->fields); $fieldid++) {
            $row[$fieldid] = $this->fields[$fieldid];
        }
        $writer->addRow($row);

        foreach($this->rowobjects AS $rowobject) {
            $row = array();
            for($fieldid = 0; $fieldid < count($this->fields); $fieldid++) {
                $row[$fieldid] = $rowobject->{$this->fields[$fieldid]};
            }
            $writer->addRow($row);
        }

        $writer->close();
    }
    /**
     * @return all rowobjects
    **/
    public function get_rowobjects(){
        return $this->rowobjects;
    }
    /**
     * Overwrites rowobjects
    **/
    public function set_rowobjects($rowobjects) {
        $this->rowobjects = $rowobjects;
    }
    /**
     * @return all known fields
    **/
    public function get_fields(){
        return $this->fields;
    }
}

abstract class local_eduvidual_lib_import_compiler {
    abstract public function compile($obj);
}

class local_eduvidual_lib_import_compiler_module extends local_eduvidual_lib_import_compiler {
    public function compile($module) {
        $payload = new stdClass();
        $payload->processed = false;
        //$payload->customize = new stdClass();
        $payload->defaults = new stdClass();
        // Common fields
        $payload->defaults->name = $module->name;
        $payload->defaults->intro = $module->description;
        if (isset($module->ltilaunch)) {

        } elseif (isset($module->url)) {
            $payload->defaults->externalurl = $module->url;
            $module->type = 'url';
            $payload->processed = true;
        }

        // Revoke processed flag if required information is missing!
        if (
            empty($module->categoryid)
            ||
            $module->categoryid == 0
            ||
            empty($module->name)) {
            $payload->processed = false;
        }
        $module->payload = $payload;
        return $module;
    }
}

class local_eduvidual_lib_import_compiler_user extends local_eduvidual_lib_import_compiler {
    var $lasts = 0;
    public function compile($obj) {
        global $CFG, $DB, $org;
        $payload = new stdClass();
        $payload->processed = true;
        if (!isset($obj->id)) {
            $obj->id = 0;
        }
        if (empty($obj->firstname)) {
            $colors = file($CFG->dirroot . '/local/eduvidual/templates/names.colors', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $color_key = array_rand($colors, 1);
            $obj->firstname = $colors[$color_key];
        }
        if (empty($obj->lastname)) {
            $animals = file($CFG->dirroot . '/local/eduvidual/templates/names.animals', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $animal_key = array_rand($animals, 1);
            $obj->lastname = $animals[$animal_key];
        }
        $dummydomain = \local_eduvidual\locallib::get_dummydomain();
        if (empty($obj->email)) {
            $pattern = 'e-' . date("Ym") . '-';
            $usernameformat= $pattern . '%1$04d';
            $lasts = $DB->get_records_sql('SELECT username FROM {user} WHERE username LIKE ? ORDER BY username DESC LIMIT 0,1', array($pattern . '%'));

            if ((count($lasts)) > 0) {
                foreach($lasts AS $last){
                    $usernumber = intval(str_replace($pattern, '', $last->username)) + $this->lasts + 1;
                }
            } else {
                $usernumber = 1 + $this->lasts;
            }
            $this->lasts++;
            $obj->username = sprintf($usernameformat, $usernumber++);
            $obj->email = $obj->username . $dummydomain;
        } else {
            $obj->email = strtolower($obj->email);
            global $CFG;

            if (empty($obj->username) || !is_siteadmin()) {
                $obj->username = str_replace($dummydomain, '', $obj->email);
            }
        }
        $obj->email = trim(str_replace("+", "_", $obj->email));
        $obj->firstname = trim($obj->firstname);
        $obj->lastname = trim($obj->lastname);
        $obj->role = trim(ucfirst(strtolower($obj->role)));
        $obj->username = trim(str_replace($dummydomain, '', $obj->username));
        if (empty($obj->cohorts_add) && !empty($obj->bunch)) {
            // Translate the old name to its new name.
            $obj->cohorts_add = $obj->bunch;
            unset($obj->bunch);
        }

        if (!filter_var($obj->email, FILTER_VALIDATE_EMAIL)) {
            $payload->processed = false;
            $payload->action = get_string('import:invalid_email', 'local_eduvidual');
        }

        // Revoke processed flag if required information is missing!
        if (!in_array($obj->role, array('Manager', 'Teacher', 'Student', 'Parent', 'Remove'))) {
            $payload->processed = false;
            $payload->action = get_string('import:invalid_role', 'local_eduvidual');
        }

        // Check if email is already taken and set userid accordingly.
        $sql = "SELECT id
                    FROM {user}
                    WHERE
                        username LIKE ? OR
                        email LIKE ?";
        $params = array(
            str_replace('_', '[_]', $obj->email),
            str_replace('_', '[_]', $obj->email)
        );
        $ids = array_keys($DB->get_records_sql($sql, $params));
        if (count($ids) > 0) {
            if (count($ids) == 1) {
                // Set the userid given.
                $obj->id = $ids[0];
            } else {
                // We have multiple candidates, if an id was given and it fits, we use it.
                if (empty($obj->id) || !in_array($obj->id, $ids)) {
                    // The id was missing or invalid - we set the first candidate.
                    $obj->id = $ids[0];
                }
            }
        }
        if ($obj->id > 0) {
            $payload->action = get_string('update');
        } else {
            $payload->action = get_string('create');
        }

        if (!empty($obj->id)) {
            $ismember = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $org->orgid, 'userid' => $obj->id));
            if ($ismember->userid != $obj->id) {
                $payload->processed = false;
                $payload->action = get_string('import:invalid_org', 'local_eduvidual');
            }
            if (is_siteadmin($obj->id)) {
                $payload->processed = false;
                $payload->action = get_string('import:issiteadmin', 'local_eduvidual');
            }
            if (!empty($obj->password) || !empty($obj->forcechangepassword)) {
                $canchangepasswordsfor = array("manual", "self");
                $usero = \core_user::get_user($obj->id, 'id,auth');
                if (!empty($usero->id) && !in_array($usero->auth, $canchangepasswordsfor)) {
                    $payload->processed = false;
                    $payload->action = get_string('import:cannotchangepassword', 'local_eduvidual', $usero);
                }
            }

        } else {
            // Test if username or email already taken.
            $sql = "SELECT id
                        FROM {user}
                        WHERE
                            username LIKE ? OR
                            username LIKE ? OR
                            email LIKE ? OR
                            email LIKE ?";
            $params = array(
                str_replace('_', '[_]', $obj->username),
                str_replace('_', '[_]', $obj->email),
                str_replace('_', '[_]', $obj->username),
                str_replace('_', '[_]', $obj->email)
            );
            $chk = $DB->get_records_sql($sql, $params);
            $ids = array_keys($chk);
            if (count($ids) > 0) {
                $payload->processed = false;
                $payload->action = get_string('import:invalid_username_or_email', 'local_eduvidual');
            }
        }

        // Set default language to german.
        $obj->lang = 'de';

        $obj->payload = $payload;
        return $obj;
    }
}

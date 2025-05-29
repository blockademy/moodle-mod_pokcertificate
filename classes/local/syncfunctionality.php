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
 * student bulk update.
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pokcertificate\local;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/admin/tool/uploaduser/locallib.php');

use html_writer;
use stdClass;

/**
 * Class syncfunctionality
 *
 * This class handles synchronization functionality for importing user data from HRMS files
 * into Moodle for the mod_pokcertificate module.
 */
class syncfunctionality {

    /**
     * @var mixed The data to be synchronized.
     */
    private $data;

    /**
     * @var array Stores error messages encountered during synchronization.
     */
    private $errors = [];

    /**
     * @var array Stores fields that have validation errors.
     */
    private $mfields = [];

    /**
     * @var int Stores the count of errors encountered during synchronization.
     */
    private $errorcount = 0;

    /**
     * @var int Stores the count of successfully updated records during synchronization.
     */
    private $updatedcount = 0;

    /**
     * @var mixed Stores information about existing users during synchronization.
     */
    private $existinguser;

    /**
     * @var mixed Returns mandatory fields count.
     */
    private $mandatoryfieldcount;

    /**
     * The line number in the Excel file being processed.
     *
     * This property keeps track of the current line number in the Excel file
     * that is being processed. It is used for error reporting and validation purposes.
     *
     * @var int
     */
    private $excellinenumber;

    /**
     * Constructor for syncfunctionality class.
     *
     * @param mixed $data The data to be synchronized.
     */
    public function __construct($data = null) {
        $this->data = $data;
    } // End of constructor.

    /**
     * Main method for HRMS frontend form submission.
     *
     * This method processes the submitted HRMS file data and performs various validation
     * checks before updating user records in Moodle.
     *
     * @param object $cir The file reader object.
     * @param array $filecolumns The columns present in the HRMS file.
     * @param mixed $formdata Additional form data.
     */
    public function main_hrms_frontendform_method($cir, $filecolumns, $formdata) {
        global $DB, $CFG;

        $linenum = 1;
        $mandatoryfields = [
            'username',
            'studentname',
            'surname',
            'email',
            'studentid',
        ];
        $this->mandatoryfieldcount = 0;
        while ($line = $cir->next()) {
            $linenum++;
            $user = new \stdClass();
            foreach ($line as $keynum => $value) {
                if (!isset($filecolumns[$keynum])) {
                    continue;
                }
                $key = $filecolumns[$keynum];
                $user->$key = trim($value);
            }
            $this->errors = [];
            $this->mfields = [];
            $this->excellinenumber = $linenum;

            // Mandatory field validation.
            foreach ($mandatoryfields as $field) {
                $this->mandatory_field_validation($user, $field);
                $this->mandatoryfieldcount++;
            }
            // To check for existing user record.
            $sql = "SELECT u.id,u.username
                      FROM {user} u
                     WHERE u.username = :username AND u.deleted = 0 ";
            $params = [];
            $params['username'] = $user->username;
            $existinguser = $DB->get_records_sql($sql, $params);
            if (count($existinguser) == 1) {
                $this->existinguser = array_values($existinguser)[0];
                if (!empty($user->studentname)) {
                    $this->studentname_validation($user);
                }
                if (!empty($user->surname)) {
                    $this->surname_validation($user);
                }
                if (!empty($user->email)) {
                    $this->email_validation($user);
                }
                if (!empty($user->studentid)) {
                    $this->studentid_validation($user);
                }
                if (!(count($this->errors) > 0)) {
                    $userobject = $this->preparing_users_object($user, $formdata);
                    $this->update_row($user, $userobject, $formdata);
                }
            } else if (count($existinguser) > 1) {
                $this->errors[] = get_string('multiple_user', 'mod_pokcertificate');
            } else {
                $this->nouserexist($user);
            }
        }

        $uploadinfo = html_writer::div(
            html_writer::tag(
                'h3',
                get_string('empfile_syncstatus', 'mod_pokcertificate'),
                ['style' => 'text-decoration: underline;']
            ) .
            html_writer::div(get_string('updatedusers_msg', 'mod_pokcertificate', $this->updatedcount)) .
            html_writer::div(get_string('errorscount_msg', 'mod_pokcertificate', $this->errorcount)),
            'critera_error1'
        );

        $button = html_writer::tag(
            'button',
            get_string('continue'),
            ['class' => 'btn btn-primary']
        );

        $link = html_writer::tag(
            'a',
            $button,
            ['href' => $CFG->wwwroot . '/mod/pokcertificate/incompletestudent.php']
        );

        $uploadinfo .= html_writer::div($link, 'w-full pull-left text-xs-center');
        mtrace($uploadinfo);
    } // End of main_hrms_frontendform_method.

    /**
     * Prepares the user object from Excel data.
     *
     * This method prepares a user object from the Excel data, including the firstname,
     * lastname, email, and idnumber fields. It also processes any custom profile fields.
     *
     * @param object $excel The Excel data for a user.
     * @param mixed $formdata Additional form data.
     * @return object The prepared user object.
     */
    public function preparing_users_object($excel, $formdata = null) {
        $user = new \stdclass();
        $user->firstname    = trim($excel->studentname);
        $user->lastname     = trim($excel->surname);
        $user->email        = strtolower(trim($excel->email));
        $user->idnumber     = trim($excel->studentid);
        $result = preg_grep("/profile_field_/", array_keys((array)$excel));

        if (count($result) > 0) {
            foreach ($result as $key => $val) {
                $user->$val = $excel->$val;
            }
        }
        return $user;
    } // End of  preparing_users_object method.

    /**
     * Updates a user record.
     *
     * This method updates a user record in Moodle with the provided Excel data and form data.
     *
     * @param object $excel The Excel data for a user.
     * @param object $userobject The prepared user object.
     * @param mixed $formdata Additional form data.
     */
    public function update_row($excel, $userobject, $formdata) {
        global $USER;
        $userid = $this->existinguser->id;
        if ($userid) {
            $user = clone $userobject;
            $user->id = $userid;
            $user->timemodified = time();
            $user->usermodified = $USER->id;
            user_update_user($user, false);
            // Pre-process custom profile menu fields data from csv file.
            $existinguser = uu_pre_process_custom_profile_data($excel);
            $existinguser->id = $user->id;

            // Save custom profile fields data from csv file.
            profile_save_data($existinguser);
            $this->updatedcount++;
        }
    } // End of  update_row method.

    /**
     * Validates mandatory missing fields.
     *
     * This method validates whether mandatory fields are missing in the provided Excel data.
     *
     * @param object $user The user object.
     * @param string $field The field to be validated.
     */
    public function mandatory_field_validation($user, $field) {
        if (empty(trim($user->$field))) {
            $strings = new stdClass;
            $strings->field = $field;
            $strings->linenumber = $this->excellinenumber;
            $missingstring = get_string('missing', 'mod_pokcertificate', $strings);
            echo html_writer::tag('div', $missingstring);
            $this->errors[] = $missingstring;
            $this->mfields[] = $field;
            $this->errorcount++;
        }
    } //End of mandatory_field_validation method.

    /**
     * Handles the case where no user record exists.
     *
     * This method is called when no corresponding user record is found for the provided Excel data.
     * It displays an error message indicating the missing user record.
     *
     * @param object $excel The Excel data for a user.
     */
    public function nouserexist($excel) {
        $strings = new stdClass;
        $strings->linenumber = $this->excellinenumber;
        $strings->username = $excel->username;
        $nouserrecord = get_string('nouserrecord', 'mod_pokcertificate', $strings);
        echo html_writer::tag('div', $nouserrecord);
        $this->errors[] = $nouserrecord;
        $this->errorcount++;
    } // End of nouserexist method.

    /**
     * Validates the studentname field.
     *
     * This method validates the studentname field in the provided Excel data. It checks for valid studentname
     *
     * @param object $excel The Excel data for a user.
     */
    public function studentname_validation($excel) {

        $strings = new StdClass();
        $strings->linenumber = $this->excellinenumber;
        $strings->data = $excel->studentname;
        $strings->field = 'studentname';
        if (preg_match('/[.+]/', trim($excel->studentname))) {
            $invalidsapecialcharecter = get_string('invalidsapecialcharecter', 'mod_pokcertificate', $strings);
            echo html_writer::tag('div', $invalidsapecialcharecter);
            $this->errors[] = $invalidsapecialcharecter;
            $this->mfields[] = 'studentname';
            $this->errorcount++;
        }
    } // End of studentname_validation method.

    /**
     * Validates the surname field.
     *
     * This method validates the surname field in the provided Excel data. It checks for valid surname
     *
     * @param object $excel The Excel data for a user.
     */
    public function surname_validation($excel) {

        $strings = new StdClass();
        $strings->linenumber = $this->excellinenumber;
        $strings->data = $excel->surname;
        $strings->field = 'surname';
        if (preg_match('/[.+]/', trim($excel->surname))) {
            $invalidsapecialcharecter = get_string('invalidsapecialcharecter', 'mod_pokcertificate', $strings);
            echo html_writer::tag('div', $invalidsapecialcharecter);
            $this->errors[] = $invalidsapecialcharecter;
            $this->mfields[] = 'surname';
            $this->errorcount++;
        }
    } // End of surname_validation method.

    /**
     * Validates the email field.
     *
     * This method validates the email field in the provided Excel data. It checks for valid email
     * format and also verifies if the email already exists in the database.
     *
     * @param object $excel The Excel data for a user.
     */
    public function email_validation($excel) {
        global $DB;
        $strings = new StdClass();
        $strings->linenumber = $this->excellinenumber;
        $strings->data = $excel->email;
        $strings->field = 'email';
        if (!validate_email($excel->email)) {
            $invalidemailmsg = get_string('invalidemail_msg', 'mod_pokcertificate', $strings);
            echo html_writer::tag('div', $invalidemailmsg);
            $this->errors[] = $invalidemailmsg;
            $this->mfields[] = 'email';
            $this->errorcount++;
        }

        $userexist = $DB->record_exists_sql(
            "SELECT *
               FROM {user}
              WHERE email = :email AND username NOT LIKE :username",
            ['email' => $excel->email, 'username' => $excel->username]
        );

        if ($userexist) {
            $studentexist = get_string('studentexist', 'mod_pokcertificate', $strings);
            echo html_writer::tag('div', $studentexist);
            $this->errors[] = $studentexist;
            $this->mfields[] = 'email';
            $this->errorcount++;
        }
    } // End of email_validation method.

    /**
     * Validates the student ID field.
     *
     * This method validates the student ID field in the provided Excel data. It checks for numeric
     * format and also verifies if the student ID already exists in the database.
     *
     * @param object $excel The Excel data for a user.
     */
    public function studentid_validation($excel) {
        global $DB;
        $strings = new stdClass();
        $strings->linenumber = $this->excellinenumber;
        $strings->data = $excel->studentid;
        $strings->field = 'studentid';
        if (preg_match('/[.+]/', trim($excel->studentid))) {
            $invalidsapecialcharecter = get_string('invalidsapecialcharecter', 'mod_pokcertificate', $strings);
            echo html_writer::tag('div', $invalidsapecialcharecter);
            $this->errors[] = $invalidsapecialcharecter;
            $this->mfields[] = "studentid";
            $this->errorcount++;
        }
    } // End of studentid_validation method.
} // End of class.

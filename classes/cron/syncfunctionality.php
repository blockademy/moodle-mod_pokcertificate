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
 * @package   mod_pokcertificate
 * @copyright 2024 Moodle India Information Solutions Pvt Ltd
 * @author    2024 Narendra.Patel <narendra.patel@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pokcertificate\cron;
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/admin/tool/uploaduser/locallib.php');

use html_writer;
use stdClass;

class syncfunctionality
{
    private $data;
    private $errors = [];
    private $mfields = [];
    private $errorcount = 0;
    private $updatedcount = 0;   
    private $existinguser;

    public function __construct($data = null) {
        $this->data = $data;
    } // end of constructor

    public function main_hrms_frontendform_method($cir, $filecolumns, $formdata) {
        global $DB, $CFG;
    
        $linenum = 1;
        $mandatoryfields = [
            'username',
            'studentname',
            'surname',
            'email',
            'studentid'
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

        $upload_info = '<div class="critera_error1"><h3 style="text-decoration: underline;">'
            . get_string('empfile_syncstatus', 'mod_pokcertificate') . '</h3>';
        
        $upload_info .= '<div class=local_users_sync_success>' . get_string(
            'updatedusers_msg',
            'mod_pokcertificate',
            $this->updatedcount
        ) . '</div>';
        $upload_info .= '<div class=local_users_sync_error>' . get_string(
            'errorscount_msg',
            'mod_pokcertificate',
            $this->errorcount
        ) . '</div>
            </div>';
        
        $button = html_writer::tag('button', get_string('continue'), array('class' => 'btn btn-primary'));
        $link = html_writer::tag('a', $button, array('href' => $CFG->wwwroot . '/mod/pokcertificate/incompletestudent.php'));
        $upload_info .= '<div class="w-full pull-left text-xs-center">' . $link . '</div>';
        mtrace($upload_info);
    } //end of main_hrms_frontendform_method

    public function preparing_users_object($excel, $formdata = null) {
        $user = new \stdclass();
        $user->firstname    = $excel->studentname;
        $user->lastname     = $excel->surname;
        $user->email        = strtolower($excel->email);
        $user->idnumber     = $excel->studentid;
        $result = preg_grep("/profile_field_/", array_keys((array)$excel));
        
        if (count($result) > 0) {
            foreach ($result as $key => $val) {
                $user->$val = $excel->$val;
            }            
        }
        return $user;
    } // end of  preparing_users_object method

    // Condition to get the userid to update the data.
    public function update_row($excel, $userobject, $formdata) {
        global $USER;
        $userid = $this->existinguser->id;
        if ($userid) {
            $user = clone $userobject;
            $user->id = $userid;
            $user->timemodified = time();
            $user->usermodified = $USER->id;
            user_update_user($user, false);
            $this->updatedcount++;
        }
    } // end of  update_row method

    // validation for mandatory missing fields
    public function mandatory_field_validation($user, $field) {
        if (empty(trim($user->$field))) {
            $strings = new stdClass;
            $strings->field = $field;
            $strings->linenumber = $this->excellinenumber;
            $missingstring = get_string('missing', 'mod_pokcertificate', $strings);
            echo '<div class=local_users_sync_error>' . $missingstring . '</div>';
            $this->errors[] = $missingstring;
            $this->mfields[] = $field;
            $this->errorcount++;
        }
    } //end of mandatory_field_validation method

    // massage for no user exist
    public function nouserexist($excel) {
        $strings = new stdClass;
        $strings->linenumber = $this->excellinenumber;
        $strings->username = $excel->username;
        echo "<div class='local_users_sync_error'>" . get_string('nouserrecord', 'mod_pokcertificate', $strings) . "</div>";
        $this->errors[] = get_string('nouserrecord', 'mod_pokcertificate', $strings);
        $this->errorcount++;
    } // end of nouserexist method

    public function email_validation($excel) {
        global $DB;
        $strings = new StdClass();
        $strings->linenumber = $this->excellinenumber;
        $strings->data = $excel->email;
        $strings->field = 'email';
        if (!validate_email($excel->email)) {
            echo '<div class="local_users_sync_error">' . get_string('invalidemail_msg', 'mod_pokcertificate', $strings) . '</div>';
            $this->errors[] = get_string('invalidemail_msg', 'mod_pokcertificate', $strings);
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
            echo '<div class="local_users_sync_error">' . get_string('studentexist', 'mod_pokcertificate', $strings) . '</div>';
            $this->errors[] = get_string('studentexist', 'mod_pokcertificate', $strings);
            $this->mfields[] = 'email';
            $this->errorcount++;
        }
    } // end of email_validation method

    public function studentid_validation($excel) {
        global $DB;
        $strings = new stdClass();
        $strings->linenumber = $this->excellinenumber;
        $strings->data = $excel->studentid;
        $strings->field = 'studentid';
        if (!is_numeric($excel->studentid)) {
            echo '<div class="local_users_sync_error">' . get_string(
                'invalidstudentid',
                'mod_pokcertificate',
                $strings
            ) . '</div>';
            $this->errors[] = get_string('invalidstudentid', 'mod_pokcertificate', $strings);
            $this->mfields[] = "studentid";
            $this->errorcount++;
        }

        $userexist = $DB->record_exists_sql(
            "SELECT *
               FROM {user}
              WHERE idnumber = :idnumber AND username NOT LIKE :username",
            ['idnumber' => $excel->studentid, 'username' => $excel->username]
        );
        if ($userexist) {
            echo '<div class="local_users_sync_error">' . get_string(
                'studentexist',
                'mod_pokcertificate',
                $strings
            ) . '</div>';
            $this->errors[] = get_string('studentexist', 'mod_pokcertificate', $strings);
            $this->mfields[] = "studentid";
            $this->errorcount++;
        }
    } // end of class studentid_validation method
} // end of class

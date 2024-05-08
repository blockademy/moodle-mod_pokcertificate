<?php
// This file is part of eAbyas
//
// Copyright eAbyas Info Solutons Pvt Ltd, India
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage mod_pokcertificate
 */

namespace mod_pokcertificate\cron;
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/admin/tool/uploaduser/locallib.php');

use html_writer;
use stdClass;

define('ADD_UPDATE', 3);
define('MANUAL_ENROLL', 1);
define('LDAP_ENROLL', 2);
define('SAML2', 3);
define('ADWEBSERVICE', 4);
define('OTP_ENROLL', 5);

class syncfunctionality
{
    private $data;
    private $errors = array();
    private $mfields = array();
    private $warnings = array();
    private $wmfields = array();
    private $errorcount = 0;
    private $warningscount = 0;
    private $updatesupervisor_warningscount = 0;    
    private $insertedcount = 0;
    private $updatedcount = 0;   
    private $existing_user;

    public function __construct($data = null)
    {
        global $CFG;
        $this->data = $data;
        $this->timezones = \core_date::get_list_of_timezones($CFG->forcetimezone);
    } // end of constructor

    public function main_hrms_frontendform_method($cir, $filecolumns, $formdata)
    {
        global $DB, $USER, $CFG;
    
        $linenum = 1;
        $mandatory_fields = [
            'first_name',
            'last_name',
            'email',
            'idnumber',
            'username',

        ];
        $this->mandatory_field_count = 0;
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
      
            $this->data[] = $user;
            $this->errors = array();
            $this->warnings = array();
            $this->mfields = array();
            $this->wmfields = array();
            $this->excel_line_number = $linenum;
            foreach ($mandatory_fields as $field) {
                // Mandatory field validation.
                $this->mandatory_field_validation($user, $field);
                $this->mandatory_field_count++;
            }
            // To check for existing user record.
            $sql = "SELECT u.id,u.username FROM {user} u WHERE u.username = :username AND u.deleted = 0";
            $params = array();
            $params['username'] = $user->username;
            $existing_user = $DB->get_records_sql($sql, $params);
            if (count($existing_user) == 1) {
                $this->existing_user = array_values($existing_user)[0];
            } else if (count($existing_user) > 1) {
                $this->errors[] = get_string('multiple_user', 'mod_pokcertificate');
            } else {
                $this->existing_user = null;
                $exists = $DB->record_exists('user', array('username' => $user->username));
                if ($exists) {
                    $strings = new stdClass;
                    $strings->excel_line_number = $this->excel_line_number;
                    $strings->username = $user->username;
                    echo "<div class='local_users_sync_error'>" . get_string('usernamealeadyexists', 'mod_pokcertificate', $strings) . "</div>";
                    $this->errors[] = get_string('usernamealeadyexists', 'mod_pokcertificate', $strings);
                    $this->mfields[] = "username";
                    $this->errorcount++;
                    continue;
                }                
            }

            if (!empty($user->timezone)) {
                $this->timezonevalidations($user);
            }
            // Validation for employee status.
            $this->employee_status_validation($user);

            if (!empty($user->email)) {
                $this->emailid_validation($user);
            }
            // if (!empty($user->employee_code)) {
            //     $this->empid_validation($user);
            // }
            
            if (!empty($user->force_password_change)) {
                $this->force_password_change_validation($user);
            }
            if (!empty($user->password) && !check_password_policy($user->password, $errmsg)) {
                $strings = new stdClass;
                $strings->errormessage = $errmsg;
                $strings->linenumber = $this->excel_line_number;
                $this->errors[] = get_string('password_upload_error', 'mod_pokcertificate', $strings);
                echo '<div class=local_users_sync_error>' . get_string('password_upload_error', 'mod_pokcertificate', $strings) . '</div>';
                $this->errorcount++;
            }
            $userobject = $this->preparing_users_object($user, $formdata);
            // To display error messages.
            if (count($this->errors) > 0) {
                $this->write_error_in_db($user);
            } else {
                if (is_null($this->existing_user)) {
                    $this->add_row($userobject, $formdata);
                } else {
                    $this->update_row($user, $userobject, $formdata);
                }
            }
            // if (count($this->warnings) > 0) {
            //     $this->write_warnings_db($user);
            //     $this->updatesupervisor_warningscount = count($this->warnings);
            // }
        }
        // if (empty($line = $cir->next())) {
        //     if ($this->mandatory_field_count == 0) {
        //         foreach ($mandatory_fields as $field) {
        //             // Mandatory field validation.
        //             $this->mandatory_field_validation($user, $field);
        //         }
        //     }
        // }
        $upload_info = '<div class="critera_error1"><h3 style="text-decoration: underline;">'
            . get_string('empfile_syncstatus', 'mod_pokcertificate') . '</h3>';
        $upload_info .= '<div class=local_users_sync_success>' . get_string(
            'addedusers_msg',
            'mod_pokcertificate',
            $this->insertedcount
        ) . '</div>';
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
        $upload_info .= '<div class=local_users_sync_warning>' . get_string(
            'warningscount_msg',
            'mod_pokcertificate',
            $this->warningscount
        ) . '</div>';
        $upload_info .= '<div class=local_users_sync_warning>' . get_string(
            'superwarnings_msg',
            'mod_pokcertificate',
            $this->updatesupervisor_warningscount
        ) . '</div>';
        $button = html_writer::tag('button', get_string('back', 'mod_pokcertificate'), array('class' => 'btn btn-primary'));
        $link = html_writer::tag('a', $button, array('href' => $CFG->wwwroot . '/mod/pokcertificate/userupload.php'));
        $upload_info .= '<div class="w-full pull-left text-xs-center">' . $link . '</div>';
        mtrace($upload_info);
        // $sync_data = new \stdClass();
        // $sync_data->newuserscount = $this->insertedcount;
        // $sync_data->updateduserscount = $this->updatedcount;
        // $sync_data->errorscount = $this->errorcount;
        // $sync_data->warningscount = $this->warningscount;
        // $sync_data->supervisorwarningscount = $this->updatesupervisor_warningscount;
        // $sync_data->usercreated = $USER->id;
        // $sync_data->usermodified = $USER->id;
        // $sync_data->timecreated = time();
        // $sync_data->timemodified = time();
        // $sync_data->costcenterid = $this->costcenterid;
        // $insert_sync_data = $DB->insert_record('local_userssyncdata', $sync_data);
    } //end of main_hrms_frontendform_method



    
    public function preparing_users_object($excel, $formdata = null)
    {
        global $USER, $DB, $CFG;
        $user = new \stdclass();
        // $user->auth = "manual"; //by default accepts manual
        $user->mnethostid = 1;
        $user->confirmed = 1;
        $user->suspended = $this->activestatus;
        $user->idnumber = $excel->idnumber;
        $user->username = strtolower($excel->username);
        $user->firstname = $excel->first_name;
        $user->lastname = $excel->last_name;
        $user->email = strtolower($excel->email);
        $user->lang = $excel->language ? $excel->language : 'en';
        $user->learner_status = $excel->employee_status;
        $user->timezone = in_array($excel->timezone, $this->timezones) ? $excel->timezone : $CFG->forcetimezone;
        
        $user->usermodified = $USER->id;
        if (!empty(trim($excel->password))) {
            $user->password = hash_internal_user_password(trim($excel->password));
        } else {
            unset($user->password);
        }
        if ($this->deletestatus == 1) {
            $user->deleted = 0;
            $user->username = time() . $user->username;
            $user->email = time() . $user->email;
        }
        if ($formdata) {
            switch ($formdata->enrollmentmethod) {
                case MANUAL_ENROLL:
                    $user->auth = "manual";
                    break;
                case LDAP_ENROLL:
                    $user->auth = "ldap";
                    break;
                case SAML2:
                    $user->auth = "saml2";
                    break;               
                case OTP_ENROLL:
                    $user->auth = "otp";
                    break;
            }
        }
        $user->force_password_change = (empty($excel->force_password_change)) ? 0 : $excel->force_password_change;
        $result = preg_grep("/profile_field_/", array_keys((array)$excel));
        
        if (count($result) > 0) {
            foreach ($result as $key => $val) {
                $user->$val = $excel->$val;
            }            
        }

        return $user;
    } // end of  preparing_users_object method

    public function add_row($userobject, $formdata)
    {
        global $DB, $USER, $CFG;

        $insertnewuserfromcsv = user_create_user($userobject, false);
        $userobject = (object)$userobject;
        $userobject->id = $insertnewuserfromcsv;

        // Pre-process custom profile menu fields data from csv file.
        $user = uu_pre_process_custom_profile_data($userobject);
        // Save custom profile fields data.
        profile_save_data($user);

        if ($userobject->force_password_change == 1) {
            set_user_preference('auth_forcepasswordchange', $userobject->force_password_change, $insertnewuserfromcsv);
        }
        if ($formdata->createpassword) {
            $usernew = $DB->get_record('user', array('id' => $insertnewuserfromcsv));
            setnew_password_and_mail($usernew);
            unset_user_preference('create_password', $usernew);
            set_user_preference('auth_forcepasswordchange', 1, $usernew);
        }
        $this->insertedcount++;
    } // end of add_row method

    public function update_row($excel, $user1, $formdata)
    {
        global $USER, $DB, $CFG;
        // Condition to get the userid to update the data.
        $userid = $this->existing_user->id;
        if ($userid) {
            $user=clone $user1;
            $user->id = $userid;
            $user->timemodified = time();
            $user->suspended = $this->activestatus;
            $user->idnumber = $excel->idnumber;
            $user->usermodified = $USER->id;
            $user->timezone = in_array($excel->timezone, $this->timezones) ? $excel->timezone : $CFG->forcetimezone;
            if (!empty($excel->password)) {
                $user->password = hash_internal_user_password($excel->password);
            } else {
                unset($user->password);
            }
            if ($this->deletestatus == 1) {
                $user->deleted = 0;
                $user->username = time() . $user->username;
                $user->email = time() . $user->email;
                $user->open_employeeid = time() . $user->open_employeeid;
            }
            // if ($excel->reportingmanager_empid) {
            //     $super_user = $this->get_super_userid($excel->reportingmanager_empid, $user->open_path);
            //     $user->open_supervisorid = $super_user;

            // }
            user_update_user($user, false);
          
            // Pre-process custom profile menu fields data from csv file.
            $existinguser = uu_pre_process_custom_profile_data($excel);
            $existinguser->id = $user->id;
            
            // Save custom profile fields data from csv file.
            profile_save_data($existinguser);

            if ($formdata->createpassword) {
                $usernew = $DB->get_record('user', array('id' => $user->id));
                setnew_password_and_mail($usernew);
                unset_user_preference('create_password', $usernew);
                set_user_preference('auth_forcepasswordchange', 1, $usernew);
            }
            if ($user->force_password_change == 1) {
                set_user_preference('auth_forcepasswordchange', $user->force_password_change, $user->id);
            } else if ($user->force_password_change == 0) {
                set_user_preference('auth_forcepasswordchange', 0, $user->id);
            }
            $this->updatedcount++;
        }
    } // end of  update_row method

    //validation for mandatory missing fields
    public function mandatory_field_validation($user, $field)
    {
        if (empty(trim($user->$field))) {
            $strings = new stdClass;
            $strings->field = $field;
            $strings->linenumber = $this->excel_line_number;
            $missingstring = get_string('missing', 'mod_pokcertificate', $strings);
            echo '<div class=local_users_sync_error>' . $missingstring . '</div>';
            $this->errors[] = $missingstring;
            $this->mfields[] = $field;
            $this->errorcount++;
        }
    } //end of mandatory_field_validation

    //validation for timezone
    public function timezonevalidations($excel)
    {
        $strings = new stdClass;
        $strings->learner_id = $excel->employee_code;
        $strings->excel_line_number = $this->excel_line_number;
        if (!array_key_exists($excel->timezone, $this->timezones)) {
            echo '<div class=local_users_sync_error>' . get_string('invalidtimezone', 'mod_pokcertificate', $strings) . '</div>';
            $this->errors[] = get_string('invalidtimezone', 'mod_pokcertificate', $strings);
            $this->mfields[] = 'usercategory';
            $this->errorcount++;
        }
    } //end of timezonevalidations
    

    //validation for employee status
    public function employee_status_validation($excel)
    {
        $strings = new stdClass;
        $strings->learner_id = $excel->employee_code;
        $strings->excel_line_number = $this->excel_line_number;
        $employee_status = $excel->employee_status;
        $this->deletestatus = 0;
        if (array_key_exists('employee_status',(array)$excel)) {
            if (strtolower($excel->employee_status) == 'active') {
                $this->activestatus = 0;
            } else if (strtolower($excel->employee_status) == 'inactive') {
                $this->activestatus = 1;
            } else if (strtolower($excel->employee_status) == 'delete') {
                $this->deletestatus = 1;
            } else if($this->mandatory_field_count == 0){
                $strings = new stdClass;
                $strings->line = $this->excel_line_number;
                echo '<div class=local_users_sync_error>' . get_string('statusvalidation', 'mod_pokcertificate', $strings) . '</div>';
                $this->errors[] = get_string('statusvalidation', 'mod_pokcertificate', $strings);
                $this->mfields[] = $excel->employee_status;
                $this->errorcount++;
            }
        } else {
            $this->activestatus = 0;
            // echo '<div class=local_users_sync_error>Error in arrangement of columns in uploaded excelsheet at line
            //  ' . $this->excel_line_number . '</div>';
            // $this->errormessage = get_string('columnsarragement_error', 'mod_pokcertificate', $excel);
            // $this->errorcount++;
        }
    } // end of  employee_status_validation

    public function empid_validation($excel)
    {
        global $DB;
        $strings = new stdClass();
        $strings->learner_id = $excel->employee_code;
        $strings->excel_line_number = $this->excel_line_number;
        $this->learner_id = $excel->employee_code;

        if (preg_match('/[^a-z0-9 ]+/i', $excel->employee_code)) {
            echo '<div class="local_users_sync_error">' . get_string(
                'employeeid_nospecialcharacters',
                'mod_pokcertificate',
                $strings
            ) . '</div>';
            $this->errors[] = get_string('employeeid_nospecialcharacters', 'mod_pokcertificate', $strings);
            $this->mfields[] = "useremployeeid";
            $this->errorcount++;
        }
    }
    public function emailid_validation($excel)
    {
        global $DB;
        $strings = new StdClass();
        $strings->employee_id = $excel->employee_code;
        $strings->excel_line_number = $this->excel_line_number;
        $this->email = $excel->email;
        if (!validate_email($excel->email)) {
            echo '<div class="local_users_sync_error">' . get_string('invalidemail_msg', 'mod_pokcertificate', $strings) . '</div>';
            $this->errors[] = get_string('invalidemail_msg', 'mod_pokcertificate', $strings);
            $this->mfields[] = 'email';
            $this->errorcount++;
        }
    }

    /**
     * [force_password_change_validation description]
     * @param  [type] $excel [description]
     */
    private function force_password_change_validation($excel)
    {
        $this->force_password_change = $excel->force_password_change;
        if (!is_numeric($this->force_password_change) || !(($this->force_password_change == 1) ||
            ($this->force_password_change == 0))) {
            echo '<div class=local_users_sync_error>force_password_change column should have value as 0 or 1 at line
             ' . $this->excel_line_number . '</div>';
            $this->errors[] = 'force_password_change column should value as 0 or 1 at line ' . $this->excel_line_number . '';
            $this->mfields[] = 'force_password_change';
            $this->errorcount++;
        }
    }

    /*public function write_warnings_db($excel)
    {
        global $DB, $USER;
        if (!empty($this->warnings) && !empty($this->wmfields)) {
            $syncwarnings = new \stdclass();
            $today = \local_costcenter\lib::get_userdate('Y-m-d');
            $syncwarnings->date_created = strtotime($today);
            $werrors_list = implode(',', $this->warnings);
            $wmandatory_list = implode(',', $this->wmfields);
            $syncwarnings->error = $werrors_list;
            $syncwarnings->modified_by = $USER->id;
            $syncwarnings->mandatory_fields = $wmandatory_list;
            if (empty($excel->email)) {
                $syncwarnings->email = '-';
            } else {
                $syncwarnings->email = $excel->email;
            }
            if (empty($excel->employee_code)) {
                $syncwarnings->idnumber = '-';
            } else {
                $syncwarnings->idnumber = $excel->employee_code;
            }
            $syncwarnings->firstname = $excel->first_name;
            $syncwarnings->lastname = $excel->last_name;
            $syncwarnings->type = 'Warning';
            $DB->insert_record('local_syncerrors', $syncwarnings);
        }
    } // end of write_warning_db method*/

    /*private function write_error_in_db($excel)
    {
        global $DB, $USER;
        //condition to hold the sync errors
        $syncerrors = new \stdclass();
        $today = \local_costcenter\lib::get_userdate('Y-m-d');
        $syncerrors->date_created = time();
        $errors_list = implode(',', $this->errors);
        $mandatory_list = implode(',', $this->mfields);
        $syncerrors->error = $errors_list;
        $syncerrors->modified_by = $USER->id;
        $syncerrors->mandatory_fields = $mandatory_list;
        if (empty($excel->email)) {
            $syncerrors->email = '-';
        } else {
            $syncerrors->email = $excel->email;
        }
        if (empty($excel->employee_code)) {
            $syncerrors->idnumber = '-';
        } else {
            $syncerrors->idnumber = $excel->employee_code;
        }
        $syncerrors->firstname = $excel->first_name;
        $syncerrors->lastname = $excel->first_name;
        $syncerrors->sync_file_name = "Employee";
        $DB->insert_record('local_syncerrors', $syncerrors);
    } // end of write_error_db method*/

} //end of class

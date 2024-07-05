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

namespace mod_pokcertificate;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use mod_pokcertificate\pok;
use mod_pokcertificate\persistent\pokcertificate;
use mod_pokcertificate\persistent\pokcertificate_fieldmapping;
use mod_pokcertificate\persistent\pokcertificate_templates;
use mod_pokcertificate\persistent\pokcertificate_issues;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');


/**
 * Class helper
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Given an api key, it returns true or false if api key is valid.
     *
     * @param  string $key authentication API key
     *
     * @return bool
     */
    public static function pokcertificate_validate_apikey($key) {

        $location = API_KEYS_ROOT . '/me';
        $params = '';
        self::set_pokcertificate_settings();
        $curl = new \curl();
        $options = [
            'CURLOPT_HTTPHEADER' => [
                'Authorization: ApiKey ' . $key,
            ],
            'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_ENCODING' => '',
            'CURLOPT_CUSTOMREQUEST' => 'GET',
            'CURLOPT_SSL_VERIFYPEER' => false,
        ];
        $result = $curl->post($location, $params, $options);

        if ($curl->get_errno()) {
            throw new \moodle_exception('connecterror', 'mod_pokcertificate', '', ['url' => $location]);
        }
        if ($curl->get_info()['http_code'] == 200) {
            $result = json_decode($result);
            if (isset($result->org)) {
                set_config('pokverified', true, 'mod_pokcertificate');
                set_config('wallet', $result->org, 'mod_pokcertificate');
                set_config('authenticationtoken', $key, 'mod_pokcertificate');
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Set default configuration settings for the POK certificate module.
     *
     * This public static function initializes the default configuration settings for the POK certificate module.
     *
     * @return void
     */
    public static function set_pokcertificate_settings() {
        set_config('pokverified', false, 'mod_pokcertificate');
        set_config('wallet', '', 'mod_pokcertificate');
        set_config('authenticationtoken', '', 'mod_pokcertificate');
        set_config('orgid', '', 'mod_pokcertificate');
        set_config('institution', '', 'mod_pokcertificate');
        set_config('availablecertificate', '', 'mod_pokcertificate');
        set_config('pendingcertificates', '0', 'mod_pokcertificate');
        set_config('issuedcertificates', '', 'mod_pokcertificate');
    }

    /**
     * Get mapped fields for a given certificate ID.
     *
     * This public static function retrieves the field mappings for a given certificate ID.
     *
     * @param int $pokid The ID of the certificate.
     * @return stdClass An object containing the mapped fields.
     */
    public static function get_mapped_fields(int $pokid) {

        $fields = pokcertificate_fieldmapping::fieldmapping_records(['pokid' => $pokid], 'id');
        $data = new \stdClass;
        $i = 0;
        if (count($fields) > 0) {

            foreach ($fields as $field) {
                if ($i < count($fields)) {
                    $templatefield = 'templatefield_' . $i;
                    $userfield = 'userfield_' . $i;
                    $data->$templatefield = $field->templatefield;
                    $data->$userfield = $field->userfield;
                    $i++;
                }
            }
        }
        return $data;
    }

    /**
     * Get a list of internal user fields.
     *
     * This public static function retrieves a list of internal user fields from the 'user' table
     * and combines them with custom profile fields. Only valid fields are included
     * in the final list.
     *
     * @return array An associative array of local fields where the key is the field name and the value is the field label.
     */
    public static function get_internalfield_list() {
        global $DB;
        $usercolumns = $DB->get_columns('user');
        $localfields = [];
        $validfields = [
            'firstname', 'lastname',
            'idnumber', 'email', 'phone1', 'department',
            'city', 'country',
        ];
        foreach ((array)$usercolumns as $key => $field) {
            if (in_array($key, $validfields)) {
                $localfields[$key] = $field->name;
            }
        }

        $allcustomfields = profile_get_custom_fields();
        $customfields = array_combine(array_column($allcustomfields, 'shortname'), $allcustomfields);
        foreach ((array)$customfields as $key => $field) {
            $localfields['profile_field_' . $key] = $field->shortname;
        }
        return $localfields;
    }

    /** Get all template definition custom fields
     *
     * @param string $template
     * @param int $pokid
     * @return array
     */
    public static function get_externalfield_list($template, $pokid) {
        $templatefields = [];
        if (isset($template) && !empty($template)) {
            $template = base64_decode($template);
            $templatedefinition = pokcertificate_templates::get_field(
                'templatedefinition',
                ['pokid' => $pokid, 'templatename' => $template]
            );
            $templatedefinition = json_decode($templatedefinition);
            if ($templatedefinition) {
                foreach ($templatedefinition->params as $param) {
                    $pos = strpos($param->name, 'custom:');
                    if ($pos !== false) {
                        $var = substr($param->name, strrpos($param->name, ':') + 1);
                        if ($var) {
                            $templatefields[$var] = $var;
                        }
                    }
                }
            }
        }
        return $templatefields;
    }

    /** Get all template definition madatory fields
     *
     * @param string $template
     * @param int $pokid
     * @return array
     */
    public static function get_mandatoryfield_list($template, $pokid) {
        $mandatoryfields = [];
        if (isset($template) && !empty($template)) {
            $template = base64_decode($template);
            $templatedefinition = pokcertificate_templates::get_field(
                'templatedefinition',
                ['pokid' => $pokid, 'templatename' => $template]
            );
            $templatedefinition = json_decode($templatedefinition);
            if ($templatedefinition) {
                foreach ($templatedefinition->params as $param) {
                    $pos = strpos($param->name, ':');
                    if ($pos === false && in_array($param->name, ['date', 'title', 'institution', 'achiever'])) {
                        $mandatoryfields[$param->name] = $param->name;
                    }
                }
            }
        }
        return $mandatoryfields;
    }

    /**
     * Retrieve a list of incomplete student profiles.
     *
     * This public static function retrieves a list of student profiles from the database where the profiles are
     * considered incomplete. It filters users based on the provided student ID (if any) and prepares
     * the data for displaying in a list format.
     *
     * @param string|null $studentid The student ID to search for (optional). *
     * @param string $studentname The student name to search for (optional).
     * @param string $email The student email to search for (optional).
     * @param int $perpage The number of records to display per page.
     * @param int $offset The offset for pagination.
     * @return array An associative array containing the total count of records and the formatted student profile data.
     */
    public static function pokcertificate_incompletestudentprofilelist(
        $studentid = '',
        $studentname = '',
        $email = '',
        $perpage = 10,
        $offset = 0
    ) {
        global $DB;

        $countsql = "SELECT count(DISTINCT(u.id)) ";
        $selectsql = "SELECT DISTINCT(u.id),u.* ";
        $fromsql = "FROM {user} u ";
        $joinsql = " LEFT JOIN {user_info_data} d ON d.userid = u.id
                    LEFT JOIN {user_info_field} f ON d.fieldid = f.id ";
        $wheresql = " WHERE u.deleted = 0
                     AND u.suspended = 0
                     AND u.id > 2 ";
        $wheresql .= " AND (
                        EXISTS (
                            SELECT 1 FROM mdl_user_info_field LIMIT 1
                        )
                        AND (
                            u.idnumber IS NULL
                            OR u.idnumber = ''
                            OR d.data IS NULL
                            OR d.data = ''
                        )
                        OR NOT EXISTS (
                            SELECT 1 FROM mdl_user_info_field LIMIT 1
                        )
                        AND (
                            u.idnumber IS NULL
                            OR u.idnumber = ''
                        )
                    )";

        $queryparam = [];
        $conditions = [];
        if (!empty(trim($studentid))) {
            $conditions[] = $DB->sql_like('u.idnumber', ':idnumber', false, false);
            $queryparam['idnumber'] = $DB->sql_like_escape($studentid) . '%';
        }
        if (!empty(trim($studentname))) {
            $conditions[] = $DB->sql_like('u.firstname', ':firstname', false, false);
            $queryparam['firstname'] = $DB->sql_like_escape($studentname) . '%';
        }
        if (!empty(trim($email))) {
            $conditions[] = $DB->sql_like('u.email', ':email', false, false);
            $queryparam['email'] = $DB->sql_like_escape($email) . '%';
        }
        if (!empty($conditions)) {
            $wheresql .= " AND " . implode(' AND ', $conditions);
        }
        $count = $DB->count_records_sql($countsql . $fromsql . $joinsql . $wheresql, $queryparam);
        $users = $DB->get_records_sql($selectsql . $fromsql . $joinsql . $wheresql, $queryparam, $offset, $perpage);

        $languages = get_string_manager()->get_list_of_languages();
        $list = [];
        $data = [];
        if ($users) {
            foreach ($users as $user) {
                $user = \core_user::get_user($user->id);

                $list = [];
                $list['id'] = $user->id;
                $list['firstname'] = $user->firstname;
                $list['lastname'] = $user->lastname;
                $list['email'] = $user->email;
                $list['studentid'] = $user->idnumber ? $user->idnumber : '-';
                $list['language'] = $languages[$user->lang];
                $data[] = $list;
            }
        }
        return ['count' => $count, 'data' => $data];
    }

    /**
     * Retrieve a list of course participants with relevant details.
     *
     * This public static function retrieves a list of course participants from the database based on the provided parameters,
     * such as course ID, student ID, completion status, etc. It prepares the data with relevant information
     * for displaying in the course participants list.
     *
     * @param int $courseid The ID of the course to retrieve participants from.
     * @param int $studentid The student ID to search for (optional).
     * @param string $studentname The student name to search for (optional).
     * @param string $email The student email to search for (optional).
     * @param string $senttopok Indicates whether certificates have been sent to Pokcertificate (optional).
     * @param string $coursestatus The completion status of the course (optional).
     * @param int $perpage The number of records to display per page.
     * @param int $offset The offset for pagination.
     * @return array An associative array containing the total count of records and the formatted participant data.
     */
    public static function pokcertificate_coursecertificatestatuslist(
        $courseid,
        $studentid = '',
        $studentname = '',
        $email = '',
        $senttopok = '',
        $coursestatus = '',
        $perpage = 10,
        $offset = 0
    ) {
        global $DB;
        $pokmoduleid = $DB->get_field('modules', 'id', ['name' => 'pokcertificate']);
        $countsql = "SELECT count(ra.id) ";
        $selectsql = "SELECT UUID(),
                    pc.name as activity,
                    u.id as userid,
                    u.firstname,
                    u.lastname,
                    u.idnumber,
                    u.email,
                    cc.timecompleted as completiondate,
                    pct.templatetype,
                    pci.status,
                    pci.pokcertificateid ,
                    pci.certificateurl ";
        $fromsql = "FROM {" . pokcertificate::TABLE . "} pc
                JOIN {course_modules} cm ON pc.id = cm.instance AND deletioninprogress = 0
                JOIN {context} ctx ON (pc.course = ctx.instanceid AND ctx.contextlevel = " . CONTEXT_COURSE . ")
                JOIN {role_assignments} ra ON ctx.id = ra.contextid
                JOIN {role} r ON (ra.roleid = r.id AND r.shortname IN ('student','employee') )
                JOIN {user} u ON ra.userid = u.id AND u.deleted = 0 AND u.suspended = 0
           LEFT JOIN {course_completions} cc ON (u.id = cc.userid AND pc.course = cc.course)
           LEFT JOIN {" . pokcertificate_templates::TABLE . "} pct ON pc.templateid = pct.id
           LEFT JOIN {" . pokcertificate_issues::TABLE . "} pci ON (u.id = pci.userid AND pc.id = pci.pokid)
               WHERE pc.course = :courseid
                     AND cm.deletioninprogress = 0
                     AND cm.module = :pokmoduleid ";

        $queryparam = [];
        $queryparam['courseid'] = $courseid;
        $queryparam['pokmoduleid'] = $pokmoduleid;

        $conditions = [];
        if (!empty(trim($studentid))) {
            $conditions[] = $DB->sql_like('u.idnumber', ':idnumber', false, false);
            $queryparam['idnumber'] = $DB->sql_like_escape($studentid) . '%';
        }
        if (!empty(trim($studentname))) {
            $conditions[] = $DB->sql_like('u.firstname', ':firstname', false, false);
            $queryparam['firstname'] = $DB->sql_like_escape($studentname) . '%';
        }
        if (!empty(trim($email))) {
            $conditions[] = $DB->sql_like('u.email', ':email', false, false);
            $queryparam['email'] = $DB->sql_like_escape($email) . '%';
        }
        if (!empty($conditions)) {
            $fromsql .= " AND " . implode(' AND ', $conditions);
        }

        if ($coursestatus == 'completed') {
            $fromsql .= "AND cc.timecompleted > 0 ";
        }

        if ($coursestatus == 'inprogress') {
            $fromsql .= "AND (cc.timecompleted = 0 OR cc.timecompleted IS NULL) ";
        }

        if ($senttopok == 'yes') {
            $fromsql .= "AND ((pci.pokcertificateid IS NOT NULL AND pci.pokcertificateid != '') OR
                        (pci.certificateurl IS NULL aND pci.certificateurl = '')) ";
        }

        if ($senttopok == 'no') {
            $fromsql .= "AND (pci.id IS NULL ) ";
        }

        $concatsql = "ORDER BY ra.id DESC ";
        $totalusers = $DB->count_records_sql($countsql . $fromsql, $queryparam);
        $certificates = $DB->get_records_sql($selectsql . $fromsql . $concatsql, $queryparam, $offset, $perpage);

        $list = [];
        $data = [];
        $showtemplatetype = false;
        if ($certificates) {
            foreach ($certificates as $c) {
                $list = [];
                $list['activity'] = $c->activity;
                $list['firstname'] = $c->firstname;
                $list['lastname'] = $c->lastname;
                $list['email'] = $c->email;
                $list['studentid'] = $c->idnumber ? $c->idnumber : '-';
                $list['enrolldate'] = pokcertificate_courseenrollmentdate($courseid, $c->userid);
                $list['completedate'] = $c->completiondate ? date('d M Y', $c->completiondate) : '-';
                $list['coursestatus'] = $c->completiondate ?
                    get_string('completed') : get_string('inprogress', 'mod_pokcertificate');
                if ($c->templatetype != '') {
                    $showtemplatetype = true;
                    $list['certificatetype'] = ($c->templatetype === '0') ? 'Free' : 'Paid';
                } else {
                    $list['certificatetype'] = '-';
                }

                $list['status'] = ($c->status || !empty($c->pokcertificateid)) ? true : false;
                $list['senttopok'] = $list['status'] ? get_string('yes') : get_string('no');
                $list['certificateurl'] = $c->certificateurl;
                $data[] = $list;
            }
        }

        return [
            'count' => $totalusers,
            'data' => $data,
            'showtemplatetype' => $showtemplatetype,
        ];
    }

    /**
     * Retrieve a list of users for awarding general certificates.
     *
     * This public static function retrieves a list of users from the database based on the provided parameters,
     * such as student ID, pagination settings, and offset. It prepares the data for awarding general certificates
     * by selecting relevant user information and formatting it appropriately.
     *
     * @param int $courseid The ID of the course to retrieve participants from.
     * @param int $studentid The student ID to search for (optional).
     * @param string $studentname The student name to search for (optional).
     * @param string $email The student email to search for (optional).
     * @param string $certificatestatus The certificate status of the POK certificate (optional).
     * @param int $perpage The number of records to display per page.
     * @param int $offset The offset for pagination.
     * @return array An associative array containing the total count of records and the formatted user data.
     */
    public static function pokcertificate_awardgeneralcertificatelist(
        $course,
        $courseid,
        $studentid,
        $studentname,
        $email,
        $certificatestatus,
        $perpage,
        $offset
    ) {
        global $DB;

        $pokmoduleid = $DB->get_field('modules', 'id', ['name' => 'pokcertificate']);
        $countsql = "SELECT count(DISTINCT(CONCAT(pc.id,u.id,c.id)) )";
        $selectsql = "SELECT DISTINCT(CONCAT(pc.id,u.id,c.id)),
                         pc.id as activityid,
                         pc.name as activity,
                         pc.templateid,
                         u.id as userid,
                         u.firstname,
                         u.idnumber,
                         u.lastname,
                         u.email,
                         cc.timecompleted as completiondate,
                         pct.templatetype,
                         pci.status,
                         pci.timecreated as issueddate,
                         pci.pokcertificateid ,
                         pci.certificateurl ,
                         c.id as courseid,
                         c.fullname AS coursename ";
        $fromsql = "FROM {" . pokcertificate::TABLE . "} pc
                JOIN {course_modules} cm ON pc.id = cm.instance AND deletioninprogress = 0
                JOIN {context} ctx ON (pc.course = ctx.instanceid AND ctx.contextlevel = " . CONTEXT_COURSE . ")
                JOIN {course} c ON ctx.instanceid = c.id
                JOIN {role_assignments} ra ON ctx.id = ra.contextid
                JOIN {role} r ON ra.roleid = r.id
                JOIN {user} u ON ra.userid = u.id AND u.deleted = 0 AND u.suspended = 0
           LEFT JOIN {course_completions} cc ON (u.id = cc.userid AND pc.course = cc.course)
           LEFT JOIN {" . pokcertificate_templates::TABLE . "} pct ON pc.templateid = pct.id
           LEFT JOIN {" . pokcertificate_issues::TABLE . "} pci ON (u.id = pci.userid AND pc.id = pci.pokid)
               WHERE ctx.contextlevel = 50
               AND r.shortname IN ('student','employee') AND pc.templateid != 0 ";

        $queryparam = [];
        $queryparam['courseid'] = $courseid;
        $queryparam['pokmoduleid'] = $pokmoduleid;

        $conditions = [];
        if (!empty(trim($studentid))) {
            $conditions[] = $DB->sql_like('u.idnumber', ':idnumber', false, false);
            $queryparam['idnumber'] = $DB->sql_like_escape($studentid) . '%';
        }
        if (!empty(trim($studentname))) {
            $conditions[] = $DB->sql_like('u.firstname', ':firstname', false, false);
            $queryparam['firstname'] = $DB->sql_like_escape($studentname) . '%';
        }
        if (!empty(trim($email))) {
            $conditions[] = $DB->sql_like('u.email', ':email', false, false);
            $queryparam['email'] = $DB->sql_like_escape($email) . '%';
        }

        if (!empty($conditions)) {
            $fromsql .= " AND " . implode(' AND ', $conditions);
        }

        if ($courseid != 0 || $course != 0) {
            $fromsql .= "AND c.id = :courseid ";
            $queryparam['courseid'] = ($courseid) ? $courseid : $course;
        }

        if ($certificatestatus == 'completed') {
            $fromsql .= "AND (pci.status = 1 AND (pci.certificateurl IS NOT NULL OR pci.certificateurl != '')) ";
        }

        if ($certificatestatus == 'inprogress') {
            $fromsql .= "AND (pci.status = 0 AND (pci.pokcertificateid IS NOT NULL OR pci.pokcertificateid != '')
                    AND (pci.certificateurl IS NULL OR pci.certificateurl = '')) ";
        }

        if ($certificatestatus == 'notissued') {
            $fromsql .= "AND (pci.id IS NULL ) ";
        }

        $fromsql .= "ORDER BY pc.id DESC ";

        $count = $DB->count_records_sql($countsql . $fromsql, $queryparam);
        $records = $DB->get_records_sql($selectsql . $fromsql, $queryparam, $offset, $perpage);

        $list = [];
        $data = [];
        if ($records) {
            foreach ($records as $c) {
                $incomplete = false;
                $poktemplate = pokcertificate_templates::get_record(['id' => $c->templateid]);
                $templatename = base64_encode($poktemplate->get('templatename'));
                $externalfields = self::get_externalfield_list($templatename, $c->activityid);
                if (!empty($externalfields)) {
                    $pokid = $c->activityid;
                    $pokfields = $DB->get_fieldset_sql(
                        "SELECT templatefield
                                    from {" . pokcertificate_fieldmapping::TABLE . "} WHERE pokid = :pokid",
                        ['pokid' => $pokid]
                    );

                    foreach ($externalfields as $key => $value) {
                        if (!in_array($key, $pokfields)) {
                            $incomplete = true;
                            break;
                        }
                    }
                }
                if ($incomplete) {
                    continue;
                }
                if ($c->status == 0 && !empty($c->pokcertificateid)) {
                    $certstatus = get_string('inprogress', 'mod_pokcertificate');
                } else if ($c->status == 1 && !empty($c->certificateurl)) {
                    $certstatus = get_string('completed');
                } else {
                    $certstatus = "-";
                }
                $list = [];
                $list['userid'] = $c->userid;
                $list['firstname'] = $c->firstname;
                $list['lastname'] = $c->lastname;
                $list['email'] = $c->email;
                $list['studentid'] = $c->idnumber ? $c->idnumber : '-';
                $list['activity'] = $c->activity;
                $list['activityid'] = $c->activityid;
                $list['courseid'] = $c->courseid;
                $list['course'] = $c->coursename;
                $list['issueddate'] = $c->issueddate ? date('d M Y', $c->issueddate) : '-';
                $list['status'] = ($c->status || !empty($c->pokcertificateid)) ? true : false;
                $list['completedate'] = $c->completiondate ? date('d M Y', $c->completiondate) : '-';
                $list['certificatestatus'] = $certstatus;
                $list['certificateurl'] = $c->certificateurl;
                $list['userinputids'] = base64_encode(serialize([$c->courseid . '_' . $c->activityid . '_' . $c->userid]));
                $data[] = $list;
            }
        }
        return ['count' => $count, 'data' => $data, 'courseid' => $courseid];
    }

    /**
     * Display the certificate preview to user or redirect the user.
     *
     * @param  object $cm
     * @param  object $pokcertificate
     * @param  bool $flag
     *
     * @return [array]
     */
    public static function pokcertificate_preview_by_user($cm, $pokcertificate, $flag) {
        global $USER;
        $id = $cm->id;
        $context = \context_module::instance($cm->id);
        $url = '';
        $adminview = false;
        $studentview = false;
        // Getting certificate template view for admin.
        if (has_capability('mod/pokcertificate:manageinstance', $context)) {
            $preview = pok::preview_template($id);
            if ($preview) {
                $adminview = true;
                $params = ['id' => $id];
                $url = new moodle_url('/mod/pokcertificate/preview.php', $params);
            }
        } else {
            // Getting certificate template view for student.
            $certificateissued = pokcertificate_issues::get_record(['pokid' => $pokcertificate->id, 'userid' => $USER->id]);

            if ($flag || ($certificateissued && !empty($certificateissued->get('pokcertificateid')))) {
                $studentview = true;
            } else {
                $params = ['cmid' => $id, 'id' => $USER->id];
                $url = new moodle_url('/mod/pokcertificate/updateprofile.php', $params);
            }
        }
        return ['student' => $studentview, 'admin' => $adminview, 'url' => $url];
    }

    /**
     * check if user has mapped field data to issue certificate
     *
     * @param  object $cm - course module info
     * @param  object $user - user object
     * @return bool
     */
    public static function check_usermapped_fielddata($cm, $user) {
        $validuser = true;

        $pokfields = pok::get_mapping_fields($user, $cm);
        $mandatoryfields = ['firstname', 'lastname', 'email', 'idnumber'];
        foreach ($mandatoryfields as $fullname) {
            if (empty($user->$fullname)) {
                $validuser = false;
            }
        }

        if (!empty($pokfields)) {
            foreach ($pokfields as $field) {
                $fieldname = $field->get('userfield');
                if (isset($fieldname) && (!in_array($fieldname, ['id']) && strpos($fieldname, 'profile_field_') !== false)) {
                    $userprofilefield = substr($fieldname, strlen('profile_field_'));
                    if (
                        isset($user->profile[$userprofilefield]) &&
                        empty(trim($user->profile[$userprofilefield]))
                    ) {
                        $validuser = false;
                    }
                } else {
                    if (empty(trim($user->$fieldname))) {
                        $validuser = false;
                    }
                }
            }
        }

        return $validuser;
    }

    /**
     * validate encoded data senet from url
     *
     * @param  string $input
     * @return bool
     */
    public static function validate_encoded_data($input) {

        // By default PHP will ignore “bad” characters, so we need to enable the “$strict” mode.
        $str = base64_decode($input, true);

        // If $input cannot be decoded the $str will be a Boolean “FALSE”.
        if ($str === false) {
            return false;
        } else {
            // Even if $str is not FALSE, this does not mean that the input is valid.
            // This is why now we should encode the decoded string and check it against input.
            $b64 = base64_encode($str);

            // Finally, check if input string and real Base64 are identical.
            if ($input === $b64) {
                return true;;
            } else {
                return false;
            }
        }
    }

    /**
     * get users data before issueing general certificate to update the incomplete profiles
     *
     * @param array $useractivityids
     * @return array An array containing validation results.
     */

    public static function pokcertificate_userslist($useractivityids) {
        $languages = get_string_manager()->get_list_of_languages();
        $list = [];
        $data = [];
        $status = [];

        if ($useractivityids) {
            foreach ($useractivityids as $rec) {
                $rec = unserialize(base64_decode($rec));
                $rec = array_shift($rec);
                $inp = explode("_", $rec);

                $course = get_course($inp[0]);
                $activityid = $inp[1];
                $user = $inp[2];
                $user = \core_user::get_user($user);
                profile_load_custom_fields($user);
                $cm = get_coursemodule_from_instance('pokcertificate', $activityid);
                $validuser = self::check_usermapped_fielddata($cm, $user);

                $pokcertificate = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);

                $list = [];
                $list['userid'] = $user->id;
                $list['cmid'] = $cm->id;
                $list['courseid'] = $cm->course;
                $list['firstname'] = $user->firstname;
                $list['lastname'] = $user->lastname;
                $list['email'] = $user->email;
                $list['activityname'] = $pokcertificate->get('name');
                $list['coursename'] = $course->fullname;;
                $list['studentid'] = $user->idnumber ? $user->idnumber : '-';
                $list['language'] = $languages[$user->lang];
                $list['status'] = ($validuser) ?
                    get_string('complete', 'mod_pokcertificate') : get_string('incomplete', 'mod_pokcertificate');
                $list['validuser'] = $validuser;
                $list['username'] = \html_writer::link(
                    new \moodle_url('/user/profile.php', ['id' => $user->id]),
                    $list['userid']
                );
                $status[] = $list['status'];
                if (!is_siteadmin()) {
                    if ($validuser) {
                        $data[] = $list;
                    }
                } else {
                    $data[] = $list;
                }
            }
        }
        $showbutton = 'disabled';
        if (in_array(get_string('complete', 'mod_pokcertificate'), $status)) {
            $showbutton = 'enabled';
        }
        return ['data' => $data, 'showbutton' => $showbutton];
    }

    /**
     * Validate user inputs public static function.
     *
     * @param array $selecteditems An encoded string containing courseid,activityid,userid for selected items to validate.
     * @return array An array containing validation results.
     */
    public static function validate_userinputs($selecteditems) {
        global $DB;

        foreach ($selecteditems as $item) {
            if (!self::validate_encoded_data($item)) {
                throw new \moodle_exception('invalidinputs', 'pokcertificate');
            }

            $item = unserialize(base64_decode($item));
            $item = array_shift($item);
            $inp = explode("_", $item);

            if (isset($inp[0]) && !empty($inp[0])) {
                $courseid = $inp[0];
                if (!$course = $DB->record_exists('course', ['id' => $courseid])) {
                    throw new \moodle_exception('invalidcourse');
                }
            } else {
                throw new \moodle_exception('invalidcourse');
            }
            if (isset($inp[1]) && !empty($inp[1])) {
                $activityid = (int)$inp[1];
                if (!$cm = get_coursemodule_from_instance('pokcertificate', $activityid)) {
                    throw new \moodle_exception('invalidcoursemodule');
                }
            } else {
                throw new \moodle_exception('invalidcoursemodule');
            }

            if (isset($inp[2]) && !empty($inp[2])) {
                $user = $inp[2];
                $courseid = $inp[0];
                $user = \core_user::get_user($user);
                $context = \context_course::instance($courseid);
                if (!is_enrolled($context, $user->id, '', true)) {
                    $course = get_course($courseid);
                    $courseshortname = format_string(
                        $course->shortname,
                        true,
                        ['context' => $context]
                    );
                    mtrace(fullname($user) . ' not an active participant in ' . $courseshortname);
                    throw new \moodle_exception('invaliduser');
                }
            } else {
                throw new \moodle_exception('invaliduser');
            }
        }
        return true;
    }
}

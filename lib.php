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
 * librery file
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use mod_pokcertificate\pok;
use mod_pokcertificate\persistent\pokcertificate_fieldmapping;
use mod_pokcertificate\persistent\pokcertificate_templates;
use mod_pokcertificate\persistent\pokcertificate_issues;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');
/**
 * List of features supported in Page module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function pokcertificate_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;

        default:
            return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function pokcertificate_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return [];
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function pokcertificate_get_view_actions() {
    return ['view', 'view all'];
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function pokcertificate_get_post_actions() {
    return ['update', 'add'];
}

/**
 * Add pokcertificate instance.
 * @param stdClass $data
 * @param mod_pokcertificate_mod_form $mform
 * @return int new pokcertificate instance id
 */
function pokcertificate_add_instance($data, $mform = null) {
    $data = pok::save_pokcertificate_instance($data, $mform);
    return $data->id;
}

/**
 * Update pokcertificate instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function pokcertificate_update_instance($data, $mform) {
    $data = pok::update_pokcertificate_instance($data, $mform);
    return true;
}

/**
 * Delete pokcertificate instance.
 * @param int $id
 * @return bool true
 */
function pokcertificate_delete_instance($id) {
    pok::delete_pokcertificate_instance($id);
    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {course_modinfo::get_array_of_activities()}
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info Info to customise main pokcertificate display
 */
function pokcertificate_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if (!$pokcertificate = $DB->get_record(
        'pokcertificate',
        ['id' => $coursemodule->instance],
        'id, name, display, displayoptions, intro, introformat'
    )) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $pokcertificate->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('pokcertificate', $pokcertificate, $coursemodule->id, false);
    }

    if ($pokcertificate->display != RESOURCELIB_DISPLAY_POPUP) {
        return $info;
    }

    $fullurl = "$CFG->wwwroot/mod/pokcertificate/view.php?id=$coursemodule->id&amp;inpopup=1&amp;formedit=1";
    $options = empty($pokcertificate->displayoptions) ? [] : (array) unserialize_array($pokcertificate->displayoptions);
    $width  = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
    $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
    $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,";
    $wh .= "directories=no,scrollbars=yes,resizable=yes";
    $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    return $info;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function pokcertificate_dndupload_register() {
    return [
        'types' => [
            [
                'identifier' => 'text/html',
                'message' => get_string('createpokcertificate', 'pokcertificate'),
            ],
            [
                'identifier' => 'text',
                'message' => get_string('createpokcertificate', 'pokcertificate'),
            ],
        ],
    ];
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function pokcertificate_dndupload_handle($uploadinfo) {
    // Gather the required info.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '<p>' . $uploadinfo->displayname . '</p>';
    $data->introformat = FORMAT_HTML;
    if ($uploadinfo->type == 'text/html') {
        $data->contentformat = FORMAT_HTML;
        $data->content = clean_param($uploadinfo->content, PARAM_CLEANHTML);
    } else {
        $data->contentformat = FORMAT_PLAIN;
        $data->content = clean_param($uploadinfo->content, PARAM_TEXT);
    }
    $data->coursemodule = $uploadinfo->coursemodule;

    // Set the display options to the site defaults.
    $config = get_config('pokcertificate');
    $data->display = $config->display;
    $data->popupheight = $config->popupheight;
    $data->popupwidth = $config->popupwidth;
    $data->printintro = $config->printintro;
    $data->printlastmodified = $config->printlastmodified;

    return pokcertificate_add_instance($data, null);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $pokcertificate       pokcertificate object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function pokcertificate_view($pokcertificate, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = [
        'context' => $context,
        'objectid' => $pokcertificate->id,
    ];

    $event = \mod_pokcertificate\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('pokcertificate', $pokcertificate);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function pokcertificate_check_updates_since(cm_info $cm, $from, $filter = []) {
    $updates = course_check_module_updates_since($cm, $from, ['content'], $filter);
    return $updates;
}

/**
 * Given an api key, it returns true or false if api key is valid.
 *
 * @param  string $key authentication API key
 *
 * @return bool
 */
function pokcertificate_validate_apikey($key) {

    $location = API_KEYS_ROOT . '/me';
    $params = '';
    set_pokcertificate_settings();
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
        throw new moodle_exception('connecterror', 'mod_pokcertificate', '', ['url' => $location]);
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
 * This function initializes the default configuration settings for the POK certificate module.
 *
 * @return void
 */
function set_pokcertificate_settings() {
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
 * This function retrieves the field mappings for a given certificate ID.
 *
 * @param int $certid The ID of the certificate.
 * @return stdClass An object containing the mapped fields.
 */
function get_mapped_fields(int $certid) {

    $fields = pokcertificate_fieldmapping::fieldmapping_records(['certid' => $certid], 'id');
    $data = new \stdClass;
    if ($fields) {
        $data->option_repeats = count($fields);
        $key = 0;

        foreach ($fields as $field) {
            $data->templatefield[$key] = $field->templatefield;
            $data->userfield[$key] = $field->userfield;
            $optionid[] = $field->id;
            $key++;
        }
        $data->optionid = $optionid;
    }
    return $data;
}

/**
 * Get a list of internal user fields.
 *
 * This function retrieves a list of internal user fields from the 'user' table
 * and combines them with custom profile fields. Only valid fields are included
 * in the final list.
 *
 * @return array An associative array of local fields where the key is the field name and the value is the field label.
 */
function get_internalfield_list() {
    global $DB;
    $usercolumns = $DB->get_columns('user');
    $localfields = [];
    $validfields = [
        'id', 'username', 'firstname', 'lastname', 'middlename',
        'idnumber', 'email', 'lang', 'phone1', 'phone2', 'department', 'institution',
        'city', 'address', 'country',
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

/** Get all template definition fields
 *
 * @param string $template
 * @param int $pokid
 * @return array
 */
function get_externalfield_list($template, $pokid) {
    $templatefields = [];
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

    return $templatefields;
}

/**
 * Retrieve a list of incomplete student profiles.
 *
 * This function retrieves a list of student profiles from the database where the profiles are
 * considered incomplete. It filters users based on the provided student ID (if any) and prepares
 * the data for displaying in a list format.
 *
 * @param string|null $studentid The student ID to search for (optional).
 * @param int $perpage The number of records to display per page.
 * @param int $offset The offset for pagination.
 * @return array An associative array containing the total count of records and the formatted student profile data.
 */
function pokcertificate_incompletestudentprofilelist($studentid, $perpage, $offset) {
    global $DB;
    $systemcontext = \context_system::instance();
    $countsql = "SELECT count(id) ";
    $selectsql = "SELECT * ";
    $fromsql = "FROM {user}
               WHERE deleted = 0
                     AND suspended = 0
                     AND id > 2 ";

    $queryparam = [];
    if ($studentid) {
        $fromsql .= "AND idnumber LIKE :studentid ";
        $queryparam['studentid'] = '%' . trim($studentid) . '%';
    }
    $count = $DB->count_records_sql($countsql . $fromsql, $queryparam);
    $records = $DB->get_records_sql($selectsql . $fromsql, $queryparam, $offset, $perpage);

    $list = [];
    $data = [];
    if ($records) {
        foreach ($records as $c) {
            $list = [];
            $list['id'] = $c->id;
            $list['firstname'] = $c->firstname;
            $list['lastname'] = $c->lastname;
            $list['email'] = $c->email;
            $list['studentid'] = $c->idnumber ? $c->idnumber : '-';
            $list['language'] = 'English';
            $data[] = $list;
        }
    }
    return ['count' => $count, 'data' => $data];
}

/**
 * Retrieve a list of course participants with relevant details.
 *
 * This function retrieves a list of course participants from the database based on the provided parameters,
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
function pokcertificate_coursecertificatestatuslist(
    $courseid,
    $studentid,
    $studentname,
    $email,
    $senttopok,
    $coursestatus,
    $perpage,
    $offset
) {
    global $DB;
    $pokmoduleid = $DB->get_field('modules', 'id', ['name' => 'pokcertificate']);
    $countsql = "SELECT count(ra.id) ";
    $selectsql = "SELECT UUID(),
                         pc.name as activity,
                         u.id as userid,
                         u.firstname,
                         u.idnumber,
                         u.email,
                         cc.timecompleted as completiondate,
                         pct.templatetype,
                         pci.certificateurl ";
    $fromsql = "FROM {pokcertificate} pc
                JOIN {course_modules} cm ON pc.id = cm.instance
                JOIN {context} ctx ON (pc.course = ctx.instanceid AND ctx.contextlevel = " . CONTEXT_COURSE . ")
                JOIN {role_assignments} ra ON ctx.id = ra.contextid
                JOIN {role} r ON (ra.roleid = r.id AND r.shortname = 'student')
                JOIN {user} u ON ra.userid = u.id
           LEFT JOIN {course_completions} cc ON (u.id = cc.userid AND pc.course = cc.course)
           LEFT JOIN {pokcertificate_templates} pct ON cm.instance = pct.pokid
           LEFT JOIN {pokcertificate_issues} pci ON (u.id = pci.userid AND pc.id = pci.certid)
               WHERE pc.course = :courseid
                     AND cm.deletioninprogress = 0
                     AND cm.module = :pokmoduleid ";

    $queryparam = [];
    $queryparam['courseid'] = $courseid;
    $queryparam['pokmoduleid'] = $pokmoduleid;
    if ($studentid) {
        $fromsql .= "AND u.idnumber LIKE :studentid ";
        $queryparam['studentid'] = '%' . trim($studentid) . '%';
    }
    if ($studentname) {
        $fromsql .= "AND u.firstname LIKE :firstname ";
        $queryparam['firstname'] = '%' . trim($studentname) . '%';
    }
    if ($email) {
        $fromsql .= "AND u.email LIKE :email ";
        $queryparam['email'] = '%' . trim($email) . '%';
    }

    if ($coursestatus == 'completed') {
        $fromsql .= "AND cc.timecompleted > 0 ";
    }

    if ($coursestatus == 'inprogress') {
        $fromsql .= "AND (cc.timecompleted = 0 OR cc.timecompleted IS NULL) ";
    }

    if ($senttopok == 'yes') {
        $fromsql .= "AND pci.certificateurl IS NOT NULL ";
    }

    if ($senttopok == 'no') {
        $fromsql .= "AND pci.certificateurl IS NULL ";
    }

    $concatsql = "ORDER BY ra.id DESC ";
    $totalusers = $DB->count_records_sql($countsql . $fromsql, $queryparam);
    $certificates = $DB->get_records_sql($selectsql . $fromsql . $concatsql, $queryparam, $offset, $perpage);

    $list = [];
    $data = [];

    if ($certificates) {
        foreach ($certificates as $c) {
            $list = [];
            $list['activity'] = $c->activity;
            $list['firstname'] = $c->firstname;
            $list['email'] = $c->email;
            $list['studentid'] = $c->idnumber ? $c->idnumber : '-';
            $list['enrolldate'] = pokcertificate_courseenrollmentdate($courseid, $c->userid);
            $list['completedate'] = $c->completiondate ? date('d M Y', $c->completiondate) : '-';
            $list['coursestatus'] = $c->completiondate ? get_string('completed') : get_string('inprogress', 'mod_pokcertificate');
            $list['certificatetype'] = $c->templatetype == 0 ? 'Free' : 'Paid';
            $list['senttopok'] = $c->certificateurl ? get_string('yes') : get_string('no');
            $list['certificateurl'] = $c->certificateurl;
            $data[] = $list;
        }
    }
    return ['count' => $totalusers, 'data' => $data];
}

/**
 * Retrieve a list of users for awarding general certificates.
 *
 * This function retrieves a list of users from the database based on the provided parameters,
 * such as student ID, pagination settings, and offset. It prepares the data for awarding general certificates
 * by selecting relevant user information and formatting it appropriately.
 *
 * @param string $studentid The student ID to search for (optional).
 * @param int $perpage The number of records to display per page.
 * @param int $offset The offset for pagination.
 * @return array An associative array containing the total count of records and the formatted user data.
 */
function pokcertificate_awardgeneralcertificatelist($studentid, $perpage, $offset) {
    global $DB;
    $systemcontext = \context_system::instance();
    $countsql = "SELECT count(id) ";
    $selectsql = "SELECT * ";
    $fromsql = "FROM {user} WHERE deleted = 0 AND suspended = 0 AND id > 2 ";

    $queryparam = [];
    if ($studentid) {
        $fromsql .= "AND idnumber LIKE :studentid ";
        $queryparam['studentid'] = '%' . trim($studentid) . '%';
    }
    $count = $DB->count_records_sql($countsql . $fromsql, $queryparam);
    $records = $DB->get_records_sql($selectsql . $fromsql, $queryparam, $offset, $perpage);

    $list = [];
    $data = [];
    if ($records) {
        foreach ($records as $c) {
            $list = [];
            $list['id'] = $c->id;
            $list['firstname'] = $c->firstname;
            $list['lastname'] = $c->lastname;
            $list['email'] = $c->email;
            $list['studentid'] = $c->idnumber ? $c->idnumber : '-';
            $list['program'] = 'Program Name';
            $data[] = $list;
        }
    }
    return ['count' => $count, 'data' => $data];
}

/**
 * Extends the course navigation with a link to the Pokcertificate module participants page.
 *
 * @param navigation_node $navigation The course navigation node.
 * @return void
 */
function mod_pokcertificate_extend_navigation_course(navigation_node $navigation) {
    global $PAGE;
    $context = context_system::instance();
    if (has_capability('mod/pokcertificate:managecoursecertificatestatus', $context)) {
        $node = navigation_node::create(
            get_string('coursecertificatestatus', 'mod_pokcertificate'),
            new moodle_url(
                '/mod/pokcertificate/coursecertificatestatus.php',
                ['courseid' => $PAGE->course->id]
            ),
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/competencies', '')
        );
        $navigation->add_node($node);
    }
}

/**
 * Terminate the current script with a fatal error.
 *
 * Adapted from core_renderer's fatal_error() method. Needed because throwing errors with HTML links in them will convert links
 * to text using htmlentities. See MDL-66161 - Reflected XSS possible from some fatal error messages.
 *
 * So need custom error handler for fatal Zoom errors that have links to help people.
 *
 * @param string $errorcode The name of the string from error.php to print
 * @param string $module name of module
 * @param string $continuelink The url where the user will be prompted to continue.
 *                             If no url is provided the user will be directed to
 *                             the site index page.
 * @param mixed $a Extra words and phrases that might be required in the error string
 */
function pokcertificate_fatal_error($errorcode, $module = '', $continuelink = '', $a = null) {
    global $CFG, $COURSE, $OUTPUT, $PAGE;

    $output = '';
    $obbuffer = '';

    // Assumes that function is run before output is generated.
    if ($OUTPUT->has_started()) {
        // If not then have to default to standard error.
        throw new moodle_exception($errorcode, $module, $continuelink, $a);
    }

    $PAGE->set_heading($COURSE->fullname);
    $output .= $OUTPUT->header();

    // Output message without messing with HTML content of error.
    $message = '<p class="errormessage">' . get_string($errorcode, $module, $a) . '</p>';

    $output .= $OUTPUT->box($message, 'errorbox alert alert-danger', null, ['data-rel' => 'fatalerror']);

    if ($CFG->debugdeveloper) {
        if (!empty($debuginfo)) {
            $debuginfo = s($debuginfo); // Removes all nasty JS.
            $debuginfo = str_replace("\n", '<br />', $debuginfo); // Keep newlines.
            $output .= $OUTPUT->notification('<strong>Debug info:</strong> ' . $debuginfo, 'notifytiny');
        }

        if (!empty($backtrace)) {
            $output .= $OUTPUT->notification('<strong>Stack trace:</strong> ' . format_backtrace($backtrace), 'notifytiny');
        }

        if ($obbuffer !== '') {
            $output .= $OUTPUT->notification('<strong>Output buffer:</strong> ' . s($obbuffer), 'notifytiny');
        }
    }

    if (!empty($continuelink)) {
        $output .= $OUTPUT->continue_button($continuelink);
    }

    $output .= $OUTPUT->footer();

    // Padding to encourage IE to display our error page, rather than its own.
    $output .= str_repeat(' ', 512);

    echo $output;

    exit(1); // General error code.
}

/**
 * Retrieves the enrollment date of a user in a specific course.
 *
 * This function queries the database to find the enrollment date
 * of a user in a specific course by joining the user_enrolments and enrol tables.
 *
 * @param int $courseid The ID of the course.
 * @param int $userid The ID of the user.
 * @return int|false The timestamp of the enrollment date if found, otherwise false.
 */
function pokcertificate_courseenrollmentdate($courseid, $userid) {
    global $DB;

    // SQL query to join user_enrolments and enrol tables to get the enrollment date.
    $sql = "SELECT ue.timecreated
            FROM {user_enrolments} ue
            JOIN {enrol} e ON ue.enrolid = e.id
            WHERE e.courseid = :courseid AND ue.userid = :userid";

    $params = [
        'courseid' => $courseid,
        'userid' => $userid,
    ];

    // Execute the query.
    $enrollment = $DB->get_record_sql($sql, $params);

    // Return the enrollment date if found, otherwise return false.
    if ($enrollment) {
        return date('d M Y', $enrollment->timecreated);
    } else {
        return false;
    }
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
function pokcertificate_preview_by_user($cm, $pokcertificate, $flag) {
    global $USER;
    $id = $cm->id;
    $context = \context_module::instance($cm->id);
    $url = '';
    $adminview = false;
    $studentview = false;
    // Getting certificate template view for admin.
    if (is_siteadmin()  || has_capability('mod/pokcertificate:manageinstance', $context)) {
        $preview = pok::preview_template($id);
        if ($preview) {
            $adminview = true;
            $params = ['id' => $id];
            $url = new moodle_url('/mod/pokcertificate/preview.php', $params);
        }
    } else {
        // Getting certificate template view for student.
        $certificateissued = pokcertificate_issues::get_record(['certid' => $pokcertificate->id, 'userid' => $USER->id]);
        if ($flag) {
            $studentview = true;
        } else if (
            !empty($certificateissued) && $certificateissued->get('status') &&
            !empty($certificateissued->get('certificateurl'))
        ) {
            $url = $certificateissued->get('certificateurl');
        } else {
            $params = ['cmid' => $id, 'id' => $USER->id];
            $url = new moodle_url('/mod/pokcertificate/updateprofile.php', $params);
        }
    }
    return ['student' => $studentview, 'admin' => $adminview, 'url' => $url];
}

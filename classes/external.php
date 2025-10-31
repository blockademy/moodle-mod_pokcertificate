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


use core_course\external\helper_for_get_mods_by_courses;
use core_external\external_api;
use core_external\external_files;
use core_external\external_format_value;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use core_external\util;
use mod_pokcertificate\pok;
use mod_pokcertificate\helper;

/**
 * pokcertificate external functions
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pokcertificate_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function view_pokcertificate_parameters() {
        return new external_function_parameters(
            [
                'pokcertificateid' => new external_value(PARAM_INT, 'pokcertificate instance id'),
            ]
        );
    }

    /**
     * Simulate the pokcertificate/view.php web interface pokcertificate: trigger events, completion, etc...
     *
     * @param int $pokcertificateid the pokcertificate instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function view_pokcertificate($pokcertificateid) {

        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/pokcertificate/lib.php");

        $params = self::validate_parameters(
            self::view_pokcertificate_parameters(),
            [
                'pokcertificateid' => $pokcertificateid,
            ]
        );
        $warnings = [];

        // Request and permission validation.
        $pokcertificate = $DB->get_record('pokcertificate', ['id' => $params['pokcertificateid']], '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($pokcertificate, 'pokcertificate');

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/pokcertificate:view', $context);

        // Call the pokcertificate/lib API.
        pokcertificate_view($pokcertificate, $course, $cm, $context);

        $result = [];
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     * @since Moodle 3.0
     */
    public static function view_pokcertificate_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Describes the parameters for get_pokcertificates_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_pokcertificates_by_courses_parameters() {
        return new external_function_parameters(
            [
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'),
                    'Array of course ids',
                    VALUE_DEFAULT,
                    []
                ),
            ]
        );
    }

    /**
     * Returns a list of pokcertificates in a provided list of courses.
     * If no list is provided all pokcertificates that the user can view will be returned.
     *
     * @param array $courseids course ids
     * @return array of warnings and pokcertificates
     * @since Moodle 3.3
     */
    public static function get_pokcertificates_by_courses($courseids = []) {

        $warnings = [];
        $returnedpokcertificates = [];

        $params = [
            'courseids' => $courseids,
        ];
        $params = self::validate_parameters(self::get_pokcertificates_by_courses_parameters(), $params);

        $mycourses = [];
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = util::validate_courses($params['courseids'], $mycourses);

            // Get the pokcertificates in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $pokcertificates = get_all_instances_in_courses("pokcertificate", $courses);
            foreach ($pokcertificates as $pokcertificate) {
                $pokdetails = helper_for_get_mods_by_courses::format_name_and_intro($pokcertificate, 'mod_pokcertificate');
                $context = \context_module::instance($pokcertificate->coursemodule);
                self::validate_context($context);
                list($pokcertificate->content, $pokcertificate->contentformat) = \core_external\util::format_text(
                    $pokcertificate->content,
                    $pokcertificate->contentformat,
                    $context,
                    'mod_pokcertificate',
                    'content',
                    $pokcertificate->revision,
                    ['noclean' => true]
                );
                $pokcertificate->contentfiles = util::get_area_files($context->id, 'mod_pokcertificate', 'content');
                if (has_capability('moodle/course:manageactivities', $context)) {
                    $pokdetails['timemodified']  = $pokcertificate->timemodified;
                    $pokdetails['completionsubmit']  = $pokcertificate->completionsubmit;
                }
                $returnedpokcertificates[] = $pokdetails;
            }
        }

        $result = [
            'pokcertificates' => $returnedpokcertificates,
            'warnings' => $warnings,
        ];
        return $result;
    }

    /**
     * Describes the get_pokcertificates_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_pokcertificates_by_courses_returns() {
        return new external_single_structure(
            [
                'pokcertificates' => new external_multiple_structure(
                    new external_single_structure(array_merge(
                        helper_for_get_mods_by_courses::standard_coursemodule_elements_returns(),
                        [
                            'content' => new external_value(PARAM_RAW, 'Page content'),
                            'contentformat' => new external_format_value('content', VALUE_REQUIRED, 'Content format'),
                            'contentfiles' => new external_files('Files in the content'),
                            'legacyfiles' => new external_value(PARAM_INT, 'Legacy files flag'),
                            'legacyfileslast' => new external_value(PARAM_INT, 'Legacy files last control flag'),
                            'display' => new external_value(PARAM_INT, 'How to display the pokcertificate'),
                            'displayoptions' => new external_value(PARAM_RAW, 'Display options (width, height)'),
                            'revision' => new external_value(PARAM_INT, 'Incremented when after each file changes, to avoid cache'),
                            'timemodified' => new external_value(PARAM_INT, 'Last time the pokcertificate was modified'),
                            'completionsubmit' => new external_value(
                                PARAM_BOOL,
                                'Completion on receiving certificate',
                                VALUE_OPTIONAL
                            ),

                        ]
                    ))
                ),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Get parameters for verifying authentication.
     *
     * @return external_function_parameters The parameters for verifying authentication.
     */
    public static function verify_authentication_parameters() {
        return new external_function_parameters(
            [
                'prodtype' => new external_value(PARAM_INT, get_string('prodtype', 'mod_pokcertificate')),
                'authtoken' => new external_value(PARAM_RAW, get_string('authtoken', 'mod_pokcertificate')),
                'institution' => new external_value(PARAM_TEXT, get_string('institution', 'mod_pokcertificate')),
            ]
        );
    }

    /**
     * Verify authentication for a POK using an authentication token and institution.
     *
     * This method verifies the authentication for a specific product type using the provided
     * authentication token and institution information.
     *
     * @param string $prodtype The type of product for which authentication is being verified.
     * @param string $authtoken The authentication token used for verification.
     * @param string $institution The institution against which the authentication is performed.
     * @return bool Returns true if authentication is successful, false otherwise.
     */
    public static function verify_authentication($prodtype, $authtoken, $institution) {
        global $CFG;

        set_config('prodtype', $prodtype, 'mod_pokcertificate');
        require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
        $params = self::validate_parameters(
            self::verify_authentication_parameters(),
            ['prodtype' => $prodtype, 'authtoken' => $authtoken, "institution" => $institution]
        );
        $context = \context_system::instance();
        self::validate_context($context);
        if (has_capability('moodle/course:manageactivities', $context)) {
            $result = helper::pokcertificate_validate_apikey($params['authtoken']);

            if ($result) {
                $orgdetails = (new mod_pokcertificate\api)->get_organization();
                $organisation = json_decode($orgdetails);
                if (isset($organisation->wallet) && isset($organisation->name)) {
                    set_config('orgid', $organisation->wallet, 'mod_pokcertificate');
                    set_config('institution', $organisation->name, 'mod_pokcertificate');
                }
                set_config('availablecertificate', $organisation->availableCredits, 'mod_pokcertificate');
                set_config('pendingcertificates', $organisation->processingCredentials, 'mod_pokcertificate');
                set_config('issuedcertificates', $organisation->emittedCredentials, 'mod_pokcertificate');
                $msg = get_string("success");
                return ["status" => 0, "msg" => $msg, "response" => $orgdetails];
            } else {
                $msg = get_string("error");
                return ["status" => 1, "msg" => $msg, "response" => ''];
            }
        } else {
            $msg = get_string('accessdenied', 'pokcertificate');
            return ["status" => 1, "msg" => $msg, "response" => ''];
        }
    }

    /**
     * Get parameters for verifying authentication returns.
     *
     * @return external_single_structure The parameters for verifying authentication returns.
     */
    public static function verify_authentication_returns() {
        return new external_single_structure(
            [
                'status'  => new external_value(PARAM_TEXT, get_string('status')),
                'msg'  => new external_value(PARAM_RAW, get_string('error')),
                'response'  => new external_value(PARAM_RAW, get_string('response', 'mod_pokcertificate')),
            ]
        );
    }

    /**
     * Get parameters for emitting general certificate templates to selected users.
     *
     * @return external_function_parameters The parameters for showing certificate templates.
     */
    public static function emit_general_certificate_parameters() {
        return new external_function_parameters(
            [
                'userinputs' => new external_value(PARAM_RAW, 'userinputs'),
                'courseid' => new external_value(PARAM_INT, 'courseid'),
            ]
        );
    }

    /**
     * Emit a general certificate based on user inputs.
     *
     * This method generates and emits a general certificate using the provided user inputs.
     * It performs necessary operations to create the certificate based on the current state.
     *
     * @param string $userinputs An array containing user inputs necessary for generating the certificate.
     *                          This typically includes information such as name, date, and details required for the certificate.
     * @param int $courseid
     * @return string|false The generated certificate content as a string on success, or false on failure.
     */
    public static function emit_general_certificate($userinputs, $courseid = 0) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
        self::validate_parameters(
            self::emit_general_certificate_parameters(),
            ['userinputs' => $userinputs, 'courseid' => $courseid]
        );
        $emitcount = 0;
        $context = \context_system::instance();

        if ($courseid > 0) {
            $context = \context_course::instance($courseid, MUST_EXIST);
        }
        self::validate_context($context);
        if (has_capability('mod/pokcertificate:awardcertificate', $context)) {

            $useridsarr = explode(",", $userinputs);
            if ($useridsarr) {
                foreach ($useridsarr as $userrec) {
                    $data = unserialize(base64_decode($userrec));
                    $rec = implode("", $data);
                    $inp = explode("_", $rec);
                    $activityid = $inp[1];
                    $user = $inp[2];
                    $cm = get_coursemodule_from_instance('pokcertificate', $activityid);
                    $user = \core_user::get_user($user);
                    $user = helper::load_user_custom_fields($user);
                    $validuser = helper::check_usermapped_fielddata($cm, $user);

                    if ($validuser) {
                        pok::emit_certificate($cm->id, $user);
                        $emitcount++;
                    }
                }
            }
        }

        return ($emitcount > 0) ? true : false;
    }

    /**
     * Get returns for showing certificate templates.
     *
     * @return external_value The returns for showing certificate templates.
     */
    public static function emit_general_certificate_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
}

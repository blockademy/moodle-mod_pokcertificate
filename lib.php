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
use mod_pokcertificate\helper;
use mod_pokcertificate\persistent\pokcertificate;
use mod_pokcertificate\persistent\pokcertificate_fieldmapping;
use mod_pokcertificate\persistent\pokcertificate_templates;
use mod_pokcertificate\persistent\pokcertificate_issues;
use mod_pokcertificate\event\course_module_viewed;

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
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
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
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the issued certificates.
 *
 * @param MoodleQuickForm $mform form passed by reference
 */
function pokcertificate_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'pokcertificateheader', get_string('modulenameplural', 'pokcertificate'));
    $mform->addElement('advcheckbox', 'reset_pokcertificates', get_string('removeissues', 'pokcertificate'));
}

/**
 * Course reset form defaults.
 *
 * @param  mixed $course
 * @return array
 */
function pokcertificate_reset_course_form_defaults($course) {
    return ['reset_pokcertificates' => 1];
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function pokcertificate_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.
    global $DB;

    $componentstr = get_string('modulenameplural', 'pokcertificate');
    $status = [];
    $course = get_course($data->id);

    if (!empty($data->reset_pokcertificates)) {
        $pokcertificates = pokcertificate::get_records(['course' => $data->courseid]);
        foreach ($pokcertificates as $pokcertificate) {
            $pokid = $pokcertificate->get('id');
            $DB->delete_records_select('pokcertificate_issues', "pokid IN ($pokid )", [$data->courseid]);
            $completion = new completion_info($course);
            $cm = get_coursemodule_from_instance('pokcertificate', $pokid);
            if ($completion->is_enabled($cm) && $pokcertificate->get('completionsubmit')) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE);
            }
        }
        $status[] = ['component' => $componentstr, 'item' => get_string('removeissues', 'pokcertificate'), 'error' => false];
    }
    return $status;
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
        'id, name, display, displayoptions, intro, introformat,completionsubmit'
    )) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $pokcertificate->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('pokcertificate', $pokcertificate, $coursemodule->id, false);
    }
    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionsubmit'] = $pokcertificate->completionsubmit;
    }
    return $info;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_pokcertificate_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (
        empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC
    ) {
        return [];
    }

    $descriptions = [];

    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionsubmit':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionmustrecievecert', 'pokcertificate');
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}
/**
 * Set visibility to user if certificate not configured properly
 * mod_pokcertificate_cm_info_dynamic
 *
 * @param \cm_info $cm
 * @return void
 */
function mod_pokcertificate_cm_info_dynamic(\cm_info $cm) {
    global $DB, $USER;

    $context = \context_module::instance($cm->id);
    $isverified = get_config('mod_pokcertificate', 'pokverified');
    $user = \core_user::get_user($USER->id);
    if (!empty($user) && !has_capability('mod/pokcertificate:manageinstance', $context)) {
        $pokrecord = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);
        if ($pokrecord && !empty($pokrecord->get('templateid')) &&  $pokrecord->get('templateid') != 0) {
            $poktemplate = pokcertificate_templates::get_record(['id' => $pokrecord->get('templateid')]);
            $templatename = base64_encode($poktemplate->get('templatename'));;
            $pokissuerec = pokcertificate_issues::get_record(['pokid' => $cm->instance, 'userid' => $user->id]);
            if ((empty($pokissuerec)) ||
                ($pokissuerec && $pokissuerec->get('useremail') != $user->email)
            ) {
                $externalfields = helper::get_externalfield_list($templatename, $pokrecord->get('id'));
                if (!empty($externalfields)) {
                    $pokid = $pokrecord->get('id');
                    $pokfields = $DB->get_fieldset_sql(
                        "SELECT templatefield
                                    from {" . pokcertificate_fieldmapping::TABLE . "} WHERE pokid = :pokid",
                        ['pokid' => $pokid]
                    );
                    foreach ($externalfields as $key => $value) {
                        if (!in_array($key, $pokfields)) {
                            $link = \html_writer::tag(
                                'p',
                                get_string('certificatenotconfigured', 'mod_pokcertificate'),
                                [
                                    'class' => 'success-complheading',
                                    'style' => 'font-size: .875em; color: #495057;',
                                ]
                            );
                            $cm->set_after_link(' ' . $link);
                            $cm->set_user_visible(false);
                        }
                    }
                }

                $modinfo = get_fast_modinfo($cm->get_course(), $user->id);
                $cmuser = $modinfo->get_cm($cm->id);

                if ($cmuser && !empty($cmuser->availability) && !empty($cmuser->uservisible) && !empty($cmuser->available)) {
                    $user = \core_user::get_user($USER->id);
                    if ($cmuser->uservisible && $cmuser->available && $isverified) {
                        $link = pok::auto_emit_certificate($cm, $user);
                        if (!empty($link)) {
                            $cm->set_after_link(' ' . $link);
                        }
                    }
                }
            }
        } else {
            $link = \html_writer::tag(
                'p',
                get_string('certificatenotconfigured', 'mod_pokcertificate'),
                [
                    'class' => 'success-complheading',
                    'style' => 'font-size: .875em; color: #495057;',
                ]
            );
            $cm->set_after_link(' ' . $link);
            $cm->set_user_visible(false);
        }
    }
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

    $event = course_module_viewed::create($params);
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
 * Extends the course navigation with a link to the Pokcertificate module participants page.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param context $context The context of the course
 * @throws coding_exception
 * @throws moodle_exception
 */
function mod_pokcertificate_extend_navigation_course(navigation_node $navigation, $course, $context) {
    global $PAGE;

    if (has_capability('mod/pokcertificate:managecoursecertificate', $context)) {

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
    if (has_capability('mod/pokcertificate:awardcertificate', $context)) {
        $params = ['courseid' => $PAGE->course->id];
        $url = new \moodle_url('/mod/pokcertificate/generalcertificate.php', $params);
        $node = navigation_node::create(
            get_string('awardcertificate', 'mod_pokcertificate'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/competencies', '')
        );
        $navigation->add_node($node);
    }
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

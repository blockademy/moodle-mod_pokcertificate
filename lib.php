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
 * See {@link course_modinfo::get_array_of_activities()}
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
 * Lists all browsable file areas
 *
 * @package  mod_pokcertificate
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function pokcertificate_get_file_areas($course, $cm, $context) {
    $areas = [];
    $areas['content'] = get_string('content', 'pokcertificate');
    return $areas;
}

/**
 * File browsing support for pokcertificate module content area.
 *
 * @package  mod_pokcertificate
 * @category files
 * @param file_browser $browser file browser instance
 * @param stdClass $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param [stdClass] $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info instance or null if not found
 */
function pokcertificate_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // Students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot . '/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_pokcertificate', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' && $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_pokcertificate', 'content', 0);
            } else {
                // Not found.
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/pokcertificate/locallib.php");
        return new pokcertificate_content_file_info(
            $browser,
            $context,
            $storedfile,
            $urlbase,
            $areas[$filearea],
            true,
            true,
            true,
            false,
        );
    }

    // Note: pokcertificate_intro handled in file_browser automatically.
    return null;
}

/**
 * Serves the pokcertificate files.
 *
 * @package  mod_pokcertificate
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param [stdClass] $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function pokcertificate_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/pokcertificate:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // Intro is handled automatically in pluginfile.php.
        return false;
    }

    // Could be $arg  revision number or index.html.
    $arg = array_shift($args);
    if ($arg == 'index.html' || $arg == 'index.htm') {
        // Serve pokcertificate content.
        $filename = $arg;

        if (!$pokcertificate = $DB->get_record('pokcertificate', array('id' => $cm->instance), '*', MUST_EXIST)) {
            return false;
        }

        // We need to rewrite the pluginfile URLs so the media filters can work.
        $content = file_rewrite_pluginfile_urls(
            $pokcertificate->content,
            'webservice/pluginfile.php',
            $context->id,
            'mod_pokcertificate',
            'content',
            $pokcertificate->revision
        );
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        $content = format_text($content, $pokcertificate->contentformat, $formatoptions);

        // Remove @@PLUGINFILE@@/.
        $options = array('reverse' => true);
        $content = file_rewrite_pluginfile_urls(
            $content,
            'webservice/pluginfile.php',
            $context->id,
            'mod_pokcertificate',
            'content',
            $pokcertificate->revision,
            $options
        );
        $content = str_replace('@@PLUGINFILE@@/', '', $content);

        send_file($content, $filename, 0, 0, true, true);
    } else {
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_pokcertificate/$filearea/0/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            $pokcertificate = $DB->get_record('pokcertificate', array('id' => $cm->instance), 'id, legacyfiles', MUST_EXIST);
            if ($pokcertificate->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                return false;
            }
            $file = resourcelib_try_file_migration('/' . $relativepath, $cm->id, $cm->course, 'mod_pokcertificate', 'content', 0);
            if (!$file) {
                return false;
            }
            // File migrate - update flag.
            $pokcertificate->legacyfileslast = time();
            $DB->update_record('pokcertificate', $pokcertificate);
        }

        // Finally send the file.
        send_stored_file($file, null, 0, $forcedownload, $options);
    }
}

/**
 * Return a list of pokcertificate types
 * @param string $pokcertificatetype current pokcertificate type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function pokcertificate_pokcertificate_type_list($pokcertificatetype, $parentcontext, $currentcontext) {
    $pokcertificatetypestr = get_string('pokcertificate-mod-pokcertificate-x', 'pokcertificate');
    $modulepokcertificatetype = array('mod-pokcertificate-*' => $pokcertificatetypestr);
    return $modulepokcertificatetype;
}

/**
 * Export pokcertificate resource contents
 *
 * @return array of file content
 */
function pokcertificate_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    $contents = [];
    $context = context_module::instance($cm->id);

    $pokcertificate = $DB->get_record('pokcertificate', array('id' => $cm->instance), '*', MUST_EXIST);

    // Pokcertificate contents.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_pokcertificate', 'content', 0, 'sortorder DESC, id ASC', false);
    $urlbase = "$CFG->wwwroot/" . $baseurl;
    foreach ($files as $fileinfo) {
        $file = [];
        $file['type']         = 'file';
        $file['filename']     = $fileinfo->get_filename();
        $file['filepath']     = $fileinfo->get_filepath();
        $file['filesize']     = $fileinfo->get_filesize();
        $path = '/' . $context->id . '/mod_pokcertificate/content/' . $pokcertificate->revision;
        $path .= $fileinfo->get_filepath() . $fileinfo->get_filename();
        $file['fileurl']      = file_encode_url($urlbase, $path, true);
        $file['timecreated']  = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder']    = $fileinfo->get_sortorder();
        $file['userid']       = $fileinfo->get_userid();
        $file['author']       = $fileinfo->get_author();
        $file['license']      = $fileinfo->get_license();
        $file['mimetype']     = $fileinfo->get_mimetype();
        $file['isexternalfile'] = $fileinfo->is_external_file();
        if ($file['isexternalfile']) {
            $file['repositorytype'] = $fileinfo->get_repository_type();
        }
        $contents[] = $file;
    }

    // Pokcertificate html conent.
    $filename = 'index.html';
    $pokcertificatefile = [];
    $pokcertificatefile['type']         = 'file';
    $pokcertificatefile['filename']     = $filename;
    $pokcertificatefile['filepath']     = '/';
    $pokcertificatefile['filesize']     = 0;
    $path = '/' . $context->id . '/mod_pokcertificate/content/' . $filename;
    $pokcertificatefile['fileurl']      = file_encode_url($urlbase, $path, true);
    $pokcertificatefile['timecreated']  = null;
    $pokcertificatefile['timemodified'] = $pokcertificate->timemodified;
    // Make this file as main file.
    $pokcertificatefile['sortorder']    = 1;
    $pokcertificatefile['userid']       = null;
    $pokcertificatefile['author']       = null;
    $pokcertificatefile['license']      = null;
    $contents[] = $pokcertificatefile;

    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function pokcertificate_dndupload_register() {
    return array('types' => array(
        array('identifier' => 'text/html', 'message' => get_string('createpokcertificate', 'pokcertificate')),
        array('identifier' => 'text', 'message' => get_string('createpokcertificate', 'pokcertificate'))
    ));
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
    $params = array(
        'context' => $context,
        'objectid' => $pokcertificate->id
    );

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
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_pokcertificate_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory,
    $userid = 0
) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['pokcertificate'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/pokcertificate/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param  string $filearea The filearea.
 * @param  array  $args The path (the part after the filearea and before the filename).
 * @return array The itemid and the filepath inside the $args path, for the defined filearea.
 */
function mod_pokcertificate_get_path_from_pluginfile(string $filearea, array $args): array {
    // Page never has an itemid (the number represents the revision but it's not stored in database).
    array_shift($args);

    // Get the filepath.
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    return [
        'itemid' => 0,
        'filepath' => $filepath,
    ];
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
            'Authorization: ApiKey ' . $key
        ],
        'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_ENCODING' => '',
        'CURLOPT_CUSTOMREQUEST' => 'GET',
        'CURLOPT_SSL_VERIFYPEER' => false
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
 * Returns the plugin settings array with values.
 *
 * @return [array]
 */
function get_pokcertificate_settings() {

    $authtoken = get_config('mod_pokcertificate', 'authenticationtoken');
    $wallet = get_config('mod_pokcertificate', 'wallet');
    $domainname = get_config('mod_pokcertificate', 'domainname');
    $institution = get_config('mod_pokcertificate', 'institution');
    $availablecertificate = get_config('mod_pokcertificate', 'availablecertificate');
    $pendingcertificates = get_config('mod_pokcertificate', 'pendingcertificates');
    $issuedcertificates = get_config('mod_pokcertificate', 'issuedcertificates');
    $endofservices = get_config('mod_pokcertificate', 'endofservices');
    $incompletestudentprofiles = get_config('mod_pokcertificate', 'incompletestudentprofiles');


    $data = [
        'authenticationtoken' => $authtoken,
        'wallet' => $wallet,
        'domainname'  => $domainname,
        'institution' => $institution,
        'availablecertificate' => $availablecertificate,
        'pendingcertificates' => $pendingcertificates,
        'issuedcertificates' => $issuedcertificates,
        'incompletestudentprofiles' => $incompletestudentprofiles,
        'endofservices' => $endofservices,
    ];

    return $data;
}

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

function get_internalfield_list() {
    global $DB;
    $usercolumns = $DB->get_columns('user');
    $localfields = [];
    foreach ((array)$usercolumns as $key => $field) {
        $localfields[$key] = $field->name;
    }

    $allcustomfields = profile_get_custom_fields();
    $customfields = array_combine(array_column($allcustomfields, 'shortname'), $allcustomfields);
    foreach ((array)$customfields as $key => $field) {
        $localfields['profile_field_' . $key] = $field->shortname;
    }
    return $localfields;
}

/* Get all template definition fields
*
* @param string $template
* @return array
*/
function get_externalfield_list($template) {

    $templatefields = [];
    $template = base64_decode($template);
    $templatedefinition = pokcertificate_templates::get_field('templatedefinition', ['templatename' => $template]);

    $templatedefinition = json_decode($templatedefinition);
    if ($templatedefinition) {
        foreach ($templatedefinition->params as $param) {
            $pos = strpos($param->name, 'custom:');
            if ($pos !== false) {
                $var = substr($param->name, strlen('custom:'));
                $templatefields[$var] = $var;
            }
        }
    }

    return $templatefields;
}

/**
 * Adding pokcertificate view button in the course-module content
 *  *
 * @param cm_info $cm
 */
/*
function mod_pokcertificate_cm_info_view(cm_info $cm) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/resource/locallib.php');
    $customdata = $cm->customdata;


    if ($cm->availability) {
        if (has_capability('mod/pokcertificate:manageinstance', context_system::instance())) {
            $cm->set_after_link(' ' . html_writer::tag(
                'a',
                get_string('previewcertificate', 'mod_pokcertificate'),
                [
                    'href' => $CFG->wwwroot . '/mod/pokcertificate/preview.php?id=' . $cm->id,
                    'class' => 'btn btn-primary certbutton', 'aria-selected' => "true"
                ]
            ));
        } else {
            $cm->set_after_link(' ' . html_writer::tag(
                'a',
                get_string('issuecertificate', 'mod_pokcertificate'),
                [
                    'href' => $CFG->wwwroot . '/mod/pokcertificate/issue.php?id=' . $cm->id,
                    'class' => 'btn btn-primary certbutton', 'aria-selected' => "true"
                ]
            ));
        }
    }
} */

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
function incompletestudentprofilelist($studentid, $perpage, $offset) {
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
 * @param string $studentid The student ID to search for (optional).
 * @param string $senttopok Indicates whether certificates have been sent to Pokcertificate (optional).
 * @param string $coursestatus The completion status of the course (optional).
 * @param int $perpage The number of records to display per page.
 * @param int $offset The offset for pagination.
 * @return array An associative array containing the total count of records and the formatted participant data.
 */
function courseparticipantslist($courseid, $studentid, $studentname, $email, $senttopok, $coursestatus, $perpage, $offset) {
    global $DB;
    $pokmoduleid = $DB->get_field('modules', 'id', ['name' => 'pokcertificate']);
    $countsql = "SELECT count(ra.id) ";
    $selectsql = "SELECT UUID(),
                         pc.name as activity,
                         u.firstname,
                         u.idnumber,
                         u.email,
                         cc.timecompleted as completiondate,
                         ue.timecreated as enrolldate,
                         pct.templatetype,
                         pci.certificateurl ";
    $fromsql = "FROM {pokcertificate} as pc
                JOIN {course_modules} cm ON pc.id = cm.instance
                JOIN {context} ctx ON (pc.course = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_COURSE.")
                JOIN {role_assignments} ra ON ctx.id = ra.contextid
                JOIN {role} r ON (ra.roleid = r.id AND r.shortname = 'student')
                JOIN {user} u ON ra.userid = u.id
                JOIN {user_enrolments} ue ON u.id = ue.userid
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
            $list['enrolldate'] = date('d M Y', $c->enrolldate);
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
function awardgeneralcertificatelist($studentid, $perpage, $offset) {
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
    $node = navigation_node::create(
        get_string('coursecertificatestatus', 'mod_pokcertificate'),
        new moodle_url(
            '/mod/pokcertificate/courseparticipants.php',
            ['courseid' => $PAGE->course->id]
        ),
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/competencies', '')
    );
    $navigation->add_node($node);
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

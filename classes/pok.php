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

use mod_pokcertificate\persistent\pokcertificate_templates;
use mod_pokcertificate\persistent\pokcertificate;
use mod_pokcertificate\persistent\pokcertificate_fieldmapping;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');
/**
 * Class pok
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pok {
    protected static $cmid;

    public static function set_cmid($cmid) {
        self::$cmid = $cmid;
    }

    public static function get_cm_instance($cmid) {
        global $DB;
        $cm = '';
        $recexists = $DB->record_exists('course_modules', ['id' => $cmid]);
        if ($recexists) {
            $cm = get_coursemodule_from_id('pokcertificate', $cmid, 0, false, MUST_EXIST);
        }
        return $cm;
    }
    /**
     * Save pokcertificate instance.
     * @param [stdClass] $data
     * @param [mod_pokcertificate_mod_form] $mform
     * @return [object] new pokcertificate instance
     */
    public static function save_pokcertificate_instance($data, $mform) {
        global $CFG, $DB, $USER;
        require_once("$CFG->libdir/resourcelib.php");
        $cmid = $data->coursemodule;

        $data->timemodified = time();
        $displayoptions = array();
        if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
            $displayoptions['popupwidth']  = $data->popupwidth;
            $displayoptions['popupheight'] = $data->popupheight;
        }
        $displayoptions['printintro']   = $data->printintro;
        $displayoptions['printlastmodified'] = $data->printlastmodified;
        $data->displayoptions = serialize($displayoptions);

        $data->orgname = get_config('mod_pokcertificate', 'institution');
        $data->orgid = get_config('mod_pokcertificate', 'orgid');
        $data->usercreated = $USER->id;
        $data->timecreated = time();

        $pokcertificate = new pokcertificate(0, $data);
        $pokcertificate->create();
        $data->id = $pokcertificate->get('id');

        // we need to use context now, so we need to make sure all needed info is already in db
        $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));
        $context = \context_module::instance($cmid);

        if ($mform and !empty($data->pokcertificate['itemid'])) {
            $draftitemid = $data->pokcertificate['itemid'];
            $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_pokcertificate', 'content', 0, pokcertificate_get_editor_options($context), $data->content);
            $data->usermodified = $USER->id;
            $pokcertificate = new pokcertificate(0, $data);
            $pokcertificate->update();
        }

        $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
        \core_completion\api::update_completion_date_event($cmid, 'pokcertificate', $data->id, $completiontimeexpected);
        return $data;
    }


    /**
     * Update pokcertificate instance.
     * @param [object] $data
     * @param [object] $mform
     * @return [bool] true
     */
    public static function update_pokcertificate_instance($data, $mform) {
        global $CFG;
        require_once("$CFG->libdir/resourcelib.php");

        $cmid        = $data->coursemodule;
        $draftitemid = $data->pokcertificate['itemid'];

        $data->timemodified = time();
        $data->id           = $data->instance;
        $data->revision++;

        $displayoptions = array();
        if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
            $displayoptions['popupwidth']  = $data->popupwidth;
            $displayoptions['popupheight'] = $data->popupheight;
        }
        $displayoptions['printintro']   = $data->printintro;
        $displayoptions['printlastmodified'] = $data->printlastmodified;
        $data->displayoptions = serialize($displayoptions);

        $pokcertificate = new pokcertificate(0, $data);
        $pokcertificate->update();

        $context = \context_module::instance($cmid);
        if ($draftitemid) {
            $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_pokcertificate', 'content', 0, pokcertificate_get_editor_options($context), $data->content);
            $pokcertificate = new pokcertificate(0, $data);
            $pokcertificate->update();
        }

        $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
        \core_completion\api::update_completion_date_event($cmid, 'pokcertificate', $data->id, $completiontimeexpected);
        return true;
    }


    /**
     * Delete pokcertificate instance.
     * @param [int] $id
     * @return [bool] true
     */
    public static function delete_pokcertificate_instance($id) {
        global $DB;

        if (!$pokcertificate = $DB->get_record('pokcertificate', array('id' => $id))) {
            return false;
        }

        $cm = get_coursemodule_from_instance('pokcertificate', $id);
        \core_completion\api::update_completion_date_event($cm->id, 'pokcertificate', $id, null);

        // note: all context files are deleted automatically

        $DB->delete_records('pokcertificate', array('id' => $pokcertificate->id));
        return true;
    }


    /**
     * Saves the selected template definition to the database.
     *
     * @param [string] $template - template name
     * @param [string] $cm - course module instance
     *
     * @return [array] $certid -pok certificate id ,$templateid - template id
     */
    public static function save_template_definition($templateinfo, $cm) {
        global $USER;
        $templateid = 0;
        $template = $templateinfo->template;
        $templatetype = $templateinfo->templatetype;
        $templatedefdata = new \stdClass();
        $templatedefinition = (new \mod_pokcertificate\api)->get_template_definition($template);
        $pokid = pokcertificate::get_field('id', ['id' => $cm->instance]);

        if ($templatedefinition) {
            $templatedefdata = new \stdclass;
            $templateexists = pokcertificate_templates::get_record(['templatename' => $template]);

            if ($templateexists) {
                $templateid = $templateexists->get('id');
                $templatedata = new pokcertificate_templates($templateexists->get('id'));
                $templatedata->set('pokid', $pokid);
                $templatedata->set('templatetype', $templatetype);
                $templatedata->set('templatename', $template);
                $templatedata->set('templatedefinition', $templatedefinition);
                $templatedata->set('usermodified', $USER->id);
                $templatedata->set('timemodified', time());
                $templatedata->update();
            } else {
                $templatedefdata->pokid = $pokid;
                $templatedefdata->templatetype = $templatetype;
                $templatedefdata->templatename = $template;
                $templatedefdata->templatedefinition = $templatedefinition;
                $templatedefdata->usercreated = $USER->id;
                $templatedata = new pokcertificate_templates(0, $templatedefdata);
                $newtemplate = $templatedata->create();
                $templateid = $newtemplate->get('id');
            }
            if ($templateid != 0) {

                $pokdata = new pokcertificate($pokid);
                $pokdata->set('templateid', $templateid);
                $pokdata->set('usermodified', $USER->id);
                $pokdata->update();
            }
        }
        return ['certid' => $pokid, 'templateid' => $templateid];
    }

    /**
     * Saves the fieldmapping fields.
     *
     * @param [object] $data - fieldmapping data
     *
     * @return [array]
     */
    public static function save_fieldmapping_data($data) {

        try {
            if ($data->certid) {
                $fields = pokcertificate_fieldmapping::get_records(['certid' => $data->certid]);

                if ($fields) {
                    foreach ($fields as $field) {
                        $mappedfield = new pokcertificate_fieldmapping($field->get('id'));
                        $mappedfield->delete();
                    }
                }
                for ($i = 0; $i < $data->option_repeats; $i++) {
                    if (isset($data->templatefield[$i]) && isset($data->userfield[$i])) {
                        $mappingfield = new \stdClass();
                        $mappingfield->timecreated = time();
                        $mappingfield->certid = $data->certid;
                        $mappingfield->templatefield = $data->templatefield[$i];
                        $mappingfield->userfield = $data->userfield[$i];
                        $fieldmapping = new pokcertificate_fieldmapping(0, $mappingfield);
                        $fieldmapping->create();
                    }
                }
                return true;
            }
            return false;
        } catch (\moodle_exception $e) {
            print_r($e);
            return false;
        }
    }

    public static function get_certificate_templates($cmid, $type = 'free') {
        global $CFG;
        require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');
        $cm = get_coursemodule_from_id('pokcertificate', $cmid, 0, false, MUST_EXIST);
        $templateid = pokcertificate::get_field('templateid', ['id' => $cm->instance, 'course' => $cm->course]);
        $templaterecord = pokcertificate_templates::get_record(['id' => $templateid]);

        $templateslist = (new \mod_pokcertificate\api)->get_templates_list();
        $templateslist = json_decode($templateslist);
        $templates = [];
        if ($templateslist) {
            foreach ($templateslist as $template) {
                $data = [];
                $previewdata = json_encode(SAMPLE_DATA);
                $templatepreview = (new \mod_pokcertificate\api)->preview_certificate($template, $previewdata);
                $data['tempname'] = base64_encode($template);
                $data['temptype'] = ($type == 'free') ? 0 : 1;
                $data['name'] = $template;
                $data['cmid'] = $cmid;
                $data['selectedtemplate'] = ($templaterecord && $templaterecord->get('templatename') == $template) ? true : false;
                $data['certimage'] = trim($templatepreview, '"');
                $templates['certdata'][] = $data;
            }
        }
        return $templates;
    }

    public static function preview_template($cmid) {
        $cm = self::get_cm_instance($cmid);
        if (!empty($cm) && has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {
            $templateid = pokcertificate::get_field('templateid', ['id' => $cm->instance, 'course' => $cm->course]);
            if ($templateid) {
                return true;
            }
        }
        return false;
    }

    public static function emit_certificate($cmid, $user) {
        $cm = self::get_cm_instance($cmid);
        $pokfields = [];
        if (!empty($cm) && !has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {
            $pokrecord = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);
            $totalcertcount = get_config('mod_pokcertificate', 'availablecertificate');
            if ($totalcertcount >= 0) {
                $pokfields = self::check_profile_fields($user, $cm);
            }
        }
        return $pokfields;
    }

    public static function check_profile_fields($user, $cm) {
        $pokrecord = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);

        $pokfields = pokcertificate_fieldmapping::get_records(['certid' => $pokrecord->get('id')]);
        return $pokfields;
        /*  foreach ($pokfields as $field) {
            $fieldname = $field->get('userfield');
            if (empty($user->$fieldname)) {
                return false;
            }
        }
        return true; */
    }
}

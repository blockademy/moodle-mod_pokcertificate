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
use mod_pokcertificate\persistent\pokcertificate_issues;

defined('MOODLE_INTERNAL') || die;

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

    /**
     * set_cmid
     *
     * @param  int $cmid
     * @return void
     */
    public static function set_cmid($cmid) {
        self::$cmid = $cmid;
    }

    /**
     * get cm instance
     *
     * @param  int $cmid
     * @return object
     */
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
        $displayoptions = [];
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

        // We need to use context now, so we need to make sure all needed info is already in db.
        $DB->set_field('course_modules', 'instance', $data->id, ['id' => $cmid]);
        $context = \context_module::instance($cmid);

        if ($mform && !empty($data->pokcertificate['itemid'])) {
            $draftitemid = $data->pokcertificate['itemid'];
            $data->content = file_save_draft_area_files(
                $draftitemid,
                $context->id,
                'mod_pokcertificate',
                'content',
                0,
                pokcertificate_get_editor_options($context),
                $data->content
            );
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
        global $CFG, $USER;
        require_once("$CFG->libdir/resourcelib.php");

        $cmid        = $data->coursemodule;
        $draftitemid = $data->pokcertificate['itemid'];

        $data->orgname = get_config('mod_pokcertificate', 'institution');
        $data->orgid = get_config('mod_pokcertificate', 'orgid');
        $data->usercreated = $USER->id;
        $data->usermodified = $USER->id;
        $data->timemodified = time();
        $data->id           = $data->instance;
        $data->revision++;

        $displayoptions = [];
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
            $data->content = file_save_draft_area_files(
                $draftitemid,
                $context->id,
                'mod_pokcertificate',
                'content',
                0,
                pokcertificate_get_editor_options($context),
                $data->content
            );
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

        if (!$pokcertificate = $DB->get_record('pokcertificate', ['id' => $id])) {
            return false;
        }

        $cm = get_coursemodule_from_instance('pokcertificate', $id);
        \core_completion\api::update_completion_date_event($cm->id, 'pokcertificate', $id, null);

        // Note: all context files are deleted automatically.

        $DB->delete_records('pokcertificate', ['id' => $pokcertificate->id]);
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
                $fieldvalues = [];
                foreach($data->templatefield as $key => $field){
                    $fieldvalues[$field] = $data->userfield[$key];
                }
                foreach($fieldvalues as $field => $value){
                    $mappingfield = new \stdClass();
                        $mappingfield->timecreated = time();
                        $mappingfield->certid = $data->certid;
                        $mappingfield->templatefield = $field;
                        $mappingfield->userfield = $value;
                        $fieldmapping = new pokcertificate_fieldmapping(0, $mappingfield);
                        $fieldmapping->create();              
                }
                return true;
            }
            return false;
        } catch (\moodle_exception $e) {
            print_r($e);
            return false;
        }
    }

    /**
     * get_certificate_templates
     *
     * @param  mixed $cmid
     * @param  mixed $type
     * @return array
     */
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

    /**
     * preview_template
     *
     * @param  mixed $cmid
     * @return bool
     */
    public static function preview_template($cmid) {
        $cm = self::get_cm_instance($cmid);
        if (!empty($cm) && has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {
            $templateid = pokcertificate::get_field(
                'templateid',
                ['id' => $cm->instance, 'course' => $cm->course]
            );
            if ($templateid) {
                return true;
            }
        }
        return false;
    }

    /**
     * emit_certificate
     *
     * @param  mixed $cmid
     * @param  mixed $user
     * @return [void]
     */
    public static function emit_certificate($cmid, $user) {
        $cm = self::get_cm_instance($cmid);
        $emitcertificate = '';
        if (!empty($cm) && !has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {
            $pokrecord = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);
            if ($pokrecord && $pokrecord->get('templateid')) {
                $template = pokcertificate_templates::get_record(['id' => $pokrecord->get('templateid')]);

                $emitdata = self::get_emitcertificate_data($user, $template, $pokrecord);
                $data = json_encode($emitdata);
                $emitcertificate = (new \mod_pokcertificate\api)->get_certificate($data);
                // if ($user->username == 'student1') {
                //     $emitcertificate = '{"processing": true, "viewUrl": "https://view.pok.tech/c/662d6a93-2dab-493c-be88-2c44e6076002"}';
                // } else {
                //     $emitcertificate = '{"processing": false, "viewUrl": "https://view.pok.tech/c/662d6a93-2dab-493c-be88-2c44e6076002"}';
                // }
                $emitcertificate = json_decode($emitcertificate);
            }
        }
        return $emitcertificate;
    }

    /**
     * get_emitcertificate_data
     *
     * @param  mixed $user
     * @param  mixed $template
     * @param  mixed $pokrecord
     * @return [object]
     */
    public static function get_emitcertificate_data($user, $template, $pokrecord) {
        global $USER;
        $templatedefinition = json_decode($template->get('templatedefinition'));
        if ($templatedefinition) {
            foreach ($templatedefinition->params as $param) {
                if ($param->name == 'institution') {
                    $param->value = get_config('mod_pokcertificate', 'institution');
                }
                if ($param->name == 'achiever') {
                    $param->value = $USER->firstname . ' ' . $USER->lastname;
                }
                if ($param->name == 'title') {
                    $param->value = $pokrecord->get('title');
                }
                if ($param->name == 'date') {
                    $param->value = date('d-m-Y');
                }
                $pos = strpos($param->name, 'custom:');

                if ($pos !== false) {

                    $pokfields = pokcertificate_fieldmapping::get_records(['certid' => $pokrecord->get('id')]);

                    if ($pokfields) {
                        foreach ($pokfields as $field) {
                            $varname = substr($param->name, strlen('custom:'));

                            if ($field->get('templatefield') == $varname) {
                                $userfield =  $field->get('userfield');
                                if (strpos($field->get('userfield'), 'profile_field_') === 0) {
                                    $userprofilefield = substr($field->get('userfield'), strlen('profile_field_'));
                                    $param->value = $USER->profile[$userprofilefield];
                                } else {
                                    $param->value = $USER->$userfield;
                                }
                            }
                        }
                    }
                }
            }
        }
        $templatedefinition = json_encode($templatedefinition);
        $emitdata = new \stdclass;
        $emitdata->email = $user->email;
        $emitdata->institution = get_config('mod_pokcertificate', 'institution');
        $emitdata->identification = '0123456789';
        $emitdata->first_name = $user->firstname;
        $emitdata->last_name = $user->lastname;
        $emitdata->title = "Course Completion";
        $emitdata->template_base64 = base64_encode($templatedefinition);
        $emitdata->date = time();
        $emitdata->free = ($template->get('templatetype') == 0) ? 'free' : 'paid';
        $emitdata->wallet = get_config('mod_pokcertificate', 'wallet');
        $emitdata->language_tag = $user->lang;
        return $emitdata;
    }

    /**
     * save_issued_certificate
     *
     * @param  mixed $cmid
     * @param  mixed $user
     * @param  mixed $emitcertificate
     * @return [void]
     */
    public static function save_issued_certificate($cmid, $user, $emitcertificate) {
        $cm = self::get_cm_instance($cmid);
        try {
            $pokrecord = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);
            if ($pokrecord) {

                $data = new \stdclass;
                $data->certid = $pokrecord->get('id');
                $data->userid = $user->id;
                $data->status = true;
                $data->templateid = $pokrecord->get('templateid');
                $data->certificateurl = $emitcertificate->viewUrl;
                $data->pokcertificateid = $emitcertificate->id;
                $data->timecreated = time();

                $pokcertificateissues = new pokcertificate_issues(0, $data);
                $pokcertificateissues->create();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * get_mapping_fields
     *
     * @param  mixed $user
     * @param  mixed $cm
     * @return [object]
     */
    public static function get_mapping_fields($user, $cm) {
        $pokrecord = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);
        $pokfields = pokcertificate_fieldmapping::get_records(['certid' => $pokrecord->get('id')]);
        return $pokfields;
    }

    /**
     * check_userfields_data
     *
     * @param  mixed $cmid
     * @param  mixed $user
     * @return bool
     */
    public static function check_userfields_data($cmid, $user) {
        $cm = self::get_cm_instance($cmid);
        $pokfields = [];
        if (!empty($cm)) {
            $pokfields = self::get_mapping_fields($user, $cm);
            foreach ($pokfields as $field) {
                $fieldname = $field->get('userfield');
                if (empty($user->$fieldname)) {
                    return false;
                }
            }
        }
        return true;
    }
}

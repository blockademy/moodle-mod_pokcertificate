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
use mod_pokcertificate\event\template_updated;
use core_availability\info_module;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
/**
 * Class pok
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pok {

    /**
     * @var int $cmid The course module ID.
     */
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
     * @param object $data
     * @param moodleform $mform
     * @return object new pokcertificate instance
     */
    public static function save_pokcertificate_instance($data, $mform) {
        global $CFG, $DB, $USER;
        require_once("$CFG->libdir/resourcelib.php");
        $cmid = $data->coursemodule;

        $data->timemodified = time();
        $displayoptions = [];

        $displayoptions['printintro']   = (isset($data->printintro) ? $data->printintro : 0);
        $displayoptions['printlastmodified'] = (isset($data->printlastmodified) ? $data->printlastmodified : 1);
        $data->displayoptions = serialize($displayoptions);

        $data->orgname = get_config('mod_pokcertificate', 'institution');
        $data->orgid = get_config('mod_pokcertificate', 'orgid');
        $data->usercreated = $USER->id;
        $data->timecreated = time();
        $displayoptions = [];
        $displayoptions['printintro']   = (isset($data->printintro) ? $data->printintro : 0);
        $displayoptions['printlastmodified'] = (isset($data->printlastmodified) ? $data->printlastmodified : 1);
        $data->displayoptions = serialize($displayoptions);

        $data->completionsubmit = $data->completionsubmit;

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
     * @param object $data
     * @param moodleform $mform
     * @return bool true
     */
    public static function update_pokcertificate_instance($data, $mform) {
        global $CFG, $USER;
        require_once("$CFG->libdir/resourcelib.php");
        $cmid        = $data->coursemodule;

        $data->orgname = get_config('mod_pokcertificate', 'institution');
        $data->orgid = get_config('mod_pokcertificate', 'orgid');
        $data->usercreated = $USER->id;
        $data->usermodified = $USER->id;
        $data->timemodified = time();
        $data->id           = $data->instance;

        $displayoptions = [];
        $displayoptions['printintro']   = (isset($data->printintro) ? $data->printintro : 0);
        $displayoptions['printlastmodified'] = (isset($data->printlastmodified) ? $data->printlastmodified : 1);
        $data->displayoptions = serialize($displayoptions);
        $data->completionsubmit = $data->completionsubmit;

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
        }

        $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
        \core_completion\api::update_completion_date_event($cmid, 'pokcertificate', $data->id, $completiontimeexpected);

        $pokcertificate = new pokcertificate(0, $data);
        $pokcertificate->update();
        return true;
    }


    /**
     * Delete pokcertificate instance and related data from other tables.
     * @param int $id
     * @return bool true
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
        $DB->delete_records('pokcertificate_templates', ['pokid' => $pokcertificate->id]);
        $DB->delete_records('pokcertificate_fieldmapping', ['pokid' => $pokcertificate->id]);
        $DB->delete_records('pokcertificate_issues', ['pokid' => $pokcertificate->id]);
        return true;
    }


    /**
     * Saves the selected template definition to the database.
     *
     * @param object $templateinfo - template information
     * @param object $templatedefinition - api template definition
     * @param object $cm - course module instance
     *
     * @return array []
     */
    public static function save_template_definition($templateinfo, $templatedefinition, $cm) {
        global $USER, $DB;
        $templateid = 0;
        $template = $templateinfo->template;
        $templatetype = $templateinfo->templatetype;
        $pokid = pokcertificate::get_field('id', ['id' => $cm->instance]);

        try {
            if ($templatedefinition) {
                $templatedefdata = new \stdclass;
                $templateexists = pokcertificate_templates::get_record(
                    [$DB->sql_compare_text('templatename') => $DB->sql_compare_text($template), 'pokid' => $pokid]
                );

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
                    $context = \context_module::instance($cm->id);
                    $eventparams = [
                        'context' => $context,
                        'objectid' => $cm->id,
                        'other' => [
                            'pokcertificateid' => $pokid,
                            'templateid' => $templateid,
                        ],
                    ];
                    template_updated::create($eventparams)->trigger();
                }
                return ['pokid' => $pokid, 'templateid' => $templateid];
            } else {
                return [];
            }
        } catch (\moodle_exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Save the fieldmapping data.
     *
     * @param object $data - fieldmapping data
     *
     * @return bool
     */
    public static function save_fieldmapping_data($data) {

        try {
            if ($data->pokid) {
                $fields = pokcertificate_fieldmapping::get_records(['pokid' => $data->pokid]);

                if ($fields) {
                    foreach ($fields as $field) {
                        $mappedfield = new pokcertificate_fieldmapping($field->get('id'));
                        $mappedfield->delete();
                    }
                }
                $fieldvalues = [];
                if (isset($data->fieldcount) && ($data->fieldcount > 0)) {
                    for ($i = 0; $i < $data->fieldcount; $i++) {
                        $tempfield = 'templatefield_' . $i;
                        $userfield = 'userfield_' . $i;
                        $fieldvalues[$data->$tempfield] = $data->$userfield;
                    }
                }

                foreach ($fieldvalues as $field => $value) {
                    $mappingfield = new \stdClass();
                    $mappingfield->timecreated = time();
                    $mappingfield->pokid = $data->pokid;
                    $mappingfield->templatefield = $field;
                    $mappingfield->userfield = $value;
                    $fieldmapping = new pokcertificate_fieldmapping(0, $mappingfield);
                    $fieldmapping->create();
                }
                return true;
            }
            return false;
        } catch (\moodle_exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get list of certificate templates.
     *
     * @param  int $cmid
     * @return array
     */
    public static function get_certificate_templates($cmid = 0) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');

        $cm = get_coursemodule_from_id('pokcertificate', $cmid, 0, false, MUST_EXIST);
        $templateid = pokcertificate::get_field('templateid', ['id' => $cm->instance, 'course' => $cm->course]);
        $templaterecord = pokcertificate_templates::get_record(['id' => $templateid]);

        $templates = [];
        $templateslist = (new \mod_pokcertificate\api)->get_templates_list();
        $templateslist = json_decode($templateslist);
        if ($templateslist) {
            foreach ($templateslist as $template) {
                $data = [];
                $previewdata = json_encode(SAMPLE_DATA);
                $templatepreview = (new \mod_pokcertificate\api)->preview_certificate($template, $previewdata);
                $data['tempname'] = base64_encode($template);
                $data['name'] = $template;
                $data['cmid'] = ($cmid) ?? $cmid;
                $data['selectedtemplate'] = ($templaterecord &&
                    $templaterecord->get('templatename') == $template) ? true : false;
                $data['certimage'] = trim($templatepreview, '"');
                $templates['certdata'][] = $data;
            }
        }
        $templates['temptype'] = ($templaterecord &&
            $templaterecord->get('templatetype') ? $templaterecord->get('templatetype') : 0);

        return $templates;
    }

    /**
     * Preview the temlate to admin.
     *
     * @param  int $cmid
     * @return bool
     */
    public static function preview_template($cmid) {
        $cm = self::get_cm_instance($cmid);
        $context = \context_module::instance($cm->id);
        if (!empty($cm) && permission::can_manage($context)) {
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
     * Invoke emit certificate api by passing the template definition and saving
     * the certificate id returned from api to issues tables.
     *
     * @param  int $cmid
     * @param  object $user
     * @return bool
     */
    public static function emit_certificate($cmid, $user) {

        $user = \core_user::get_user($user->id);
        profile_load_custom_fields($user);

        $cm = self::get_cm_instance($cmid);
        $emitcertificate = new \stdClass();
        try {

            if (!empty($cm)) {

                $pokrecord = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);

                if ($pokrecord && $pokrecord->get('templateid')) {
                    $template = pokcertificate_templates::get_record(['id' => $pokrecord->get('templateid')]);
                    $pokissuerec = pokcertificate_issues::get_record(['pokid' => $cm->instance, 'userid' => $user->id]);
                    if ((empty($pokissuerec)) ||
                        ($pokissuerec && $pokissuerec->get('useremail') != $user->email)
                    ) {
                        $emitdata = self::get_emitcertificate_data($user, $template, $pokrecord);
                        $data = json_encode($emitdata);

                        $emitcertificate = (new \mod_pokcertificate\api)->emit_certificate($data);
                        $emitcertificate = json_decode($emitcertificate);

                        if ($emitcertificate) {
                            $emitcertificate->status = false;
                            self::save_issued_certificate($cmid, $user, $emitcertificate);
                            return true;
                        }
                    }
                }
            }
            return false;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Invoke get_certificate api to issue certificate by passing the certificate id emitted.
     *
     * @param  object $pokissuerec
     * @return mixed
     */
    public static function issue_certificate($pokissuerec) {
        $data = $pokissuerec->to_record();
        if (!empty($data->pokcertificateid)) {
            $issuecertificate = (new \mod_pokcertificate\api)->get_certificate($data->pokcertificateid);
            $issuecertificate = json_decode($issuecertificate);
            return $issuecertificate;
        }
        return '';
    }

    /**
     * Replace template defifnition values with user values and
     * adding custom params if mapped to template.
     *
     * @param  object $user
     * @param  object $template
     * @param  object $pokrecord
     * @return object
     */
    public static function get_emitcertificate_data($user, $template, $pokrecord) {

        $templatename = $template->get('templatename');
        $resptemplatedefinition = (new \mod_pokcertificate\api)->get_template_definition($templatename);

        if (!empty($resptemplatedefinition)) {
            $templatedefinition = json_decode($resptemplatedefinition);
        } else {
            $templatedefinition = json_decode($template->get('templatedefinition'));
        }

        $customparams = [];
        if ($templatedefinition && $templatedefinition->params) {
            foreach ($templatedefinition->params as $param) {
                if ($param->name == 'institution') {
                    $param->value = get_config('mod_pokcertificate', 'institution');
                }
                if ($param->name == 'achiever') {
                    $param->value = $user->firstname . ' ' . $user->lastname;
                }
                if ($param->name == 'title') {
                    $param->value = $pokrecord->get('title');
                }
                if ($param->name == 'date') {
                    $param->value = date('d-m-Y');
                }
                $pos = strpos($param->name, 'custom:');

                if ($pos !== false) {

                    $pokfields = pokcertificate_fieldmapping::get_records(['pokid' => $pokrecord->get('id')]);

                    if ($pokfields) {
                        foreach ($pokfields as $field) {
                            $varname = substr($param->name, strrpos($param->name, ':') + 1);
                            if ($field->get('templatefield') == $varname) {
                                $userfield = $field->get('userfield');
                                if (strpos($field->get('userfield'), 'profile_field_') === 0) {
                                    $userprofilefield = substr($field->get('userfield'), strlen('profile_field_'));
                                    $customparams[$param->name] = $user->profile[$userprofilefield];
                                    $param->value = $user->profile[$userprofilefield];
                                } else {
                                    if ($user->$userfield == 'country') {
                                        $choices = get_string_manager()->get_list_of_countries();
                                        $customparams[$param->name] = $user->$userfield;
                                        $param->value = $choices[$user->$userfield];
                                    } else {
                                        $customparams[$param->name] = $user->$userfield;
                                        $param->value = $user->$userfield;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $templatedefinition = ($templatedefinition) ? json_encode($templatedefinition) : '';
        $emitdata = new \stdclass;
        $emitdata->email = $user->email;
        $emitdata->institution = get_config('mod_pokcertificate', 'institution');
        $emitdata->identification = $user->idnumber;
        $emitdata->first_name = $user->firstname;
        $emitdata->last_name = $user->lastname;
        $emitdata->title = $pokrecord->get('title');
        $emitdata->template_base64 = (!empty($templatedefinition)) ? base64_encode($templatedefinition) : '';
        $emitdata->date = time();
        $emitdata->free = ($template->get('templatetype') == 0) ? true : false;
        $emitdata->wallet = get_config('mod_pokcertificate', 'wallet');
        $emitdata->language_tag = $user->lang;
        if (!empty($customparams)) {
            $emitdata->custom_params = $customparams;
        }

        return $emitdata;
    }

    /**
     * Save the certificate details issued to user.
     *
     * @param  int $cmid
     * @param  object $user
     * @param  object $certificate
     * @return [void]
     */
    public static function save_issued_certificate($cmid, $user, $certificate) {
        $cm = self::get_cm_instance($cmid);
        try {

            $pokrecord = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);
            $pokissuedataexists = pokcertificate_issues::get_record(['pokid' => $cm->instance, 'userid' => $user->id]);

            $data = new \stdClass;
            $data->pokid = $pokrecord->get('id');
            $data->userid = $user->id;
            $data->useremail = $user->email;
            $data->status = (isset($certificate->status)) ? $certificate->status : false;
            $data->templateid = $pokrecord->get('templateid');
            $data->certificateurl = (isset($certificate->viewUrl)) ? $certificate->viewUrl : '';
            $data->pokcertificateid = (isset($certificate->id)) ? $certificate->id : 0;

            if ($pokissuedataexists) {
                $data->timemodified = time();
                $data->id = $pokissuedataexists->get('id');
                $issues = new pokcertificate_issues(0, $data);
                $issues->update();
            } else {
                $data->timecreated = time();
                $issues = new pokcertificate_issues(0, $data);
                $issues->create();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get fields that are mapped to course module.
     *
     * @param  mixed $user
     * @param  mixed $cm
     * @return object $pokfields fields data
     */
    public static function get_mapping_fields($user, $cm) {
        $pokrecord = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);
        $pokfields = pokcertificate_fieldmapping::get_records(['pokid' => $pokrecord->get('id')]);
        return $pokfields;
    }

    /**
     * Check if mapped user field has user data.
     *
     * @param  int $cmid
     * @param  object $user
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

    /**
     * Gets list of users for whom the certificate is not issued.
     *
     * @param \stdClass $pokcertificate
     * @param \cm_info $cm
     * @return array
     */
    public static function get_users_to_issue($pokcertificate, $cm) {

        // Get users already issued subquery.
        $users = self::get_notissued_users_list(
            $pokcertificate->id,
            $pokcertificate->course,
            $pokcertificate->templateid
        );

        // Filter only users with access to the activity.
        $info = new info_module($cm);
        $filteredusers = $info->filter_user_list($users);

        $users = [];
        foreach ($filteredusers as $filtereduser) {

            $modinfo = get_fast_modinfo($cm->get_course(), $filtereduser->userid);
            $cmuser = $modinfo->get_cm($cm->id);

            if ($cmuser && $cmuser->uservisible && $cmuser->available) {
                $users[] = $filtereduser;
            }
        }

        return $users;
    }

    /**
     * Returns select for the users that have been not issued
     *
     * @param int $pokid
     * @param int $courseid
     * @param int $templateid
     * @return array
     */
    private static function get_notissued_users_list(int $pokid, int $courseid, int $templateid): array {
        global $DB;
        $sql = "SELECT DISTINCT poki.* FROM {" . pokcertificate_issues::TABLE . "} poki
                    JOIN {" . pokcertificate::TABLE . "} pok ON pok.templateid = poki.templateid AND pok.id=poki.pokid
                WHERE pok.course = :courseid AND pok.templateid = :templateid
                      AND poki.status = 0 AND poki.certificateurl IS NULL OR poki.certificateurl = ''
                      AND pok.id = :pokid ";
        $params = [
            'courseid' => $courseid,
            'templateid' => $templateid,
            'pokid' => $pokid,
        ];
        $users = $DB->get_records_sql($sql, $params);
        return $users;
    }

    /**
     * Verifies whether user has mapped field data if template has custom fields and if valid
     * checking
     *
     * @param  mixed $cm
     * @param  mixed $user
     * @return void
     */
    public static function auto_emit_certificate($cm, $user) {

        $link = '';

        $pokissuerec = pokcertificate_issues::get_record(['pokid' => $cm->instance, 'userid' => $user->id]);
        if ((empty($pokissuerec)) ||
            ($pokissuerec && $pokissuerec->get('useremail') != $user->email)
        ) {
            $validuser = check_usermapped_fielddata($cm, $user);
            if ($validuser) {
                $emitcertificate = self::emit_certificate($cm->id, $user);
                if ($emitcertificate) {
                    $link = \html_writer::tag(
                        'p',
                        get_string('certificateissuemsg', 'mod_pokcertificate') . $user->email,
                        [
                            'class' => 'success-complheading',
                            'style' => 'font-size: .875em; color: #495057;',
                        ]
                    );
                }
            }
        }
        return $link;
    }
}

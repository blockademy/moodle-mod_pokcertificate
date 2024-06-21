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
 * Describe file fieldmapping
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pokcertificate\pok;
use mod_pokcertificate\form\fieldmapping_form;
use mod_pokcertificate\helper;

require('../../config.php');
require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');

$id = required_param('id', PARAM_INT); // Course module id.
$tempname = required_param('temp', PARAM_RAW); // Selected template name.
$temptype = optional_param('type', 0, PARAM_INT);

if ($id && !$cm = get_coursemodule_from_id('pokcertificate', $id)) {
    throw new \moodle_exception('invalidcoursemodule');
}

$pokcertificate = $DB->get_record('pokcertificate', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = \context_module::instance($cm->id);
$url = new moodle_url('/mod/pokcertificate/fieldmapping.php', ['id' => $id, 'temp' => $tempname]);
require_capability('mod/pokcertificate:manageinstance', $context);

$PAGE->set_url('/mod/pokcertificate/view.php', ['id' => $cm->id]);
$PAGE->set_title($course->shortname . ': ' . $pokcertificate->name);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');
$PAGE->set_activity_record($pokcertificate);
$renderer = $PAGE->get_renderer('mod_pokcertificate');
$renderer->verify_authentication_check();
// Save selected template definition.
if (!empty(trim($tempname)) && helper::validate_encoded_data($tempname)) {
    $templatename = base64_decode($tempname);
    $templateinfo = new \stdclass;
    $templateinfo->template = $templatename;
    $templateinfo->templatetype = $temptype;
    $templatedefinition = (new \mod_pokcertificate\api)->get_template_definition($templatename);

    if ($templatedefinition) {
        $data = pok::save_template_definition($templateinfo, $templatedefinition, $cm);

        $remotefields = helper::get_externalfield_list($tempname, $pokcertificate->id);
        $data = data_submitted();
        if ($remotefields) {
            $pokid = $pokcertificate->id;
            $templateid = $pokcertificate->templateid;
            $fielddata = helper::get_mapped_fields($pokid);

            $mform = new fieldmapping_form(
                $url,
                ['data' => $fielddata, 'id' => $id, 'template' => $tempname, 'templateid' => $templateid, 'pokid' => $pokid] + (array)$data
            );

            $redirecturl = new moodle_url('/course/view.php', ['id' => $cm->course]);

            if ($mform->is_cancelled()) {
                $certificateslink = new \moodle_url('/mod/pokcertificate/certificates.php', ['id' => $id]);
                redirect($certificateslink);
            } else if ($data = $mform->get_data()) {
                $return = pok::save_fieldmapping_data($data);
                if ($return) {
                    redirect($redirecturl);
                }
            } else {
                echo $OUTPUT->header();
                $mform->display();
            }
        } else {
            $preview = pok::preview_template($id);
            if ($preview) {
                $params = ['id' => $id];
                $url = new moodle_url('/mod/pokcertificate/preview.php', $params);
                redirect($url);
            }
        }
    } else {
        echo $OUTPUT->header();
        $url = new moodle_url('/mod/pokcertificate/certificates.php', ['id' => $id]);
        echo $output = notice('<p class="errorbox alert alert-danger">' .
            get_string('invalidtemplatedef', 'mod_pokcertificate') . '</p>', $url);
    }
} else {
    echo $OUTPUT->header();
    $url = new moodle_url('/mod/pokcertificate/certificates.php', ['id' => $id]);
    echo $output = notice('<p class="errorbox alert alert-danger">' .
        get_string('invalidtemplate', 'mod_pokcertificate') . '</p>', $url);
}
echo $OUTPUT->footer();

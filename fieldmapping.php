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
 * TODO describe file fieldmapping
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pokcertificate\api;
use mod_pokcertificate\persistent\pokcertificate_fieldmapping;

require('../../config.php');

require_login();
require_once($CFG->dirroot . '/mod/pokcertificate/fieldmapping_form.php');

$id  = required_param('id', PARAM_INT);
$tempname = optional_param('temp', '', PARAM_RAW);
$template = base64_decode($tempname);

if ($id && !$cm = get_coursemodule_from_id('pokcertificate', $id)) {
    throw new \moodle_exception('invalidcoursemodule');
}
$pokcertificate = $DB->get_record('pokcertificate', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
$url = new moodle_url('/mod/pokcertificate/fieldmapping.php', ['id' => $id]);

$PAGE->set_url('/mod/pokcertificate/view.php', ['id' => $cm->id]);
$PAGE->set_title($course->shortname . ': ' . $pokcertificate->name);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');
$PAGE->set_activity_record($pokcertificate);

echo $OUTPUT->header();
// Save selected template definition.
if ($tempname) {
    $template = base64_decode($tempname);
    $data = api::save_template_definition($template, $cm);
}

$certid = $pokcertificate->id;
$templateid = $pokcertificate->templateid;
$fielddata = get_mapped_fields($certid);

$mform = new mod_pokcertificate_fieldmapping_form(
    $url,
    ['data' => $fielddata, 'id' => $id, 'template' => $tempname, 'templateid' => $templateid, 'certid' => $certid]
);
$redirecturl = new moodle_url('/course/view.php', ['id' => $cm->course]);

if ($mform->is_cancelled()) {
    redirect($url);
} else if ($data = $mform->get_data()) {
    $return = api::save_fieldmapping_data($data);
    if ($return) {
        redirect($redirecturl);
    }
} else {
    $mform->display();
}
echo $OUTPUT->footer();

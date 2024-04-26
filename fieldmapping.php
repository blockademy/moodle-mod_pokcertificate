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

require('../../config.php');

global $CFG, $USER;

require_login();
require_once($CFG->dirroot . '/mod/pokcertificate/fieldmapping_form.php');

$id  = required_param('id', PARAM_INT);
$tempname = required_param('temp', PARAM_RAW);
$template = base64_decode($tempname);

$cm = get_coursemodule_from_id('pokcertificate', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

$url = new moodle_url('/mod/pokcertificate/fieldmapping.php', ['id' => $id, 'temp' => $tempname]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title($course->shortname . ': ' . $pokcertificate->name);
$PAGE->set_heading($course->fullname);
// $PAGE->requires->js(new moodle_url('/mod/pokcertificate/fieldmap.js'));
$PAGE->requires->js_call_amd("mod_pokcertificate/pokcertificate", "init");

echo $OUTPUT->header();
// Save selected template definition.
if ($template) {
    $data = api::save_template_definition($template, $cm);
    $certid = $data['certid'];
    $templateid = $data['templateid'];
}

// $html = fieldmapping::get_mapping_fields();

$mform = new mod_pokcertificate_fieldmapping_form($url, ['id' => $id, 'template' => $tempname, 'templateid' => $templateid, 'certid' => $certid]);
$redirecturl = new moodle_url('/mod/pokcertificate/view.php', ['id' => $id]);

if ($mform->is_cancelled()) {
    redirect($url);
} else if ($mform->get_data()) {
    $data = $mform->get_data();
    $return = api::save_fieldmapping_data($data);
    if ($return) {
        redirect($url);
    }
} else {
    $mform->display();
}
echo $OUTPUT->footer();

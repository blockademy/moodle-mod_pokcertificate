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
 * Describe file preview
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID.
$url = new moodle_url('/mod/pokcertificate/preview.php', ['id' => $id]);

if (!$cm = get_coursemodule_from_id('pokcertificate', $id)) {
    throw new \moodle_exception('invalidcoursemodule');
}
$pokcertificate = $DB->get_record('pokcertificate', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pokcertificate:view', $context);
$PAGE->navbar->add(get_string('preview'));
$PAGE->set_url('/mod/pokcertificate/view.php', ['id' => $cm->id]);
$PAGE->set_title($course->shortname . ': ' . $pokcertificate->name);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');
$PAGE->set_activity_record($pokcertificate);

$renderer = $PAGE->get_renderer('mod_pokcertificate');
$renderer->verify_authentication_check();
echo $OUTPUT->header();
echo $renderer->action_bar($id, $PAGE->url);
echo $renderer->preview_certificate_template($id);

echo $OUTPUT->footer();

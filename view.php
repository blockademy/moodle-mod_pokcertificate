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
 * Page module version information
 *
 * @package mod_pokcertificate
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$p       = optional_param('p', 0, PARAM_INT);  // Page instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);

if ($p) {
    if (!$pokcertificate = $DB->get_record('pokcertificate', ['id' => $p])) {
        throw new \moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('pokcertificate', $pokcertificate->id, $pokcertificate->course, false, MUST_EXIST);
} else {
    if (!$cm = get_coursemodule_from_id('pokcertificate', $id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    $pokcertificate = $DB->get_record('pokcertificate', ['id' => $cm->instance], '*', MUST_EXIST);
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pokcertificate:view', $context);

// Completion and trigger events.
pokcertificate_view($pokcertificate, $course, $cm, $context);

$PAGE->set_url('/mod/pokcertificate/view.php', ['id' => $cm->id]);
$PAGE->requires->js_call_amd("mod_pokcertificate/pokcertificate", "init");

$options = empty($pokcertificate->displayoptions) ? [] : (array) unserialize_array($pokcertificate->displayoptions);

$activityheader = ['hidecompletion' => false];
if (empty($options['printintro'])) {
    $activityheader['description'] = '';
}

if ($inpopup and $pokcertificate->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title($course->shortname . ': ' . $pokcertificate->name);
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->add_body_class('limitedwidth');
    $PAGE->set_title($course->shortname . ': ' . $pokcertificate->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($pokcertificate);
    if (!$PAGE->activityheader->is_title_allowed()) {
        $activityheader['title'] = "";
    }
}
$PAGE->activityheader->set_attrs($activityheader);
echo $OUTPUT->header();

if (!isset($options['printlastmodified']) || !empty($options['printlastmodified'])) {
    $strlastmodified = get_string("lastmodified");
    echo html_writer::div("$strlastmodified: " . userdate($pokcertificate->timemodified), 'modified');
}
if (has_capability('mod/pokcertificate:manageinstance', $context)) {
    $renderer = $PAGE->get_renderer('mod_pokcertificate');
    echo $renderer->show_certificate_templates($id);
} else {

    echo '<a class="btn btn-primary certbutton" data-action="previewtemplate" tabindex="0" aria-selected="true">Preview Template</a>';
    echo '<a href= "http://localhost/moodle/mod/pokcertificate/preview.php?id=35" class="btn btn-primary certbutton" data-action="previewtemplate" tabindex="0" aria-selected="true">Preview Template</a>';

    $renderer = $PAGE->get_renderer('mod_pokcertificate');
    echo $renderer->preview_cetificate_template($id);
}

echo $OUTPUT->footer();

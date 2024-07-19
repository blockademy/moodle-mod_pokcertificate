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
 * pokcertificate view page
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pokcertificate\pok;
use mod_pokcertificate\helper;

require('../../config.php');
require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/utility.php');
require_once($CFG->libdir . '/completionlib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID.
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);
$flag = optional_param('flag', 0, PARAM_BOOL);

if (!$cm = get_coursemodule_from_id('pokcertificate', $id)) {
    throw new \moodle_exception('invalidcoursemodule');
}
$pokcertificate = $DB->get_record('pokcertificate', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

$context = \context_module::instance($cm->id);
require_capability('mod/pokcertificate:view', $context);

// Completion and trigger events.
pokcertificate_view($pokcertificate, $course, $cm, $context);

$PAGE->set_url('/mod/pokcertificate/view.php', ['id' => $cm->id]);
$PAGE->requires->js_call_amd("mod_pokcertificate/pokcertificate", "init");
require_course_login($course, true, $cm);
$options = empty($pokcertificate->displayoptions) ? [] : (array) unserialize_array($pokcertificate->displayoptions);

$activityheader = ['hidecompletion' => false];
if (empty($options['printintro'])) {
    $activityheader['description'] = '';
}
$PAGE->set_title($course->shortname . ': ' . $pokcertificate->name);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');
$PAGE->activityheader->set_attrs($activityheader);
$PAGE->set_subpage('certificates');
$renderer = $PAGE->get_renderer('mod_pokcertificate');
$renderer->verify_authentication_check();

pok::set_cmid($id);
if ($pok = helper::pokcertificate_preview_by_user($cm, $pokcertificate, $flag)) {

    if ($pok['url']) {
        redirect($pok['url']);
    } else if ($pok['student']) {
        echo $OUTPUT->header();
        echo $renderer->emit_certificate_templates($id, $USER);
        echo $OUTPUT->footer();
        exit;
    }
}
echo $OUTPUT->render_from_template('mod_pokcertificate/loader', []);

echo $OUTPUT->header();
echo $renderer->show_certificate_templates($id);
echo $OUTPUT->footer();

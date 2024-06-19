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
 * Course Certificate Status View Page
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pokcertificate\form\searchfilter_form;

require_once('../../config.php');
global $OUTPUT, $PAGE, $CFG, $DB;

// Set up page context and heading.
$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid]);
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);
require_capability('mod/pokcertificate:manageinstance', $context);
require_capability('mod/pokcertificate:managecoursecertificatestatus', $context);

$url = new \moodle_url('/mod/pokcertificate/coursecertificatestatus.php', ['courseid' => $courseid]);
$heading = get_string('coursecertificatestatus', 'mod_pokcertificate');
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_heading($course->fullname);
$PAGE->set_title($heading);
$PAGE->set_url($url);
$studentid = optional_param('studentid', '', PARAM_RAW);
$studentname = optional_param('studentname', '', PARAM_RAW);
$email = optional_param('email', '', PARAM_RAW);
$senttopok = optional_param('senttopok', '', PARAM_RAW);
$coursestatus = optional_param('coursestatus', '', PARAM_RAW);
if (!empty($studentid) || !empty($studentname) || !empty($email) || !empty($senttopok) || !empty($coursestatus)) {
    $show = 'show';
} else {
    $show = '';
}

$mform = new searchfilter_form('', ['viewtype' => 'participaints', 'courseid' => $courseid]);
$mform->set_data([
    'courseid' => $courseid,
    'studentid' => $studentid,
    'studentname' => $studentname,
    'email' => $email,
    'senttopok' => $senttopok,
    'coursestatus' => $coursestatus,
]);

if ($mform->is_cancelled()) {
    redirect(new \moodle_url('/mod/pokcertificate/coursecertificatestatus.php', ['courseid' => $courseid]));
} else if ($userdata = $mform->get_data()) {
    redirect(new \moodle_url(
        '/mod/pokcertificate/coursecertificatestatus.php',
        [
            'courseid' => $userdata->courseid,
            'studentid' => $userdata->studentid,
            'studentname' => $studentname,
            'email' => $email,
            'senttopok' => $userdata->senttopok,
            'coursestatus' => $userdata->coursestatus,
        ]
    ));
}

echo $OUTPUT->header();

echo '<a class = "btn-link btn-sm" data-toggle = "collapse"
    data-target = "#mod_pokcertificate-filter_collapse"
    aria-expanded = "false" aria-controls = "mod_pokcertificate-filter_collapse">
        <i class = "m-0 fa fa-sliders fa-2x" aria-hidden = "true"></i>
    </a>';
echo '<div class = "mt-3 mb-2 collapse ' . $show . '"
    id = "mod_pokcertificate-filter_collapse">
        <div id = "filters_form" class = "card card-body p-2">';
$mform->display();
echo    '</div>
    </div>';

$renderer = $PAGE->get_renderer('mod_pokcertificate');
$records = $renderer->get_coursecertificatestatuslist();
echo $records['recordlist'];
echo $records['pagination'];

echo $OUTPUT->footer();

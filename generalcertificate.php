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
 * index to view award general certificate.
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pokcertificate\form\searchfilter_form;

require_once('../../config.php');

global $OUTPUT, $PAGE, $USER;

$courseid = optional_param('courseid', 0, PARAM_INT);
$context = \context_system::instance();
if ($courseid > 0) {
    $context = context_course::instance($courseid, MUST_EXIST);
    if (!is_enrolled($context, $USER->id)) {
        throw new \moodle_exception('usernotincourse');
    }
}
require_capability('mod/pokcertificate:manageinstance', $context);
require_capability('mod/pokcertificate:awardcertificate', $context);

$url = new \moodle_url('/mod/pokcertificate/generalcertificate.php', []);
$heading = get_string('awardcertificate', 'mod_pokcertificate');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_heading($heading);
$PAGE->set_title($heading);
$PAGE->set_url($url);
require_login();
$course = optional_param('course', 0, PARAM_INT);
$studentid = optional_param('studentid', '', PARAM_RAW);
$studentname = optional_param('studentname', '', PARAM_RAW);
$email = optional_param('email', '', PARAM_RAW);
$certificatestatus = optional_param('certificatestatus', '', PARAM_RAW);

if (!empty($course) || !empty($studentid) || !empty($studentname) || !empty($email) || !empty($certificatestatus)) {
    $show = 'show';
} else {
    $show = '';
}

$renderer = $PAGE->get_renderer('mod_pokcertificate');
$mform = new searchfilter_form('', ['viewtype' => 'generalcertificate', 'courseid' => $courseid]);
$mform->set_data([
    'course' => $course,
    'studentid' => $studentid,
    'studentname' => $studentname,
    'email' => $email,
    'certificatestatus' => $certificatestatus,
    'courseid' => $courseid,
]);

if ($mform->is_cancelled()) {
    $urlparams = [];
    if ($courseid > 0) {
        $urlparams = ['courseid' => $courseid];
    }
    redirect(new \moodle_url('/mod/pokcertificate/generalcertificate.php', $urlparams));
} else if ($userdata = $mform->get_data()) {
    redirect(new \moodle_url(
        '/mod/pokcertificate/generalcertificate.php',
        [
            'course' => $course,
            'studentid' => $studentid,
            'studentname' => $studentname,
            'email' => $email,
            'certificatestatus' => $certificatestatus,
            'courseid' => $courseid,
        ]
    ));
}

echo $OUTPUT->header();
echo $renderer->display_tabs();
echo $renderer->display_search_form($show, $mform);
$records = $renderer->get_generalcertificate();
echo $records['recordlist'];
echo $records['pagination'];

echo $OUTPUT->footer();

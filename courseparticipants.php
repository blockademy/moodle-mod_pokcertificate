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
 * User bulk upload form
 *
 * @package     mod_pokcertificate
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Narendra.Patel <narendra.patel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
global $OUTPUT, $PAGE, $CFG;
require_once($CFG->dirroot . '/mod/pokcertificate/classes/form/searchfilter_form.php');
require_login();

// Set up page context and heading.
$context = context_system::instance();
$courseid = required_param('courseid', PARAM_INT);
$url = new \moodle_url('/mod/pokcertificate/courseparticipants.php', ['courseid' => $courseid]);
$heading = get_string('courseparticipants', 'mod_pokcertificate');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_heading($heading);
$PAGE->set_title($heading);
$PAGE->set_url($url);
$studentid = optional_param('studentid', '', PARAM_RAW);
$studentname = optional_param('studentname', '', PARAM_RAW);
$email = optional_param('email', '', PARAM_RAW);
$senttopok = optional_param('senttopok', '', PARAM_RAW);
$coursestatus = optional_param('coursestatus', '', PARAM_RAW);
if (!empty($studentid)||!empty($studentname)||!empty($email)||!empty($senttopok)||!empty($coursestatus)) {
    $show = 'show';
} else {
    $show = '';
}
echo $OUTPUT->header();
$mform = new \searchfilter_form('', ['viewtype' => 'participaints', 'courseid' => $courseid]);
$mform->set_data([
    'courseid' => $courseid,
    'studentid' => $studentid,
    'studentname' => $studentname,
    'email' => $email,
    'senttopok' => $senttopok,
    'coursestatus' => $coursestatus,
]);
if ($mform->is_cancelled()) {
    redirect(new \moodle_url('/mod/pokcertificate/courseparticipants.php', ['courseid' => $courseid]));
} else if ($userdata = $mform->get_data()) {
    redirect(new \moodle_url('/mod/pokcertificate/courseparticipants.php',
        ['courseid' => $userdata->courseid,
        'studentid' => $userdata->studentid,
        'studentname' => $studentname,
        'email' => $email,
        'senttopok' => $userdata->senttopok,
        'coursestatus' => $userdata->coursestatus]
    ));
} else {
    echo '<a class = "btn-link btn-sm" data-toggle = "collapse"
        data-target = "#mod_pokcertificate-filter_collapse"
        aria-expanded = "false" aria-controls = "mod_pokcertificate-filter_collapse">
            <i class = "m-0 fa fa-sliders fa-2x" aria-hidden = "true"></i>
        </a>';
    echo '<div class = "mt-2 mb-2 collapse '.$show.'"
        id = "mod_pokcertificate-filter_collapse">
            <div id = "filters_form" class = "card card-body p-2">';
                $mform->display();
    echo    '</div>
        </div>';
}
$renderer = $PAGE->get_renderer('mod_pokcertificate');
$records = $renderer->get_courseparticipantslist();
echo $records['recordlist'];
echo $records['pagination'];
echo $OUTPUT->footer();

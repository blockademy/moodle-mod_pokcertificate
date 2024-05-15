<?php
global $CFG;
// use mod_pokcertificate\form\mod_pokcertificate_searchfilter_form;
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/pokcertificate/classes/form/searchfilter_form.php');
require_login();

// Set up page context and heading
$context = context_system::instance();
$courseid = required_param('courseid', PARAM_INT);
$studentid = optional_param('studentid', '', PARAM_RAW);
$senttopok = optional_param('senttopok', '', PARAM_RAW);
$coursestatus = optional_param('coursestatus', '', PARAM_RAW);
$url = new \moodle_url('/mod/pokcertificate/courseparticipants.php', ['courseid' => $courseid]);
$heading = get_string('courseparticipants', 'mod_pokcertificate');
$PAGE->set_context($context);
$PAGE->set_heading($heading);
$PAGE->set_title($heading);

global $DB, $OUTPUT;
echo $OUTPUT->header();
$mform = new \searchfilter_form('', ['viewtype' => 'participaints']);
$mform->set_data(['courseid' => $courseid, 'studentid' => $studentid, 'senttopok' => $senttopok, 'coursestatus' => $coursestatus]);
if ($mform->is_cancelled()) {
    redirect(new \moodle_url('/mod/pokcertificate/courseparticipants.php', ['courseid' => $courseid]));
} else if ($userdata = $mform->get_data()) {

    redirect(new \moodle_url('/mod/pokcertificate/courseparticipants.php', ['courseid' => $userdata->courseid, 'studentid' => $userdata->studentid, 'senttopok' => $userdata->senttopok, 'coursestatus' => $userdata->coursestatus]));
} else {
    $mform->display();
}
$renderer = $PAGE->get_renderer('mod_pokcertificate');
$records = $renderer->get_courseparticipantslist();
echo $records['recordlist'];
echo $records['pagination'];
echo $OUTPUT->footer();

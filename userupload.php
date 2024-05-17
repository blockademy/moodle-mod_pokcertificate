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
 * User bulk upload
 *
 * @package     mod_pokcertificate
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Narendra.Patel <narendra.patel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/mod/pokcertificate/userupload_form.php');
$iid = optional_param('iid', '', PARAM_INT);
@set_time_limit(60 * 60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();

$errorstr = get_string('error');
$stryes = get_string('yes');
$strno = get_string('no');
$stryesnooptions = [0 => $strno, 1 => $stryes];

$systemcontext = \context_system::instance();
$PAGE->set_context($systemcontext);

global $USER, $DB , $OUTPUT;

$returnurl = new moodle_url('/mod/pokcertificate/incompletestudent.php');

$PAGE->set_url('/mod/pokcertificate/userupload.php');
$PAGE->set_heading(get_string('bulkupload', 'mod_pokcertificate'));
$strheading = get_string('bulkupload', 'mod_pokcertificate');
$PAGE->set_title($strheading);
$returnurl = new moodle_url('/mod/pokcertificate/incompletestudent.php');
$PAGE->navbar->add(get_string('incompletestudent', 'mod_pokcertificate'), $returnurl);
$PAGE->navbar->add(get_string('bulkupload', 'mod_pokcertificate'));

$stdfields = [
    'username'      => 'username',
    'studentname'   => 'studentname',
    'surname'       => 'surname',
    'email'         => 'email',
    'studentid'     => 'studentid',
];


$rpffields = [];
// If variable $iid equal to zero,it allows enter into the form.
$mform1 = new mod_pokcertificate_userupload_form();
if ($mform1->is_cancelled()) {
    redirect($returnurl);
}
if ($formdata = $mform1->get_data()) {
    echo $OUTPUT->header();
    $iid = csv_import_reader::get_new_iid('userfile');
    $cir = new csv_import_reader($iid, 'userfile'); // This class fromcsvlib.php(includes csv methods and classes).
    $content = $mform1->get_file_content('userfile');
    $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
    $cir->init();
    $linenum = 1;

    $progresslibfunctions = new mod_pokcertificate\cron\progresslibfunctions();
    $filecolumns = $progresslibfunctions->uu_validate_user_upload_columns($cir, $stdfields, $rpffields, $returnurl);

    $hrms = new mod_pokcertificate\cron\syncfunctionality();
    $hrms->main_hrms_frontendform_method($cir, $filecolumns, $formdata);
    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->header();
    echo html_writer::tag(
        'a',
        get_string('back', 'mod_pokcertificate'),
        [
            'href' => $CFG->wwwroot . '/mod/pokcertificate/incompletestudent.php',
            'class' => "btn btn-secondary ml-2 float-right",
        ]
    );
    echo html_writer::tag(
        'a',
        get_string('sample', 'mod_pokcertificate'),
        [
            'href' => $CFG->wwwroot . '/mod/pokcertificate/userupload_sample.php',
            'class' => "btn btn-secondary ml-2 float-right",
        ]
    );
    echo html_writer::tag(
        'a',
        get_string('help_manual', 'mod_pokcertificate'),
        [
            'href' => $CFG->wwwroot . '/mod/pokcertificate/userupload_help.php',
            'class' => "btn btn-secondary ml-2 float-right",
        ]
    );
    $mform1->display();
    echo $OUTPUT->footer();
    die;
}

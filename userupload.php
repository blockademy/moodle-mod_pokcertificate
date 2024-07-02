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
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');

use mod_pokcertificate\form\userupload_form;
use mod_pokcertificate\local\progresslibfunctions;
use mod_pokcertificate\local\syncfunctionality;

global $USER, $DB, $OUTPUT;

$iid = optional_param('iid', '', PARAM_INT);
@set_time_limit(60 * 60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

$systemcontext = \context_system::instance();
require_capability('mod/pokcertificate:bulkupdateincompleteprofile', $systemcontext);

$errorstr = get_string('error');
$stryes = get_string('yes');
$strno = get_string('no');
$stryesnooptions = [0 => $strno, 1 => $stryes];

$PAGE->set_context($systemcontext);

$returnurl = new moodle_url('/mod/pokcertificate/incompletestudent.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/mod/pokcertificate/userupload.php');
$PAGE->set_heading(get_string('bulkupload', 'mod_pokcertificate'));
$strheading = get_string('bulkupload', 'mod_pokcertificate');
$PAGE->set_title($strheading);

require_login();
$returnurl = new moodle_url('/mod/pokcertificate/incompletestudent.php');

$stdfields = [
    'username'      => 'username',
    'studentname'   => 'studentname',
    'surname'       => 'surname',
    'email'         => 'email',
    'studentid'     => 'studentid',
];


$rpffields = [];
// If variable $iid equal to zero,it allows enter into the form.
$mform = new userupload_form();
if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($formdata = $mform->get_data()) {
    echo $OUTPUT->header();
    $iid = csv_import_reader::get_new_iid('userfile');
    $cir = new csv_import_reader($iid, 'userfile'); // This class fromcsvlib.php(includes csv methods and classes).
    $content = $mform->get_file_content('userfile');
    $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
    $cir->init();

    $progresslibfunctions = new progresslibfunctions();
    $filecolumns = $progresslibfunctions->uu_validate_user_upload_columns($cir, $stdfields, $rpffields, $returnurl);

    $hrms = new syncfunctionality();
    $hrms->main_hrms_frontendform_method($cir, $filecolumns, $formdata);
    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->header();
    $renderer = $PAGE->get_renderer('mod_pokcertificate');
    echo $renderer->render_upload_buttons();
    $mform->display();
    echo $OUTPUT->footer();
    die;
}

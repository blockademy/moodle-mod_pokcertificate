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
 * TODO describe file pokcertificate
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();
require_once($CFG->dirroot . '/mod/pokcertificate/verifyauth_form.php');

$url = new moodle_url('/mod/pokcertificate/pokcertificate.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading($SITE->fullname);

$PAGE->requires->js_call_amd("mod_pokcertificate/pokcertificate", "init");

echo $OUTPUT->header();
echo $OUTPUT->container_start();

$url = $CFG->wwwroot . '/mod/pokcertificate/pokcertificate.php';
$mform = new mod_pokcertificate_verifyauth_form();

if ($form_data = $mform->get_data()) {
}

echo
'<div class="row verification_content">

    <div class="col-md-8">
        <div class="verification_form">';
$mform->display();
echo   '</div>
    </div>
    <div class=" col-md-4">
    <div class="verification_form">
        <h5 class="" style ="border-bottom: 1px solid #f0f0f0;">' . get_string("certficatestobesent", "pokcertificate") . ' : </h5>

        <h5 class="" style ="border-bottom: 1px solid #f0f0f0;">' . get_string("incompleteprofile", "pokcertificate") . ' : </h5>

    </div>
</div>
</div>';

echo $OUTPUT->container_end();

echo $OUTPUT->footer();

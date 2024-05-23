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
 * Describe file pokcertificate
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();
require_once($CFG->dirroot . '/mod/pokcertificate/verifyauth_form.php');
require_once($CFG->libdir . '/adminlib.php');

$url = new moodle_url('/mod/pokcertificate/pokcertificate.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading($SITE->fullname);

$PAGE->requires->js_call_amd("mod_pokcertificate/pokcertificate", "init");
// Restrict normal user to access this page.
admin_externalpage_setup('managemodules');

echo $OUTPUT->header();
echo $OUTPUT->container_start();

$renderer = $PAGE->get_renderer('mod_pokcertificate');
echo $renderer->display_tabs();
$data = new stdClass();

if (get_config('mod_pokcertificate', 'institution')) {
    $data->institution = get_config('mod_pokcertificate', 'institution');
}
$mform = new mod_pokcertificate_verifyauth_form(
    $url,
    ['data' => $data]
);

echo
'<div class="row mt-5 pok_details_content mx-0">
<div class="col-md-8 p-0">
        <div class="verification_form">';
$mform->display();
echo
'</div>
    </div>';

$pokverified = get_config('mod_pokcertificate', 'pokverified');

if ($pokverified) {
    echo '
    <div class="col-md-4 p-0">
      <ul class="pok_details ml-0 ml-md-5">
        <li class="d-flex justify-content-between align-items-center">
          <p class="m-0">' . get_string("certficatestobesent", "pokcertificate") . ' : </p>
          <p class="m-0 text-muted text-right">' . get_config('mod_pokcertificate', 'availablecertificate') . '</p>
        </li>
        <li class="d-flex justify-content-between border-0">
          <p class="m-0">' . get_string("incompleteprofile", "pokcertificate") . ' :</p>
          <p class="m-0 text-muted text-right">' . get_config('mod_pokcertificate', 'availablecertificate') . '</p>
        </li>
      </ul>
    </div>
  </div>';
}
echo $OUTPUT->container_end();

echo $OUTPUT->footer();

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

use mod_pokcertificate\form\verifyauth_form;

require('../../config.php');

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
$context = \context_system::instance();
require_capability('mod/pokcertificate:manageinstance', $context);

$url = new moodle_url('/mod/pokcertificate/pokcertificate.php', []);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
require_login();

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
$mform = new verifyauth_form(
    $url,
    ['data' => $data]
);

echo '<div class="row mt-5 pok_details_content mx-0">
    <div class="col-md-8 p-0">
        <div class="verification_form">';
$mform->display();
echo
'       </div>
    </div>';

$pokverified = get_config('mod_pokcertificate', 'pokverified');

if ($pokverified) {
    echo $renderer->verificationstats();
}
echo  '</div>';
echo $OUTPUT->container_end();

echo $OUTPUT->footer();

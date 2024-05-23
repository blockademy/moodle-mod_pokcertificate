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
 * Describe file help
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_login();

$url = new moodle_url('/mod/pokcertifcate/help.php', []);
$systemcontext = \context_system::instance();

$PAGE->set_url($url);
$PAGE->set_context($systemcontext);

$strheading = get_string('pluginname', 'mod_pokcertificate');
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('helpmanual', 'mod_pokcertificate', $strheading));
$PAGE->set_title($strheading);

if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}

echo $OUTPUT->header();
echo html_writer::tag(
    'a',
    get_string('back', 'mod_pokcertificate'),
    [
        'href' => $CFG->wwwroot . '/mod/pokcertificate/userupload.php',
        'class' => "btn btn-secondary ml-2 float-right",
    ]
);
echo get_string('helpmanualsdata', 'mod_pokcertificate');
echo $OUTPUT->footer();


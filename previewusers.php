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
 * TODO describe file previewusers
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

use mod_pokcertificate\helper;

global $CFG, $OUTPUT, $PAGE, $USER;
require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
$selecteditems = optional_param_array('selectedusers', null, PARAM_RAW);
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

$url = new moodle_url('/mod/pokcertificate/previewusers.php', []);
$heading = get_string('awardcertificate', 'mod_pokcertificate');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_heading($heading);
$PAGE->set_title($heading);
$PAGE->set_url($url);
require_login();
$PAGE->requires->js_call_amd("mod_pokcertificate/pokcertificate", "init");
echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('mod_pokcertificate');

echo $renderer->display_tabs();

if (!empty($selecteditems) && count($selecteditems) > 0) {

    if (helper::validate_userinputs($selecteditems)) {
        $renderer = $PAGE->get_renderer('mod_pokcertificate');
        $records = $renderer->get_userslist_topreview($selecteditems, $courseid);
        echo $records['recordlist'];
    }
}

echo $OUTPUT->footer();

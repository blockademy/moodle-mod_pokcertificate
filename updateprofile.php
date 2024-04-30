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
 * TODO describe file updateprofile
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/mod/pokcertificate/updateprofile_form.php');

require_login();

$userid  = required_param('id', PARAM_INT);
$cmid  = required_param('cmid', PARAM_INT);

$url = new moodle_url('/mod/pokcertificate/updateprofile.php', ['id' => $USER->id, 'cmid' => $cmid]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
if (!$user = $DB->get_record('user', ['id' => $userid])) {
    throw new \moodle_exception('invaliduserid');
} else {
    $cm = get_coursemodule_from_id('pokcertificate', $cmid, 0, false, MUST_EXIST);
    if (!profile_has_required_custom_fields_set($user->id)) {
        // Load user preferences.
        useredit_load_preferences($user);

        // Load custom profile fields data.
        profile_load_data($user);
        $mform = new mod_pokcertificate_updateprofile_form($url, ['user' => $user, 'cmid' => $cmid]);
        $redirecturl = new moodle_url('/course/view.php', ['id' => $cm->course]);

        if ($mform->is_cancelled()) {
            redirect($redirecturl);
        } else if ($userdata = $mform->get_data()) {
            $userdata->timemodified = time();
            // Update user with new profile data.
            user_update_user($userdata, false, false);
            // Save custom profile fields data.
            profile_save_data($userdata);
        } else {
            $mform->display();
        }
    } else {
        $renderer = $PAGE->get_renderer('mod_pokcertificate');
        echo $renderer->preview_cetificate_template($cmid);
    }
}

echo $OUTPUT->footer();

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

use mod_pokcertificate\pok;

require('../../config.php');
require_once($CFG->dirroot . '/mod/pokcertificate/updateprofile_form.php');
require_once($CFG->dirroot . '/mod/pokcertificate/classes/form/editprofile_form.php');

require_login();

global $USER, $PAGE;

$id  = optional_param('cmid', 0, PARAM_INT);
$userid  = required_param('id', PARAM_INT);

if ($id > 0) {

    $url = new moodle_url('/mod/pokcertificate/updateprofile.php', ['id' => $id]);
    if (!$cm = get_coursemodule_from_id('pokcertificate', $id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if ($userid != $USER->id || (!$user = $DB->get_record('user', ['id' => $userid]))) {
        throw new \moodle_exception('invaliduserid');
    }

    $pokcertificate = $DB->get_record('pokcertificate', ['id' => $cm->instance], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

    require_course_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    require_capability('mod/pokcertificate:view', $context);

    $PAGE->set_url('/mod/pokcertificate/view.php', ['id' => $cm->id]);
    $PAGE->set_title($course->shortname . ': ' . $pokcertificate->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->add_body_class('limitedwidth');
    $PAGE->set_activity_record($pokcertificate);

    if (!$user = $DB->get_record('user', ['id' => $userid])) {
        throw new \moodle_exception('invaliduserid');
    } else {

        $cm = get_coursemodule_from_id('pokcertificate', $id, 0, false, MUST_EXIST);

        $pokfields = pok::get_mapping_fields($user, $cm);
        // Load user preferences.
        useredit_load_preferences($user);
        // Load custom profile fields data.
        profile_load_data($user);
        $mform = new mod_pokcertificate_updateprofile_form($url, ['pokfields' => $pokfields, 'user' => $user, 'cmid' => $id]);
        $redirecturl = new moodle_url('/course/view.php', ['id' => $cm->course]);
        $renderer = $PAGE->get_renderer('mod_pokcertificate');
        if ($mform->is_cancelled()) {
            redirect($redirecturl);
        } else if ($userdata = $mform->get_data()) {
            $userdata->timemodified = time();
            // Update user with new profile data.
            user_update_user($userdata, false, false);
            // Save custom profile fields data.
            profile_save_data($userdata);
            $redirecturl = new moodle_url('/mod/pokcertificate/view.php', ['id' => $id, 'flag' => 1]);
            redirect($redirecturl);
        } else {
            echo $OUTPUT->header();
            $mform->display();
        }
    }
} else {

    $url = new moodle_url('/mod/pokcertificate/updateprofile.php', ['userid' => $userid]);
    $context = context_system::instance();
    $PAGE->set_url($url);
    $PAGE->set_context($context);
    $PAGE->set_title(get_string('profile', 'mod_pokcertificate'));
    $PAGE->set_heading(get_string('profile', 'mod_pokcertificate'));

    if (!$user = $DB->get_record('user', ['id' => $userid])) {
        throw new \moodle_exception('invaliduserid');
    } else {

        useredit_load_preferences($user);

        // Load custom profile fields data.
        profile_load_data($user);
        $mform = new \mod_pokcertificate_editprofile_form($url, ['user' => $user]);
        $redirecturl = new moodle_url('/mod/pokcertificate/incompletestudent.php');
        if ($mform->is_cancelled()) {
            redirect($redirecturl);
        } else if ($userdata = $mform->get_data()) {
            $userdata->timemodified = time();
            // Update user with new profile data.
            user_update_user($userdata, false, false);
            // Save custom profile fields data.
            profile_save_data($userdata);
            redirect($redirecturl);
        } else {
            echo $OUTPUT->header();
            $mform->display();
        }
    }
}
echo $OUTPUT->footer();

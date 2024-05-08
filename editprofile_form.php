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
 * TODO describe file updateprofile_form
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/pokcertificate/locallib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');
/**
 *form shown while adding activity.
 */
class mod_pokcertificate_editprofile_form extends \moodleform {

    public function definition() {
        global $USER;
        $mform = $this->_form;

        $user = $this->_customdata['user'];
        $userid = $user->id;

        // Next the customisable profile fields.

        $strrequired = get_string('required');
        $stringman = get_string_manager();

        $mform->addElement('static', 'currentpicture', get_string('currentpicture'));
        // Add the necessary names.
        foreach (useredit_get_required_name_fields() as $fullname) {
            $purpose = user_edit_map_field_purpose($user->id, $fullname);
            $mform->addElement('text', $fullname,  get_string($fullname),  'maxlength="100" size="30"' . $purpose);
            if ($stringman->string_exists('missing' . $fullname, 'core')) {
                $strmissingfield = get_string('missing' . $fullname, 'core');
            } else {
                $strmissingfield = $strrequired;
            }
            $mform->addRule($fullname, $strmissingfield, 'required', null, 'client');
            $mform->setType($fullname, PARAM_NOTAGS);
        }

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="30"' . $purpose);
        $mform->addRule('email', $strrequired, 'required', null, 'client');
        $mform->setType('email', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'idnumber', get_string('idnumber'), 'maxlength="255" size="25"');
        $mform->setType('idnumber', core_user::get_property_type('idnumber'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $userid);

        profile_definition($mform, $userid);

        $this->add_action_buttons(true, get_string('updatemyprofile'));

        $this->set_data($user);
    }

    /**
     * Extend the form definition after the data has been parsed.
     */
    public function definition_after_data() {
        global $USER, $CFG, $DB, $OUTPUT;

        $mform = $this->_form;
        if ($userid = $mform->getElementValue('id')) {
            $user = $DB->get_record('user', array('id' => $userid));
        } else {
            $user = false;
        }

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }

        // if ($user) {

        //     // Disable fields that are locked by auth plugins.
        //     $fields = get_user_fieldnames();
        //     $authplugin = get_auth_plugin($user->auth);
        //     $customfields = $authplugin->get_custom_user_profile_fields();
        //     $customfieldsdata = profile_user_record($userid, false);
        //     $fields = array_merge($fields, $customfields);
        //     foreach ($fields as $field) {
        //         if ($field === 'description') {
        //             // Hard coded hack for description field. See MDL-37704 for details.
        //             $formfield = 'description_editor';
        //         } else {
        //             $formfield = $field;
        //         }
        //         if (!$mform->elementExists($formfield)) {
        //             continue;
        //         }

        //         // Get the original value for the field.
        //         if (in_array($field, $customfields)) {
        //             $key = str_replace('profile_field_', '', $field);
        //             $value = isset($customfieldsdata->{$key}) ? $customfieldsdata->{$key} : '';
        //         } else {
        //             $value = $user->{$field};
        //         }

        //         $configvariable = 'field_lock_' . $field;
        //         if (isset($authplugin->config->{$configvariable})) {
        //             if ($authplugin->config->{$configvariable} === 'locked') {
        //                 $mform->hardFreeze($formfield);
        //                 $mform->setConstant($formfield, $value);
        //             } else if ($authplugin->config->{$configvariable} === 'unlockedifempty' and $value != '') {
        //                 $mform->hardFreeze($formfield);
        //                 $mform->setConstant($formfield, $value);
        //             }
        //         }
        //     }

        //     // Next the customisable profile fields.
        //     profile_definition_after_data($mform, $user->id);
        // } else {
        //     profile_definition_after_data($mform, 0);
        // }

        
        // Print picture.
        if ($user) {
            $context = context_user::instance($user->id, MUST_EXIST);
            $fs = get_file_storage();
            $hasuploadedpicture = ($fs->file_exists($context->id,
                'user', 'icon', 0, '/', 'f2.png') || $fs->file_exists(
                $context->id, 'user', 'icon', 0, '/', 'f2.jpg'));
            // print_r($hasuploadedpicture); die;
            if (!empty($user->picture) && $hasuploadedpicture) {
                $imagevalue = $OUTPUT->user_picture($user, array('courseid' => SITEID, 'size' => 66, 'link' => false));
            } else {
                $imagevalue = get_string('none');
            }
        }
        
        $imageelement = $mform->getElement('currentpicture');
        $imageelement->setValue($imagevalue);
    }

    public function validation($data, $files) {
    }
}

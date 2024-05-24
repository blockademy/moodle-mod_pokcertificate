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
 * Describe file updateprofile_form
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
 * form shown while adding activity.
 */
class mod_pokcertificate_updateprofile_form extends \moodleform {
    /**
     * Definition method for the form.
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        $user = $this->_customdata['user'];
        $cmid = $this->_customdata['cmid'];
        $pokfields = ($this->_customdata['pokfields']) ? $this->_customdata['pokfields'] : '';
        $userid = $user->id;

        $strrequired = get_string('required');
        $stringman = get_string_manager();

        $mandatoryfields = ['firstname', 'lastname', 'email', 'idnumber'];
        foreach ($mandatoryfields as $fullname) {
            $style = '';
            if (!empty($user->$fullname)) {
                $style = 'readonly="readonly"';
            }
            $mform->addElement(
                'text',
                $fullname,
                get_string($fullname, 'mod_pokcertificate'),
                'maxlength="100" size="30"' . $style
            );
            $mform->addRule($fullname, '', 'required', null, 'client');
            $mform->setType($fullname, PARAM_RAW);
            $mform->addHelpButton($fullname, $fullname, 'pokcertificate');
        }
        $translations = get_string_manager()->get_list_of_translations();
        $mform->addElement('select', 'lang', get_string('preferredlanguage'), $translations, 'disabled');
        $lang = empty($user->lang) ? $CFG->lang : $user->lang;
        $mform->setDefault('lang', $lang);

        if (!empty($pokfields)) {

            foreach ($pokfields as $field) {
                $fieldname = $field->get('userfield');

                if ((!in_array($fieldname, ['id']) && strpos($fieldname, 'profile_field_') === false)) {

                    $purpose = user_edit_map_field_purpose($user->id, $fieldname);
                    $style = '';
                    if (!empty($user->$fieldname)) {
                        $style = 'readonly="readonly"';
                    }
                    if ($fieldname == 'country') {
                        $choices = get_string_manager()->get_list_of_countries();
                        $choices = ['' => get_string('selectacountry') . '...'] + $choices;
                        $disabled = '';
                        if (!empty($user->$fieldname)) {
                            $disabled = 'disabled';
                        }
                        $mform->addElement('select', 'country', get_string('selectacountry'), $choices, $purpose . $disabled);
                        if (!empty($CFG->country)) {
                            $mform->setDefault('country', core_user::get_property_default('country'));
                        }
                    } else {
                        $mform->addElement(
                            'text',
                            $fieldname,
                            get_string($fieldname),
                            'maxlength="100" size="30"' . $purpose . $style
                        );
                        if ($stringman->string_exists('missing' . $fieldname, 'core')) {
                            $strmissingfield = get_string('missing' . $fieldname, 'core');
                        } else {
                            $strmissingfield = $strrequired;
                        }
                        $mform->addRule($fieldname, $strmissingfield, 'required', null, 'client');
                        $mform->setType($fieldname, PARAM_NOTAGS);
                    }
                }
            }
        }
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $userid);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->setDefault('cmid', $cmid);

        self::get_profile_fields($mform, $pokfields, $user);
        $this->add_action_buttons(true, get_string('updatemyprofile'));

        $this->set_data($user);
    }

    /**
     * Extend the form definition after the data has been parsed.
     */
    public function definition_after_data() {
        global  $DB;

        $mform = $this->_form;
        $userid = $mform->getElementValue('id');

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }

        if ($user = $DB->get_record('user', ['id' => $userid])) {

            // Disable fields that are locked by auth plugins.
            $fields = get_user_fieldnames();
            $authplugin = get_auth_plugin($user->auth);
            $customfields = $authplugin->get_custom_user_profile_fields();
            $customfieldsdata = profile_user_record($userid, false);
            $fields = array_merge($fields, $customfields);
            foreach ($fields as $field) {
                if ($field === 'description') {
                    // Hard coded hack for description field. See MDL-37704 for details.
                    $formfield = 'description_editor';
                } else {
                    $formfield = $field;
                }
                if (!$mform->elementExists($formfield)) {
                    continue;
                }

                // Get the original value for the field.
                if (in_array($field, $customfields)) {
                    $key = str_replace('profile_field_', '', $field);
                    $value = isset($customfieldsdata->{$key}) ? $customfieldsdata->{$key} : '';
                } else {
                    $value = $user->{$field};
                }

                $configvariable = 'field_lock_' . $field;
                if (isset($authplugin->config->{$configvariable})) {
                    if ($authplugin->config->{$configvariable} === 'locked') {
                        $mform->hardFreeze($formfield);
                        $mform->setConstant($formfield, $value);
                    } else if ($authplugin->config->{$configvariable} === 'unlockedifempty' && $value != '') {
                        $mform->hardFreeze($formfield);
                        $mform->setConstant($formfield, $value);
                    }
                }
            }

            // Next the customisable profile fields.
            profile_definition_after_data($mform, $user->id);
        } else {
            profile_definition_after_data($mform, 0);
        }
    }


    /**
     * get_profile_fields
     *
     * @param  mixed $mform
     * @param  mixed $pokfields
     * @param  mixed $user
     * @return void
     */
    public function get_profile_fields(&$mform, $pokfields, $user) {
        $categories = profile_get_user_fields_with_data_by_category($user->id);
        foreach ($categories as $categoryid => $fields) {
            // Check first if *any* fields will be displayed.
            $fieldstodisplay = [];
            if (!empty($pokfields)) {
                foreach ($pokfields as $field) {
                    $fieldname = $field->get('userfield');
                    foreach ($fields as $formfield) {
                        if ($formfield->inputname == $fieldname && $formfield->is_editable()) {

                            $fieldstodisplay[] = $formfield;
                        }
                    }
                }
            }

            if (empty($fieldstodisplay)) {
                continue;
            }

            // Display the header and the fields.
            foreach ($fieldstodisplay as $formfield) {
                $formfield->edit_field($mform);

                if ($mform->elementExists($formfield->inputname)) {
                    if ($formfield->data) {
                        $mform->hardFreeze($formfield->inputname);
                    }
                }
            }
        }
    }


    /**
     * Validates the form data submitted by the user.
     *
     * This method is responsible for validating the form data submitted by the user.
     * It performs necessary validation checks on the data and files provided.
     *
     * @param array $data An associative array containing the form data submitted by the user.
     * @param array $files An associative array containing any files uploaded via the form.
     * @return array|bool An array of validation errors, or true if validation succeeds.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}

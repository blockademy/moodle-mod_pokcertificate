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
 * Describe file fieldmapping_form
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
/**
 * form shown while adding activity.
 */
class mod_pokcertificate_fieldmapping_form extends moodleform {

    /**
     * Definition method for the form.
     */
    public function definition() {

        $mform = $this->_form;
        $id        = $this->_customdata['id'];
        $templatename  = $this->_customdata['template'];
        $templateid  = $this->_customdata['templateid'];
        $certid  = $this->_customdata['certid'];
        $data  = $this->_customdata['data'];

        $mform->addElement('header', 'fieldmapping', get_string('fieldmapping', 'pokcertificate') . "");

        $groupelem = [];
        $groupelem[] = &$mform->createElement('html', '<span>' . get_string('apifields', 'pokcertificate') . '</span>');
        $groupelem[] = &$mform->createElement(
            'html',
            '<span class="ufheader" >' . get_string('userfields', 'pokcertificate') . '</span>'
        );
        $mform->addGroup($groupelem, '', '', [' '], false, ['class' => 'mappingheaders']);

        $localfields = get_internalfield_list();
        $remotefields = get_externalfield_list($templatename, $certid);

        $repeatarray = [
            $mform->createElement('hidden', 'fieldmapping', 'fieldmapping'),

            $mform->createElement('html', '<div class = "fieldmapping">'),
            $mform->createElement(
                'select',
                'templatefield',
                '',
                $remotefields,
                ['class' => 'templatefields']
            ),

            $mform->createElement(
                'select',
                'userfield',
                '',
                $localfields,
                ['class' => 'userfields']
            ),

            $mform->createElement(
                'submit',
                'delete',
                'X',
                ['class' => 'removerow']
            ),

            $mform->createElement(
                'hidden',
                'optionid'
            ),
            $mform->createElement('html', '</div>'),

        ];

        $repeateloptions = [];
        $repeateloptions['fieldmapping']['default'] = '{no}';
        $repeateloptions['fieldmapping']['type'] = PARAM_RAW;
        $repeateloptions['templatefield']['type'] = PARAM_RAW;
        $repeateloptions['userfield']['type'] = PARAM_RAW;

        $mform->setDefault('optionid', 0);
        $mform->setType('optionid', PARAM_INT);

        $repeatno = 1;
        $count = count($remotefields);
        if ($count > 0) {
            $repeatno = $count;
        }

        $this->repeat_elements(
            $repeatarray,
            $repeatno,
            $repeateloptions,
            'option_repeats',
            'option_add_fields addfields',
            1,
            get_string('add', 'pokcertificate'),
            true,
            'delete',
        );

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'temp', $templatename);
        $mform->setType('temp', PARAM_TEXT);

        $mform->addElement('hidden', 'tempid', $templateid);
        $mform->setType('tempid', PARAM_INT);

        $mform->addElement('hidden', 'certid', $certid);
        $mform->setType('certid', PARAM_INT);

        $this->set_data($data);

        $this->add_action_buttons();
    }


    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
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
        if (!isset($data['fieldmapping']) || count($data['fieldmapping']) == 0) {
            $errors['fieldmapping'] = 'Fields to be mapped';
        }
        return $errors;
    }
}

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
 * TODO describe file fieldmapping_form
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

use mod_pokcertificate\persistent\pokcertificate_templates;
use mod_pokcertificate\persistent\pokcertificate_fieldmapping;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/pokcertificate/locallib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');

/**
 *form shown while adding activity.
 */
class mod_pokcertificate_fieldmapping_form extends moodleform {

    public function definition() {

        $mform = $this->_form;
        $id        = $this->_customdata['id'];
        $templatename  = $this->_customdata['template'];
        $templateid  = $this->_customdata['templateid'];
        $certid  = $this->_customdata['certid'];
        $data  = $this->_customdata['data'];

        $mform->addElement('header', 'fieldmapping', get_string('fieldmapping', 'pokcertificate') . "<div class ='test'> </div>");

        $localfields = $this->get_internalfield_list();
        $remotefields = $localfields; //$this->get_externalfield_list($template);

        $repeatarray = [
            $mform->createElement('hidden', 'fieldmapping', 'fieldmapping'),

            $mform->createElement(
                'select',
                'templatefield',
                get_string('apifields', 'pokcertificate'),
                $remotefields,
                ['class' => 'fieldmapping']
            ),

            $mform->createElement(
                'select',
                'userfield',
                get_string('userfields', 'pokcertificate'),
                $localfields,
                ['class' => 'fieldmapping']
            ),

            $mform->createElement(
                'submit',
                'delete',
                get_string('delete'),
                ['class' => 'deletefield']
            ),

            $mform->createElement(
                'hidden',
                'optionid'
            ),
        ];

        $repeateloptions = [];
        $repeateloptions['fieldmapping']['default'] = '{no}';
        $repeateloptions['templatefield']['type'] = PARAM_RAW;
        $repeateloptions['userfield']['type'] = PARAM_RAW;

        $mform->setDefault('optionid', 0);
        $mform->setType('optionid', PARAM_INT);

        $repeatno = 1;
        if (!empty($id)) {
            $count = pokcertificate_fieldmapping::count_records(
                ['certid' => $certid]
            );
            if ($count > 0) {
                $repeatno = $count;
            }
        }

        $this->repeat_elements(
            $repeatarray,
            $repeatno,
            $repeateloptions,
            'option_repeats',
            'option_add_fields',
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

    public function get_internalfield_list() {
        global $DB;
        $usercolumns = $DB->get_columns('user');
        $localfields = array();
        foreach ((array)$usercolumns as $key => $field) {
            $localfields[$key] = $field->name;
        }

        $allcustomfields = profile_get_custom_fields();
        $customfields = array_combine(array_column($allcustomfields, 'shortname'), $allcustomfields);
        foreach ((array)$customfields as $key => $field) {
            $localfields['profile_field_' . $key] = $field->shortname;
        }

        return $localfields;
    }

    /* Get all template definition fields
    *
    * @param string $template
    * @return array
    */
    public function get_externalfield_list($template) {

        $templatefields = [];
        $template = base64_decode($template);
        $templatedefinition = pokcertificate_templates::get_field('templatedefinition', ['templatename' => $template]);
        $templatedefinition = json_decode($templatedefinition);

        return $templatedefinition;
    }

    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
    }

    public function validation($data, $files) {
    }
}

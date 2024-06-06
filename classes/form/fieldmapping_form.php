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

namespace mod_pokcertificate\form;

defined('MOODLE_INTERNAL') || die;

use moodleform;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/pokcertificate/locallib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
/**
 * form shown while adding activity.
 */
class fieldmapping_form extends moodleform {

    /**
     * Definition method for the form.
     */
    public function definition() {

        $mform = $this->_form;
        $id        = $this->_customdata['id'];
        $templatename  = $this->_customdata['template'];
        $templateid  = $this->_customdata['templateid'];
        $pokid  = $this->_customdata['pokid'];
        $data  = $this->_customdata['data'];

        $mform->addElement('header', 'fieldmapping', get_string('fieldmapping', 'pokcertificate') . "");

        $groupelem = [];
        $groupelem[] = &$mform->createElement('html', '<div class = "fieldmappingheaders">');
        $groupelem[] = &$mform->createElement('html', '<span>' . get_string('apifields', 'pokcertificate') . '</span>');
        $groupelem[] = &$mform->createElement(
            'html',
            '<span class="ufheader" >' . get_string('userfields', 'pokcertificate') . '</span>'
        );
        $groupelem[] = &$mform->createElement('html', '</div>');
        $mform->addGroup($groupelem, '', '', [' '], false, []);

        $defaultselect = [null => get_string('select')];

        $localfields = $defaultselect + get_internalfield_list();
        $remotefields = get_externalfield_list($templatename, $pokid);
        $i = 0;
        foreach ($remotefields as $key => $value) {

            $fieldgrpelem = [];
            $fieldgrpelem[] = &$mform->createElement('html', '<div class = "fieldmapping">');
            $fieldgrpelem[] = &$mform->createElement(
                'select',
                'templatefield_' . $i . '',
                '',
                [$key => $value],
                ['class' => 'templatefields']
            );
            $fieldgrpelem[] = &$mform->createElement('html', '<span>→</span>');
            $fieldgrpelem[] = &$mform->createElement('select', 'userfield_' . $i . '', '', $localfields, ['class' => 'userfields']);
            $fieldgrpelem[] = &$mform->createElement('html', '</div>');
            $mform->addGroup($fieldgrpelem, "fieldgrpelem[$i]", '', [' '], false, []);
            $i++;
        }

        $mform->addElement('hidden', 'fieldcount', $i);
        $mform->setType('fieldcount', PARAM_INT);

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'temp', $templatename);
        $mform->setType('temp', PARAM_TEXT);

        $mform->addElement('hidden', 'tempid', $templateid);
        $mform->setType('tempid', PARAM_INT);

        $mform->addElement('hidden', 'pokid', $pokid);
        $mform->setType('pokid', PARAM_INT);

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
        if (isset($data['fieldcount']) && $data['fieldcount'] > 0) {
            for ($i = 0; $i < $data['fieldcount']; $i++) {
                if (!isset($data['userfield_' . $i]) || empty($data['userfield_' . $i])) {
                    $errors['userfield_' . $i] = 'Please select user field to be mapped';
                    $errors["fieldgrpelem[$i]"] = 'Please select user field to be mapped';
                }
            }
        }

        return $errors;
    }
}

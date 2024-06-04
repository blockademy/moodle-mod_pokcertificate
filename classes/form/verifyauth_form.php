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
 * Describe file verifyauth_form
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
 * Form shown while adding activity.
 */
class verifyauth_form extends moodleform {
    /**
     * Form elements defnations.
     */
    public function definition() {
        $data  = $this->_customdata['data'];
        $mform = $this->_form;

        $mform->addElement('header', 'pokheading', get_string('linkpokdetails', 'pokcertificate') . "<div class ='test'> </div>");

        $options = [1 => 'QA', 2 => 'LIVE'];

        $mform->addElement('select', 'prodtype', get_string('prodtype', 'pokcertificate'), $options);
        $mform->setDefault('prodtype', 1);
        $mform->addHelpButton('prodtype', 'prodtype', 'pokcertificate');

        $mform->addElement('passwordunmask', 'authtoken', get_string('authtoken', 'pokcertificate'), 'size="35"');
        $mform->setType('authtoken', PARAM_RAW);
        $mform->addHelpButton('authtoken', 'authtoken', 'pokcertificate');
        $mform->addRule('authtoken', get_string('required'), 'required', null, 'client');
        if (get_config('mod_pokcertificate', 'authenticationtoken')) {
            $mform->setDefault("authtoken", get_config('mod_pokcertificate', 'authenticationtoken'));
        }

        $institution = get_config('mod_pokcertificate', 'institution');
        $authenticationtoken =  get_config('mod_pokcertificate', 'authenticationtoken');
        if ($authenticationtoken) {
            $class = ($institution) ? 'verified' : 'notverified';
            $faicon = ($institution) ? ' fa-solid fa-circle-check' : ' fa-solid fa-circle-xmark';
            $message = ($institution) ?
                ucwords(get_string('verified', 'mod_pokcertificate')) : ucwords(get_string('notverified', 'mod_pokcertificate'));
        }

        $groupelem = [];
        $groupelem[] = &$mform->createElement(
            'text',
            'institution',
            get_string('institution', 'pokcertificate'),
            'size="35",readonly="readonly"'
        );
        $groupelem[] = &$mform->createElement('html', '<div id="verifyresponse" ><i class="' . $class . $faicon . '"></i>
            <span>' . $message . '</span></div>');
        $groupelem[] = &$mform->createElement('html', '<div class="loadElement"></div>');

        $mform->addGroup(
            $groupelem,
            'institution',
            get_string('institution', 'pokcertificate'),
            [' '],
            false,
            ['class' => 'locationtypes']
        );
        $mform->setType('institution', PARAM_TEXT);
        $mform->addHelpButton('institution', 'institution', 'pokcertificate');

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('button', 'verifyauth', get_string("verify", "pokcertificate"), "", "");
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        $this->set_data($data);
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
        $errors = [];
        $errors = parent::validation($data, $files);
        return $errors;
    }
}

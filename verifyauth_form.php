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
 * TODO describe file verifyauth_form
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/pokcertificate/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 *form shown while adding activity.
 */
class mod_pokcertificate_verifyauth_form extends moodleform {
    public function definition() {

        $mform = $this->_form;

        $mform->addElement('header', 'pokheading', get_string('linkpokdetails', 'pokcertificate') . "<div class ='test'> </div>");

        $mform->addElement('text', 'institution', get_string('institution', 'pokcertificate'), 'size="35",readonly="readonly"');
        $mform->setType('institution', PARAM_TEXT);
        $mform->addHelpButton('institution', 'institution', 'pokcertificate');


        $mform->addElement('text', 'domain', get_string('domain', 'pokcertificate'), 'size="35",readonly="readonly"');
        $mform->setType('domain', PARAM_TEXT);
        $mform->addHelpButton('domain', 'domain', 'pokcertificate');

        $mform->addElement('text', 'authtoken', get_string('authtoken', 'pokcertificate'), 'size="35"');
        $mform->setType('authtoken', PARAM_RAW);
        $mform->addHelpButton('authtoken', 'authtoken', 'pokcertificate');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('button', 'verifyauth', get_string("verify", "pokcertificate"), "", "");
        $buttonarray[] = $mform->createElement('html', '<div id="verify_response"> </div>');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    public function validation($data, $files) {
    }
}

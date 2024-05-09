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
require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');


/**
 * form shown while adding activity.
 */
class mod_pokcertificate_verifyauth_form extends moodleform {
    public function definition() {
        $data  = $this->_customdata['data'];
        $mform = $this->_form;

        $mform->addElement('header', 'pokheading', get_string('linkpokdetails', 'pokcertificate') . "<div class ='test'> </div>");

        $options = [1 => 'QA', 2 => 'LIVE'];

        $mform->addElement('select', 'prodtype', get_string('prodtype', 'pokcertificate'), $options);
        $mform->setDefault('prodtype', 1);
        $mform->addHelpButton('prodtype', 'prodtype', 'pokcertificate');

        $mform->addElement('password', 'authtoken', get_string('authtoken', 'pokcertificate'), 'size="35"');
        $mform->setType('authtoken', PARAM_RAW);
        $mform->addHelpButton('authtoken', 'authtoken', 'pokcertificate');
        if (get_config('mod_pokcertificate', 'authenticationtoken')) {
            $mform->setDefault("authtoken", get_config('mod_pokcertificate', 'authenticationtoken'));
        }

        $institution = get_config('mod_pokcertificate', 'institution');
        $class = ($institution) ? 'verified' : 'notverified';
        $faicon = ($institution) ? ' fa-solid fa-circle-check' : ' fa-solid fa-circle-xmark';
        $message = ($institution) ?
            ucwords(get_string('verified', 'mod_pokcertificate')) : ucwords(get_string('notverified', 'mod_pokcertificate'));

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

    public function validation($data, $files) {
    }
}

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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class searchfilter_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        // customdata values
        $viewtype = $this->_customdata['viewtype'];

        // Text input field
        $mform->addElement('text', 'studentid', '');
        $mform->setType('studentid', PARAM_RAW);

        if($viewtype == 'participaints') {
            // Autocomplete input field 1
            $mform->addElement('select', 'senttopok', get_string('senttopok','mod_pokcertificate'), array('' => 'select', 'yes' => 'yes', 'no' => 'no')); 
            $mform->setType('senttopok', PARAM_RAW);

            // Autocomplete input field 2
            $mform->addElement('select', 'coursestatus', get_string('coursestatus','mod_pokcertificate'), array('' => 'select', 'completed' => 'completed', 'inprogress' => 'inprogress'));
            $mform->setType('coursestatus', PARAM_RAW);

            // hidden field
            $mform->addElement('hidden', 'courseid', $courseid);
        }

        // Add submit button
        $this->add_action_buttons(true, get_string('submit'));
    }
}


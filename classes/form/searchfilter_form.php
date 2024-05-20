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

/**
 * Class searchfilter_form
 *
 * This class defines a search filter form for the mod_pokcertificate module.
 * It extends the moodleform class to provide a custom form for filtering
 * student certificates or course participants based on various criteria.
 */
class searchfilter_form extends moodleform {

    /**
     * Defines the form elements.
     *
     * This function sets up the form elements based on the view type specified
     * in the custom data. It includes fields for student ID, and if the view type
     * is 'participants', additional fields for filtering by whether the record
     * was sent to POK and the course status. It also adds a submit button.
     */
    public function definition() {
        $mform = $this->_form;

        // Customdata values.
        $viewtype = isset($this->_customdata['viewtype']);
        $courseid = isset($this->_customdata['viewtype']);

        // Text input field studentid.
        $mform->addElement('text', 'studentid', get_string('studentid', 'mod_pokcertificate'));
        $mform->setType('studentid', PARAM_RAW);

        if ($viewtype == 'participaints') {

            // Text input field studentname.
            $mform->addElement('text', 'studentname', get_string('studentname', 'mod_pokcertificate'));
            $mform->setType('studentname', PARAM_RAW);

            // Text input field email.
            $mform->addElement('text', 'email', get_string('email', 'mod_pokcertificate'));
            $mform->setType('email', PARAM_RAW);

            // Autocomplete input field 1.
            $mform->addElement('select',
                               'senttopok',
                               get_string('senttopok', 'mod_pokcertificate'),
                               [
                                    '' => get_string('select'),
                                    'yes' => get_string('yes'),
                                    'no' => get_string('no'),
                                ]);
            $mform->setType('senttopok', PARAM_RAW);

            // Autocomplete input field 2.
            $mform->addElement('select',
                               'coursestatus',
                               get_string('coursestatus', 'mod_pokcertificate'),
                               [
                                    '' => get_string('select'),
                                    'completed' => get_string('completed'),
                                    'inprogress' => get_string('inprogress', 'mod_pokcertificate'),
                                ]);
            $mform->setType('coursestatus', PARAM_RAW);

            // Hidden field.
            $mform->addElement('hidden', 'courseid', $courseid);
            $mform->setType('courseid', PARAM_INT);
        }

        // Add submit button.
        $this->add_action_buttons(true, get_string('submit'));
    }
}


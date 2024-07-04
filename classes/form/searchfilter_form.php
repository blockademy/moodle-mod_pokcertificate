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
 * Describe file searchfilter_form
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pokcertificate\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use moodleform;

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
        global $DB;
        $mform = $this->_form;

        // Customdata values.
        $viewtype = isset($this->_customdata['viewtype']) ? $this->_customdata['viewtype'] : '';
        $courseid = isset($this->_customdata['courseid']) ? $this->_customdata['courseid'] : 0;

        // Text input field studentid.
        $mform->addElement('text', 'studentid', get_string('studentid', 'mod_pokcertificate'));
        $mform->setType('studentid', PARAM_RAW);

        // Text input field studentname.
        $mform->addElement('text', 'studentname', get_string('studentname', 'mod_pokcertificate'));
        $mform->setType('studentname', PARAM_RAW);

        // Text input field email.
        $mform->addElement('text', 'email', get_string('email', 'mod_pokcertificate'));
        $mform->setType('email', PARAM_RAW);

        if ($viewtype == 'participaints' || $viewtype == 'generalcertificate') {

            if ($viewtype == 'participaints') {
                // Autocomplete input field 1.
                $mform->addElement(
                    'select',
                    'senttopok',
                    get_string('senttopok', 'mod_pokcertificate'),
                    [
                        '' => get_string('select'),
                        'yes' => get_string('yes'),
                        'no' => get_string('no'),
                    ]
                );
                $mform->setType('senttopok', PARAM_RAW);

                // Autocomplete input field 2.
                $mform->addElement(
                    'select',
                    'coursestatus',
                    get_string('coursestatus', 'mod_pokcertificate'),
                    [
                        '' => get_string('select'),
                        'completed' => get_string('completed'),
                        'inprogress' => get_string('inprogress', 'mod_pokcertificate'),
                    ]
                );
                $mform->setType('coursestatus', PARAM_RAW);
            }

            // Hidden field.
            if ($courseid > 0) {
                $mform->addElement('hidden', 'courseid', $courseid);
                $mform->setType('courseid', PARAM_INT);
            }

            if ($viewtype == 'generalcertificate') {

                // Autocomplete input field 2.
                $mform->addElement(
                    'select',
                    'certificatestatus',
                    get_string('certificatestatus', 'mod_pokcertificate'),
                    [
                        '' => get_string('select'),
                        'completed' => get_string('completed'),
                        'inprogress' => get_string('inprogress', 'mod_pokcertificate'),
                        'notissued' => get_string('notissued', 'mod_pokcertificate'),
                    ]
                );
                $mform->setType('certificatestatus', PARAM_RAW);

                // Get all courses from the database.
                $courses = ['' => get_string('selectcourse', 'mod_pokcertificate')];

                $sql = "SELECT id, fullname
                          FROM {course}
                         WHERE category > 0
                      ORDER BY fullname ";
                $courses = $courses + $DB->get_records_sql_menu($sql);

                if (!empty($courses) && has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {
                    // Add a select element to the form for choosing a course.
                    $mform->addElement(
                        'autocomplete',
                        'course',
                        get_string('course', 'mod_pokcertificate'),
                        $courses,
                        [
                            'multiple' => false,
                        ]
                    );
                    $mform->setType('course', PARAM_RAW);
                }
            }
        }

        // Add submit button.
        $this->add_action_buttons(true, get_string('submit'));
    }
}

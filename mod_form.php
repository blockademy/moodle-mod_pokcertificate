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
 * pokcertificate configuration form
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use mod_pokcertificate\persistent\pokcertificate;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/pokcertificate/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Class mod_pokcertificate_mod_form
 *
 * Represents the form for configuring the mod_pokcertificate module instance.
 */
class mod_pokcertificate_mod_form extends moodleform_mod {
    /**
     * Definition method for the form.
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;
        $renderer = $PAGE->get_renderer('mod_pokcertificate');
        $renderer->verify_authentication_check();

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('certificatename', 'pokcertificate'), ['size' => '48']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement(
            'text',
            'institution',
            get_string('institution', 'pokcertificate'),
            ['size' => '48', 'readonly' => true]
        );
        if (get_config('mod_pokcertificate', 'institution')) {
            $mform->setDefault('institution', get_config('mod_pokcertificate', 'institution'));
        }
        $mform->setType('institution', PARAM_TEXT);
        $mform->addRule('institution', null, 'required', null, 'client');
        $mform->addHelpButton('institution', 'institution_help', 'pokcertificate');

        $mform->addElement('text', 'title', get_string('title', 'pokcertificate'), ['size' => '48']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('title', PARAM_TEXT);
        } else {
            $mform->setType('title', PARAM_CLEANHTML);
        }
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('title', 'title', 'pokcertificate');

        $this->standard_intro_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();

        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
        // This is where we can add the data from the flexurl table to the data provided.

        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = (array) unserialize_array($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printlastmodified'])) {
                $defaultvalues['printlastmodified'] = $displayoptions['printlastmodified'];
            }
        }
    }


    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        if (!empty($data->instance)) {
            $pokrecord = pokcertificate::get_record(['id' => $data->instance]);
            $data->templateid = 0;
            if ($pokrecord) {
                $data->templateid = $pokrecord->get('templateid');
            }
        }

        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix};
            $autocompletion = !empty($completion) && $completion == COMPLETION_TRACKING_AUTOMATIC;
            if (!$autocompletion || empty($data->{'completionsubmit' . $suffix})) {
                $data->{'completionsubmit' . $suffix} = 0;
            }
        }
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $suffix = $this->get_suffix();
        $completionsubmit = 'completionsubmit' . $suffix;
        $mform->addElement('checkbox', $completionsubmit, '', get_string('completionmustrecievecert', 'pokcertificate'));
        // Enable this completion rule by default.
        $mform->setDefault($completionsubmit, 1);
        return [$completionsubmit];
    }

    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        return !empty($data['completionsubmit' . $suffix]);
    }
}

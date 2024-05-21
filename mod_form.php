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
 * @package mod_pokcertificate
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/pokcertificate/locallib.php');
require_once($CFG->libdir . '/filelib.php');

class mod_pokcertificate_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;

        $config = get_config('mod_pokcertificate');
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

        $mform->addElement('header', 'appearancehdr', get_string('appearance'));
        $options = [];
        if ($this->current->instance) {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
            if (isset($config->displayoptions)) {
                $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions));
            }
        }
        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'pokcertificate'), $options);
            $mform->setDefault('display', $config->display);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'pokcertificate'), ['size' => 3]);
            if (count($options) > 1) {
                $mform->hideIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'pokcertificate'), ['size' => 3]);
            if (count($options) > 1) {
                $mform->hideIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
        }

        // Add legacy files flag only if used.
        if (isset($this->current->legacyfiles) && $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = [
                RESOURCELIB_LEGACYFILES_DONE   => get_string('legacyfilesdone', 'pokcertificate'),
                RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'pokcertificate')
            ];
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'pokcertificate'), $options);
            $mform->setAdvanced('legacyfiles', 1);
        }

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

        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('pokcertificate');
            $defaultvalues['pokcertificate']['format'] = $defaultvalues['contentformat'];
            $defaultvalues['pokcertificate']['text']   = file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_pokcertificate',
                'content',
                0,
                pokcertificate_get_editor_options($this->context),
                $defaultvalues['content']
            );
            $defaultvalues['pokcertificate']['itemid'] = $draftitemid;
        }
        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = (array) unserialize_array($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printlastmodified'])) {
                $defaultvalues['printlastmodified'] = $displayoptions['printlastmodified'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $defaultvalues['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $defaultvalues['popupheight'] = $displayoptions['popupheight'];
            }
        }
    }
}

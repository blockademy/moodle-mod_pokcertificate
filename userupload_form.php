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
 * User bulk upload form
 *
 * @package     mod_pokcertificate
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Narendra.Patel <narendra.patel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use moodleform;
use csv_import_reader;
use core_text;
define('ADD_UPDATE', 3);

/**
 * Form class for uploading users in the Pokcertificate module.
 */
class mod_pokcertificate_userupload_form extends moodleform {
    /**
     * Defines the elements and structure of the user upload form.
     */
    public function definition() {
        $mform = $this->_form;
        $auths = \core_component::get_plugin_list('auth');
        $enabled = get_string('pluginenabled', 'core_plugin');
        $disabled = get_string('plugindisabled', 'core_plugin');
        $authoptions = [];
        $cannotchangepass = [];
        $cannotchangeusername = [];

        $mform->addElement('filepicker', 'userfile', get_string('file'));
        $mform->addRule('userfile', null, 'required');

        $mform->addElement('hidden',  'delimiter_name');
        $mform->setType('delimiter_name', PARAM_TEXT);
        $mform->setDefault('delimiter_name',  'comma');

        $mform->addElement('hidden',  'encoding');
        $mform->setType('encoding', PARAM_RAW);
        $mform->setDefault('encoding',  'UTF-8');

        $mform->addElement('hidden', 'option', ADD_UPDATE);
        $mform->setType('option', PARAM_INT);

        $this->add_action_buttons(true, get_string('upload'));
    }

}

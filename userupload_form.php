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


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use moodleform;
use csv_import_reader;
use core_text;
define('ADD_UPDATE', 3);

class mod_pokcertificate_userupload_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $auths = \core_component::get_plugin_list('auth');
        $enabled = get_string('pluginenabled', 'core_plugin');
        $disabled = get_string('plugindisabled', 'core_plugin');
        $authoptions = array();
        $cannotchangepass = array();
        $cannotchangeusername = array();
        foreach ($auths as $auth => $unused) {
            if ($auth == 'nologin') 
                continue;
                $authinst = get_auth_plugin($auth);
            

            if (!$authinst->is_internal()) {
                $cannotchangeusername[] = $auth;
            }

            $passwordurl = $authinst->change_password_url();
            if (!($authinst->can_change_password() && empty($passwordurl))) {
                if ($userid < 1 && $authinst->is_internal()) {
                    // This is unlikely but we can not create account without password.
                    // When plugin uses passwords, we need to set it initially at least.
                } else {
                    $cannotchangepass[] = $auth;
                }
            }
            if (is_enabled_auth($auth)) {
                $authoptions[$auth] = get_string('pluginname', "auth_{$auth}");
            }
        }

        $mform->addElement('filepicker', 'userfile', get_string('file'));
        $mform->addRule('userfile', null, 'required');

		$mform->addElement('select', 'enrollmentmethod', get_string('authenticationmethods', 'mod_pokcertificate'), $authoptions);
        $mform->addRule('enrollmentmethod', null, 'required', null, 'client');
		$mform->setType('enrollmentmethod', PARAM_TEXT);
        $mform->setDefault('enrollmentmethod', 'manual');

        $mform->addElement('advcheckbox', 'createpassword', get_string('createpassword', 'auth'));

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

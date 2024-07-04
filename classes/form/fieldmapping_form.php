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
 * Describe file fieldmapping_form
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pokcertificate\form;

defined('MOODLE_INTERNAL') || die;

use moodleform;
use mod_pokcertificate\helper;
use mod_pokcertificate\persistent\pokcertificate;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
/**
 * form shown while adding activity.
 */
class fieldmapping_form extends moodleform {

    /**
     * Definition method for the form.
     */
    public function definition() {

        global $CFG;
        $mform = $this->_form;
        $id        = $this->_customdata['id'];
        $templatename  = $this->_customdata['template'];
        $templateid  = $this->_customdata['templateid'];
        $pokid  = $this->_customdata['pokid'];
        $data  = $this->_customdata['data'];

        $defaultselect = [null => get_string('select')];
        $userfields = helper::get_internalfield_list();
        $localfields = $defaultselect + $userfields;
        $remotefields = helper::get_externalfield_list($templatename, $pokid);
        $mandatoryfields = helper::get_mandatoryfield_list($templatename, $pokid);

        $pokrecord = pokcertificate::get_record(['id' => $pokid]);

        $html =
            '<div class="table-responsive ">
            <table class="fieldlist">
            <thead>
                <tr>
                <th class="header c0 fieldmapheader" style="" scope="col">' .
            get_string("userfieldmapping", "pokcertificate") . '</th>
                <th class="header c1 " style="" scope="col">' . get_string('apifields', 'pokcertificate') . '</th>
                <th class="header c2 " style="" scope="col"></th>
                <th class="header c3 " style="" scope="col">' . get_string('userfields', 'pokcertificate') . '</th>
                </tr>
            </thead>
            <tbody>';
        $mform->addElement('html', $html);

        foreach ($mandatoryfields as $key => $value) {

            if ($key == 'institution') {
                $fieldvalue = get_config('mod_pokcertificate', 'institution');
            }
            if ($key == 'title') {
                $fieldvalue = $pokrecord->get('title');
            }
            if ($key == 'date') {
                $fieldvalue = date('d-m-Y');
            }
            if ($key == 'achiever') {
                $fieldvalue = get_string('userfullname', 'pokcertificate');
            }
            $mform->addElement('html', '<tr class="">
                                            <td class="cell c0 " style=""></td>
                                            <td class="cell c1 fieldmapfields" style="">');
            $mform->addElement(
                'select',
                'mandatoryfield_' . $key . '',
                '',
                [$key => $value],
                ['class' => 'templatefields']
            );
            $mform->addElement('html', '</td>');
            $mform->addElement('html', '<td class="cell c2 fieldmapfields" style=""><span></span></td>
                                                              <td class="cell c3 fieldmapfields" style="">');
            $mform->addElement('text', 'userfield_' . $key . '', '', ['class' => 'textfield userfields', 'readonly' => 'readonly']);
            $mform->addElement('html', '</td>');
            $mform->addElement('html', '</tr>');
            $mform->setDefault('userfield_' . $key, $fieldvalue);
            $mform->setType('userfield_' . $key, PARAM_TEXT);
        }
        $i = 0;
        foreach ($remotefields as $key => $value) {

            $mform->addElement('html', '<tr class="">
                                            <td class="cell c0 " style=""></td>
                                            <td class="cell c1 fieldmapfields" style="">');
            $mform->addElement(
                'select',
                'templatefield_' . $i . '',
                '',
                [$key => $value],
                ['class' => 'templatefields']
            );
            $mform->addElement('html', '</td>');
            $mform->addElement('html', '<td class="cell c2 fieldmapfields" style=""><span></span></td>
                                                              <td class="cell c3 fieldmapfields" style="">');
            $mform->addElement('select', 'userfield_' . $i . '', '', $localfields, ['class' => 'userfields']);
            $mform->addRule('userfield_' . $i . '', get_string('required'), 'required', null, 'client');
            $mform->addElement('html', '</td>');
            $mform->addElement('html', '</tr>');

            $i++;
        }

        $html = '</tbody>
        </table></div>';
        $mform->addElement('html', $html);

        $mform->addElement('hidden', 'fieldcount', $i);
        $mform->setType('fieldcount', PARAM_INT);

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'temp', $templatename);
        $mform->setType('temp', PARAM_TEXT);

        $mform->addElement('hidden', 'tempid', $templateid);
        $mform->setType('tempid', PARAM_INT);

        $mform->addElement('hidden', 'pokid', $pokid);
        $mform->setType('pokid', PARAM_INT);

        $this->set_data($data);
        $this->add_action_buttons(true, get_string('save'));
    }
}

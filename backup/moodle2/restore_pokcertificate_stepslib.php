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
 * Restore stepslib for pokcertificate activity.
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Aleti Vinod Kumar <vinod.aleti@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_pokcertificate_activity_structure_step extends restore_activity_structure_step {
    /**
     * describes the table structure
     * issues are considered or not depending on userinfo setting
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('users');

        $paths[] = new restore_path_element('pokcertificate', '/activity/pokcertificate');
        $paths[] = new restore_path_element('fieldmapping', '/activity/pokcertificate/fieldmappings/fieldmapping');
        $paths[] = new restore_path_element('template', '/activity/pokcertificate/templates/template');
        if ($userinfo) {
            $paths[] = new restore_path_element('issue', '/activity/pokcertificate/issues/issue');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * handles restore on pokcertificate table
     * @param object $data data
     */
    protected function process_pokcertificate($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        unset($data->id);

        $newitemid = $DB->insert_record('pokcertificate', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * handles restore on pokcertificate_fieldmapping table
     * @param object $data data
     */
    protected function process_fieldmapping($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->pokid = $this->get_new_parentid('pokcertificate');

        $newitemid = $DB->insert_record('pokcertificate_fieldmapping', $data);
        $this->set_mapping('fieldmapping', $oldid, $newitemid);
    }

    /**
     * handles restore on pokcertificate_templates table
     * @param object $data data
     */
    protected function process_template($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->pokid = $this->get_new_parentid('pokcertificate');

        $newitemid = $DB->insert_record('pokcertificate_templates', $data);
        $this->set_mapping('template', $oldid, $newitemid);
    }

    /**
     * handles restore on pokcertificate_issues table
     * @param object $data data
     */
    protected function process_issue($data) {
        global $DB;

        $data = (object)$data;

        $data->pokid = $this->get_new_parentid('pokcertificate');
        $data->templateid = $this->get_mappingid('pokcertificate_template', $data->templateid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('pokcertificate_issues', $data);
    }
}

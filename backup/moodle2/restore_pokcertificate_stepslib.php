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
 * @package   mod_pokcertificate
 * @category  restore
 * @copyright 2024 Moodle India Information Solutions Pvt Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_pokcertificate_activity_task
 */

/**
 * Structure step to restore one pokcertificate activity
 */
class restore_pokcertificate_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = [];
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');
        $paths[] = new restore_path_element('pokcertificate', '/activity/pokcertificate');
        if ($userinfo) {
            $paths[] = new restore_path_element('pokcertificate_issue', '/activity/pokcertificate/issues/issue');
        }
        $paths[] = new restore_path_element('pokcertificate_fieldmapping', '/activity/pokcertificate/fieldmappings/fieldmapping');
        $paths[] = new restore_path_element('pokcertificate_template', '/activity/pokcertificate/templates/template');
        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_pokcertificate($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        if (isset($data->templateid)) {
            $data->templateid = $data->templateid;
        }
        // Insert the pokcertificate record.
        $newitemid = $DB->insert_record('pokcertificate', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_pokcertificate_issue($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->certid = $this->get_new_parentid('pokcertificate');
        $newitemid = $DB->insert_record('pokcertificate_issues', $data);
        $this->set_mapping('pokcertificate_issues', $oldid, $newitemid);
    }

    protected function process_pokcertificate_fieldmapping($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->certid = $this->get_new_parentid('pokcertificate');
        $newitemid = $DB->insert_record('pokcertificate_fieldmapping', $data);
        $this->set_mapping('pokcertificate_fieldmapping', $oldid, $newitemid);
    }

    protected function process_pokcertificate_template($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->pokid = $this->get_new_parentid('pokcertificate');
        $newitemid = $DB->insert_record('pokcertificate_templates', $data);
        $this->set_mapping('pokcertificate_templates', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add pokcertificate related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_pokcertificate', 'intro', null);
    }
}

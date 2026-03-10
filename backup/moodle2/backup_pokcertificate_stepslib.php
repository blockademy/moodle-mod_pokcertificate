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
 * Backup stepslib for pokcertificate activity.
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Aleti Vinod Kumar <vinod.aleti@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_pokcertificate_activity_structure_step extends backup_activity_structure_step {
    /**
     * describes the table structure
     * issues are considered or not depending on userinfo setting
     */
    protected function define_structure() {
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $pokcertificate = new backup_nested_element('pokcertificate', ['id'], [
            'course', 'name', 'intro', 'introformat', 'title', 'orgname', 'orgid',
            'page', 'templateid', 'display', 'displayoptions', 'completionsubmit',
            'usercreated', 'timecreated', 'usermodified', 'timemodified',
        ]);

        $fieldmappings = new backup_nested_element('fieldmappings');
        $fieldmapping = new backup_nested_element('fieldmapping', ['id'], [
            'pokid', 'templatefield', 'userfield', 'timecreated', 'timemodified',
        ]);

        $templates = new backup_nested_element('templates');
        $template = new backup_nested_element('template', ['id'], [
            'pokid', 'templatetype', 'templatename', 'templatedefinition',
            'responsevalue', 'usercreated', 'timecreated', 'usermodified', 'timemodified',
        ]);

        $issues = new backup_nested_element('issues');
        $issue = new backup_nested_element('issue', ['id'], [
            'pokid', 'userid', 'useremail', 'status', 'templateid',
            'certificateurl', 'pokcertificateid', 'timecreated',
        ]);

        // Build the tree.
        $pokcertificate->add_child($fieldmappings);
        $fieldmappings->add_child($fieldmapping);

        $pokcertificate->add_child($templates);
        $templates->add_child($template);

        $pokcertificate->add_child($issues);
        $issues->add_child($issue);

        // Define sources.
        $pokcertificate->set_source_table('pokcertificate', ['id' => backup::VAR_ACTIVITYID]);
        $fieldmapping->set_source_table('pokcertificate_fieldmapping', ['pokid' => backup::VAR_PARENTID]);
        $template->set_source_table('pokcertificate_templates', ['pokid' => backup::VAR_PARENTID]);
        if ($userinfo) {
            $issue->set_source_table('pokcertificate_issues', ['pokid' => backup::VAR_PARENTID]);
        }

        // Return the root element (pokcertificate), wrapped into standard activity structure.
        return $this->prepare_activity_structure($pokcertificate);
    }
}

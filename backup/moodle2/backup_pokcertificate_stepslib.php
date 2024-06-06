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
 * @category  backup
 * @copyright 2024 Moodle India Information Solutions Pvt Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete pokcertificate structure for backup, with file and id annotations
 */
class backup_pokcertificate_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $pokcertificate = new backup_nested_element(
            'pokcertificate',
            ['id'],
            [
                'name', 'course', 'intro', 'introformat', 'title', 'orgname', 'orgid',
                'templateid', 'display', 'displayoptions',
                'usercreated', 'usermodified', 'timecreated', 'timemodified',
            ]
        );

        $issues = new backup_nested_element('issues');

        $issue = new backup_nested_element(
            'issue',
            ['id'],
            ['pokid', 'userid', 'useremail', 'status', 'templateid', 'certificateurl', 'pokcertificateid', 'timecreated']
        );

        $fieldmappings = new backup_nested_element('fieldmappings');

        $fieldmapping = new backup_nested_element(
            'fieldmapping',
            ['id'],
            ['pokid', 'templatefield', 'userfield', 'timecreated', 'timemodified']
        );

        $templates = new backup_nested_element('templates');

        $template = new backup_nested_element(
            'template',
            ['id'],
            [
                'pokid', 'templatetype', 'templatename', 'templatedefinition', 'responsevalue',
                'usercreated', 'usermodified', 'timecreated', 'timemodified',
            ]
        );

        // Remember that order is important, try moving this line to the end and compare XML.
        $pokcertificate->add_child($issues);
        $issues->add_child($issue);

        $pokcertificate->add_child($fieldmappings);
        $fieldmappings->add_child($fieldmapping);

        $pokcertificate->add_child($templates);
        $templates->add_child($template);

        $pokcertificate->set_source_table('pokcertificate', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            if ($userinfo) {
                $issue->set_source_sql(
                    'SELECT *
            FROM {pokcertificate_issues}
            WHERE pokid = ?',
                    ['pokid' => backup::VAR_PARENTID]
                );
            }
            // Define id annotations.
            $issue->annotate_ids('user', 'userid');
        }

        $fieldmapping->set_source_sql(
            'SELECT *
            FROM {pokcertificate_fieldmapping}
            WHERE pokid = ?',
            ['pokid' => backup::VAR_PARENTID]
        );

        $template->set_source_sql(
            'SELECT *
            FROM {pokcertificate_templates}
            WHERE pokid = ?',
            ['pokid' => backup::VAR_PARENTID]
        );
        // Return the root element (pokcertificate], wrapped into standard activity structure.
        return $this->prepare_activity_structure($pokcertificate);
    }
}

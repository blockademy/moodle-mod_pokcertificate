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
 * TODO describe file lib
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * POK certificate conversion handler
 */
class moodle1_mod_pokcertificate_handler extends moodle1_mod_handler {


    /** @var int cmid */
    protected $moduleid = null;


    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@link convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * Note that the path /MOODLE_BACKUP/COURSE/MODULES/MOD/POKCERTIFICATE does not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@link convert_path} instances
     */
    public function get_paths() {
        return array(
            new convert_path(
                'pokcertificate',
                '/MOODLE_BACKUP/COURSE/MODULES/MOD/POKCERTIFICATE',
                array(
                    'renamefields' => array(
                        'description' => 'intro',
                        'format' => 'introformat',
                    )
                )
            ),
            new convert_path(
                'pokcertificate_issues',
                '/MOODLE_BACKUP/COURSE/MODULES/MOD/POKCERTIFICATE/ISSUES'
            ),
            new convert_path(
                'pokcertificate_issue',
                '/MOODLE_BACKUP/COURSE/MODULES/MOD/POKCERTIFICATE/ISSUES/ISSUE'
            ),
            new convert_path(
                'pokcertificate_fieldmappings',
                '/MOODLE_BACKUP/COURSE/MODULES/MOD/POKCERTIFICATE/FIELDMAPPINGS',
            ),
            new convert_path(
                'pokcertificate_fieldmapping',
                '/MOODLE_BACKUP/COURSE/MODULES/MOD/POKCERTIFICATE/FIELDMAPPINGS/FIELDMAPPING',
            ),
            new convert_path(
                'pokcertificate_templates',
                '/MOODLE_BACKUP/COURSE/MODULES/MOD/POKCERTIFICATE/TEMPLATES',
            ),
            new convert_path(
                'pokcertificate_template',
                '/MOODLE_BACKUP/COURSE/MODULES/MOD/POKCERTIFICATE/TEMPLATES/TEMPLATE'
            ),

        );
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/POKCERTIFICATE
     * data available
     */
    public function process_pokcertificate($data) {
        global $CFG;

        // get the course module id and context id
        $instanceid     = $data['id'];
        $cminfo         = $this->get_cminfo($instanceid);
        $this->moduleid = $cminfo['id'];
        $contextid      = $this->converter->get_contextid(CONTEXT_MODULE, $this->moduleid);

        // get a fresh new file manager for this instance
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_pokcertificate');

        // convert course files embedded into the intro
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid   = 0;
        $data['intro'] = moodle1_converter::migrate_referenced_files($data['intro'], $this->fileman);

        // convert the introformat if necessary
        if ($CFG->texteditors !== 'textarea') {
            $data['intro'] = text_to_html($data['intro'], false, false, true);
            $data['introformat'] = FORMAT_HTML;
        }

        // start writing pokcertificate.xml
        $this->open_xml_writer("activities/pokcertificate{$this->moduleid}/pokcertificate.xml");
        $this->xmlwriter->begin_tag('activity', array(
            'id' => $instanceid, 'moduleid' => $this->moduleid,
            'modulename' => 'pokcertificate', 'contextid' => $contextid
        ));
        $this->xmlwriter->begin_tag('pokcertificate', array('id' => $instanceid));

        foreach ($data as $field => $value) {
            if ($field <> 'id') {
                $this->xmlwriter->full_tag($field, $value);
            }
        }
        return $data;
    }

    public function on_pokcertificate_issues_start() {
        $this->xmlwriter->begin_tag('issues');
    }

    public function process_pokcertificate_issues($data) {
        $this->write_xml('issue', $data, array('/issue/id'));
    }

    public function on_pokcertificate_issues_end() {
        $this->xmlwriter->end_tag('issues');
    }

    public function on_pokcertificate_fieldmappings_start() {
        $this->xmlwriter->begin_tag('fieldmappings');
    }

    public function process_pokcertificate_fieldmapping($data) {
        $this->write_xml('fieldmapping', $data, array('/fieldmapping/id'));
    }

    public function on_pokcertificate_fieldmappings_end() {
        $this->xmlwriter->end_tag('fieldmappings');
    }

    public function on_pokcertificate_templates_start() {
        $this->xmlwriter->begin_tag('templates');
    }

    public function process_pokcertificate_templates($data) {
        $this->write_xml('template', $data, array('/template/id'));
    }

    public function on_pokcertificate_templates_end() {
        $this->xmlwriter->end_tag('templates');
    }
    /**
     * This is executed when we reach the closing </MOD> tag of our 'pokcertificate' path
     */
    public function on_pokcertificate_end() {
        $this->xmlwriter->end_tag('pokcertificate');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // write inforef.xml
        $this->open_xml_writer("activities/pokcertificate_{$this->moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');

        $this->close_xml_writer();
    }
}

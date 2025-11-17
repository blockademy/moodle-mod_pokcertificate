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

namespace mod_pokcertificate\external;

use core_course\external\helper_for_get_mods_by_courses;
use external_api;
use external_files;
use external_format_value;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use util;
use mod_pokcertificate\pok;
use mod_pokcertificate\helper;

/**
 * pokcertificate external functions
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_pokcertificates_by_courses extends \external_api {
    /**
     * Describes the parameters for execute.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'),
                    'Array of course ids',
                    VALUE_DEFAULT,
                    []
                ),
            ]
        );
    }

    /**
     * Returns a list of pokcertificates in a provided list of courses.
     * If no list is provided all pokcertificates that the user can view will be returned.
     *
     * @param array $courseids course ids
     * @return array of warnings and pokcertificates
     * @since Moodle 3.3
     */
    public static function execute($courseids = []) {

        $warnings = [];
        $returnedpokcertificates = [];

        $params = [
            'courseids' => $courseids,
        ];
        $params = self::validate_parameters(self::execute_parameters(), $params);

        $mycourses = [];
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {
            [$courses, $warnings] = util::validate_courses($params['courseids'], $mycourses);

            // Get the pokcertificates in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $pokcertificates = get_all_instances_in_courses("pokcertificate", $courses);
            foreach ($pokcertificates as $pokcertificate) {
                $pokdetails = helper_for_get_mods_by_courses::format_name_and_intro($pokcertificate, 'mod_pokcertificate');
                $context = \context_module::instance($pokcertificate->coursemodule);
                self::validate_context($context);
                [$pokcertificate->content, $pokcertificate->contentformat] = \core_external\util::format_text(
                    $pokcertificate->content,
                    $pokcertificate->contentformat,
                    $context,
                    'mod_pokcertificate',
                    'content',
                    $pokcertificate->revision,
                    ['noclean' => true]
                );
                $pokcertificate->contentfiles = util::get_area_files($context->id, 'mod_pokcertificate', 'content');
                if (has_capability('moodle/course:manageactivities', $context)) {
                    $pokdetails['timemodified']  = $pokcertificate->timemodified;
                    $pokdetails['completionsubmit']  = $pokcertificate->completionsubmit;
                }
                $returnedpokcertificates[] = $pokdetails;
            }
        }

        $result = [
            'pokcertificates' => $returnedpokcertificates,
            'warnings' => $warnings,
        ];
        return $result;
    }

    /**
     * Describes the execute return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'pokcertificates' => new external_multiple_structure(
                    new external_single_structure(array_merge(
                        helper_for_get_mods_by_courses::standard_coursemodule_elements_returns(),
                        [
                            'content' => new external_value(PARAM_RAW, 'Page content'),
                            'contentformat' => new external_format_value('content', VALUE_REQUIRED, 'Content format'),
                            'contentfiles' => new external_files('Files in the content'),
                            'legacyfiles' => new external_value(PARAM_INT, 'Legacy files flag'),
                            'legacyfileslast' => new external_value(PARAM_INT, 'Legacy files last control flag'),
                            'display' => new external_value(PARAM_INT, 'How to display the pokcertificate'),
                            'displayoptions' => new external_value(PARAM_RAW, 'Display options (width, height)'),
                            'revision' => new external_value(PARAM_INT, 'Incremented when after each file changes, to avoid cache'),
                            'timemodified' => new external_value(PARAM_INT, 'Last time the pokcertificate was modified'),
                            'completionsubmit' => new external_value(
                                PARAM_BOOL,
                                'Completion on receiving certificate',
                                VALUE_OPTIONAL
                            ),

                        ]
                    ))
                ),
                'warnings' => new external_warnings(),
            ]
        );
    }
}

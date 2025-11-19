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

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
global $CFG;
require_once("{$CFG->libdir}/externallib.php");

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
class view_pokcertificate extends \external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'pokcertificateid' => new external_value(PARAM_INT, 'pokcertificate instance id'),
            ]
        );
    }

    /**
     * Simulate the pokcertificate/view.php web interface pokcertificate: trigger events, completion, etc...
     *
     * @param int $pokcertificateid the pokcertificate instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function execute($pokcertificateid) {

        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/pokcertificate/lib.php");

        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'pokcertificateid' => $pokcertificateid,
            ]
        );
        $warnings = [];

        // Request and permission validation.
        $pokcertificate = $DB->get_record('pokcertificate', ['id' => $params['pokcertificateid']], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($pokcertificate, 'pokcertificate');

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/pokcertificate:view', $context);

        // Call the pokcertificate/lib API.
        pokcertificate_view($pokcertificate, $course, $cm, $context);

        $result = [];
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     * @since Moodle 3.0
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings(),
            ]
        );
    }
}
